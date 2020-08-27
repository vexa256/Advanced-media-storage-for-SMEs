<?php namespace App\Services;

use App;
use App\Album;
use App\Artist;
use App\Channel;
use App\Genre;
use App\Playlist;
use App\Track;
use App\User;
use Carbon\Carbon;
use Common\Pages\CustomPage;
use Common\Settings\Settings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Storage;
use Str;

class SitemapGenerator {

    /**
     * @var Settings
     */
    private $settings;

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * How much records to process per db query.
     *
     * @var integer
     */
    private $queryLimit = 6000;

    /**
     * Base site url.
     *
     * @var string
     */
    private $baseUrl = '';

    /**
     * Storage directory url.
     *
     * @var string
     */
    private $storageUrl = '';

    /**
     * Current date and time string.
     *
     * @var string
     */
    private $currentDateTimeString = '';

    /**
     * @var array
     */
    private $resources = [
        'albums'     => ['id', 'name', 'artist_id', 'artist_type'],
        'playlists'  => ['id', 'name'],
        'artists'    => ['id', 'name', 'updated_at'],
        'tracks'     => ['id', 'name'],
        'genres'     => ['id', 'name', 'updated_at'],
        'users'      => ['id', 'first_name', 'last_name', 'email', 'updated_at'],
    ];

    /**
     * How many items do we have in current xml string.
     *
     * @var int
     */
    private $counter = 0;

    /**
     * How many sitemaps we have already generated for current resource.
     *
     * @var int
     */
    private $sitemapCounter = 1;

    /**
     * @var string|boolean
     */
    private $xml = false;

    /**
     * @var UrlGenerator
     */
    private $urlGenerator;

    /**
     * @param Settings $settings
     * @param Filesystem $fs
     * @param UrlGenerator $urlGenerator
     */
    public function __construct(Settings $settings, Filesystem $fs, UrlGenerator $urlGenerator)
    {
        $this->fs = $fs;
        $this->settings = $settings;
        $this->urlGenerator = $urlGenerator;
        $this->baseUrl = url('') . '/';
        $this->storageUrl = url('storage') . '/';
        $this->currentDateTimeString = Carbon::now()->toDateTimeString();

        ini_set('memory_limit', '160M');
        ini_set('max_execution_time', 7200);
    }

    /**
     * Generate a sitemap of all urls of the site.
     *
     * @return void
     */
    public function generate()
    {
        $index = [];

        foreach ($this->resources as $mapName => $columns) {
            $index[$mapName] = $this->makeDynamicMaps($mapName, $columns);
        }

        $this->makeStaticMap();
        $this->makeIndex($index);
    }

    /**
     * Make fully qualified url on the site for given item.
     *
     * @param string $mapName
     * @param Model $item
     * @return string
     */
    private function makeItemUrl($mapName, Model $item)
    {
        $method = Str::singular($mapName);
        return $this->urlGenerator->$method($item->toArray());
    }

    private function getItemUpdatedAtTime($item = null)
    {
        $date = (! isset($item->updated_at) || $item->updated_at == '0000-00-00 00:00:00') ? $this->currentDateTimeString : $item->updated_at;
        return date('Y-m-d\TH:i:sP', strtotime($date));
    }

    /**
     * Generate sitemap and save it to a file.
     *
     * @param string $fileName
     */
    private function save($fileName)
    {
        $this->xml .= "\n</urlset>";

        Storage::disk('public')->put('sitemaps/'.$fileName.'.xml', $this->xml);

        $this->xml = false;
        $this->counter = 0;
        $this->sitemapCounter++;
    }

