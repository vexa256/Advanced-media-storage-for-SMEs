<?php namespace Common\Core\Values;

use Auth;
use Common\Admin\Appearance\Themes\CssTheme;
use Common\Auth\Permissions\Permission;
use Common\Domains\CustomDomain;
use Common\Localizations\Localization;
use Common\Pages\CustomPage;
use Common\Settings\Settings;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Filesystem\Filesystem;
use Arr;
use Illuminate\Support\Collection;
use Str;

class ValueLists
{
    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var Localization
     */
    private $localization;

    /**
     * @param Filesystem $fs
     * @param Localization $localization
     */
    public function __construct(Filesystem $fs, Localization $localization)
    {
        $this->fs = $fs;
        $this->localization = $localization;
    }

    /**
     * @param string $names
     * @param array $params
     * @return Collection
     */
    public function get($names, $params = [])
    {
        return collect(explode(',', $names))
            ->mapWithKeys(function($name) use($params) {
                $methodName = Str::studly($name);
                $value = method_exists($this, $name) ?
                    $this->$methodName($params) :
                    $this->loadAppValueFile($name, $params);
                return [$name => $value];
            })->filter();
    }

    /**
     * @return Permission[]|Collection
     */
    public function permissions()
    {
        $permissions = app(Permission::class)->get();

        $permissions = $permissions->filter(function(Permission $permission) {
            $admin = $permission->name !== 'admin' || Auth::user()->hasExactPermission('admin');
            $customDomain = !Str::startsWith($permission->name, 'custom_domain') || config('common.site.enable_custom_domains');
            return $admin && $customDomain;
        });

        return $permissions->values();
    }

    public function currencies()
    {
        return json_decode($this->fs->get(__DIR__ . '/../../resources/lists/currencies.json'), true);
    }

    public function timezones()
    {
        return json_decode($this->fs->get(__DIR__ . '/../../resources/lists/timezones.json'), true);
    }

    public function countries()
    {
        return json_decode($this->fs->get(__DIR__ . '/../../resources/lists/countries.json'), true);
    }

    public function languages()
    {
        return json_decode($this->fs->get(__DIR__ . '/../../resources/lists/languages.json'), true);
    }

    public function localizations()
    {
        return $this->localization->get(['id', 'name', 'language']);
    }

    public function menuItemCategories()
    {
        return array_map(function($category) {
            $category['items'] = app($category['itemsLoader'])->execute();
            unset($category['itemsLoader']);
            return $category;
        }, config('common.menus'));
    }

    public function pages($params = [])
    {
        if ( ! isset($params['userId'])) {
            app(Gate::class)->authorize('index', CustomPage::class);
        }

        $query = app(CustomPage::class)
            ->select(['id', 'title'])
            ->where('type', Arr::get($params, 'pageType') ?: CustomPage::DEFAULT_PAGE_TYPE);

        if ($userId = Arr::get($params, 'userId')) {
            $query ->where('user_id', $userId);
        }

        return $query->get();
    }

    /**
     * @param $params
     * @return Collection
     */
    public function domains($params)
    {
        return app(CustomDomain::class)
            ->select(['host', 'id'])
            ->where('user_id', Arr::get($params, 'userId'))
            ->orWhere('global', true)
            ->get();
    }

    /**
     * @param $params
     * @return Collection
     */
    public function themes($params)
    {
        app(Gate::class)->authorize('index', CssTheme::class);
        return app(CssTheme::class)
            ->select(['name', 'id'])
            ->get();
    }

    /**
     * @param string $name
     * @param array $params
     * @return array|null
     */
    private function loadAppValueFile($name, $params)
    {
        $fileName = Str::kebab($name);
        $path = resource_path("lists/$fileName.json");
        if (file_exists($path)) {
            return json_decode(file_get_contents($path), true);
        }
        return null;
    }
}
