<?php namespace App\Services\Providers;

use App;
use Common\Settings\Settings;
use Illuminate\Support\Str;

class ProviderResolver
{
    /**
     * @var Settings
     */
    private $settings;

    /**
     * @var array
     */
    private $defaults = [
        'artist' => 'local',
        'album' => 'local',
        'search' => 'local',
        'audio_search' => 'youtube',
        'genreArtists' => 'local',
        'radio' => 'spotify'
    ];

    /**
     * @param Settings $settings
     */
    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @param string $contentType
     * @param string $preferredProvider
     * @return ContentProvider
     */
    public function get($contentType, $preferredProvider = null)
    {
        if ( ! $preferredProvider) {
            $preferredProvider = $this->resolvePreferredProviderFromSettings($contentType);
        }

        // make fully qualified provider class name
        $namespace = $this->getNamespace($contentType, $preferredProvider);

        if ( ! $contentType || ! class_exists($namespace)) {
            $namespace = $this->getNamespace($contentType, $this->defaults[$contentType]);
        }
        return App::make($namespace);
    }

    /**
     * @param $type
     * @return string
     */
    public function resolvePreferredProviderFromSettings($type)
    {
        return $this->settings->get(Str::snake($type . '_provider'), $this->defaults[$type]);
    }

    /**
     * Make fully qualified namespace for provider class.
     *
     * @param string $type
     * @param string $provider
     * @return null|string
     */
    private function getNamespace($type, $provider)
    {
        if ( ! $type || ! $provider) return null;

        // audio_search => audioSearch
        $type = Str::camel($type);

        // track:top => TopTracks
        $words = array_map(function($word) {
            return ucfirst($word);
        }, array_reverse(explode(':', $type)));
        $type = join('', $words);
        if (count($words) > 1) {
            $type = Str::plural($type);
        }

        $provider = ucfirst(Str::camel($provider));
        return 'App\Services\Providers\\' . $provider . '\\' . $provider . $type;
    }
}