    /**
     * @param string $name
     * @param array $columns
     * @return Album|Artist|Genre|Playlist|Track|User
     */
    private function getModel($name, $columns)
    {
        if ($name === 'artists') {
            return Artist::where('fully_scraped', true)->select($columns);
        } else if ($name === 'albums') {
            return Album::with('artist')->where('fully_scraped', true)->orWhere('local_only', true)->select($columns);
        } else if ($name === 'tracks') {
            return Track::select($columns);
        } else if ($name === 'playlists') {
            return Playlist::where('public', true)->select($columns);
        } else if ($name === 'genres') {
            return Genre::select($columns);
        } else if ($name === 'users') {
            return User::select($columns);
        }
    }

    /**
     * Add new url line to xml string.
     *
     * @param string $mapName
     * @param mixed $item
     * @param string $url
     * @param string $updatedAt
     */
    private function addNewLine($mapName = null, $item = null, $url = null, $updatedAt = null)
    {
        $url       = $url ? $url : $this->makeItemUrl($mapName, $item);
        $updatedAt = $updatedAt ? $updatedAt : $this->getItemUpdatedAtTime($item);

        if ($this->xml === false) {
            $this->startNewXmlFile();
        }

        if ($this->counter === 50000) {
            $this->save("$mapName-sitemap-{$this->sitemapCounter}");
            $this->startNewXmlFile();
        }

        $line = "\t"."<url>\n\t\t<loc>".htmlspecialchars($url)."</loc>\n\t\t<lastmod>".$updatedAt."</lastmod>\n\t\t<changefreq>weekly</changefreq>\n\t\t<priority>1.00</priority>\n\t</url>\n";

        $this->xml .= $line;

        $this->counter++;
    }

    /**
     * Add xml headers to xml string
     */
    private function startNewXmlFile()
    {
        $this->xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">'."\n";
    }

    /**
     * Create sitemaps for all dynamic resources.
     *
     * @param  string $mapName
     * @param  array $columns
     * @return integer
     */
    private function makeDynamicMaps($mapName, array $columns)
    {
        $this->getModel($mapName, $columns)->orderBy('id')->chunk($this->queryLimit, function($items) use($mapName) {
            foreach ($items as $item) {
                $this->addNewLine($mapName, $item);
            }
        });

        //check for unused items
        if ($this->xml) {
            $this->save("$mapName-sitemap-{$this->sitemapCounter}");
        }

        $index = $this->sitemapCounter-1;

        $this->sitemapCounter = 1;
        $this->counter = 0;

        return $index;
    }

    /**
     * Create a sitemap for static pages.
     *
     * @return void
     */
    private function makeStaticMap()
    {
        $this->addNewLine(false, false, $this->baseUrl, $this->getItemUpdatedAtTime());

        CustomPage::all()->each(function(CustomPage $page) {
            $this->addNewLine(false, false, $this->urlGenerator->page($page->toArray()), $this->getItemUpdatedAtTime());
        });

        Channel::all()->each(function(Channel $channel) {
            $this->addNewLine(false, false, $this->urlGenerator->channel($channel->toArray()), $this->getItemUpdatedAtTime());
        });

        $this->save("static-urls-sitemap");
    }

    /**
     * Create a sitemap index from all individual sitemaps.
     *
     * @param  array  $index
     * @return void
     */
    private function makeIndex(array $index)
    {
        $string = '<?xml version="1.0" encoding="UTF-8"?>'."\n".
            '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($index as $resource => $number) {
            for ($i=1; $i <= $number; $i++) {
                $url = $this->storageUrl."sitemaps/{$resource}-sitemap-$i.xml";
                $string .= "\t<sitemap>\n"."\t\t<loc>$url</loc>\n"."\t\t<lastmod>{$this->getItemUpdatedAtTime()}</lastmod>\n"."\t</sitemap>\n";
            }
        }

        $string .= "\t<sitemap>\n\t\t<loc>{$this->storageUrl}sitemaps/static-urls-sitemap.xml</loc>\n\t\t<lastmod>{$this->getItemUpdatedAtTime()}</lastmod>\n\t</sitemap>\n";

        $string .= '</sitemapindex>';

        Storage::disk('public')->put('sitemaps/sitemap-index.xml', $string);
    }
}
