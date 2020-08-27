<?php

namespace App\Services\Providers\Lastfm;

use App\Artist;
use App\Genre;
use App\Services\HttpClient;
use App\Services\Providers\ContentProvider;
use App\Services\Providers\SaveOrUpdate;
use Illuminate\Support\Str;

class LastfmGenreArtists implements ContentProvider
{
    use SaveOrUpdate;

    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var string
     */
    private $apiKey;

    public function __construct()
    {
        $this->httpClient = new HttpClient(['base_uri' => 'http://ws.audioscrobbler.com/2.0/']);
        $this->apiKey = config('common.site.lastfm.key');

        ini_set('max_execution_time', 0);
    }

    public function getContent(Genre $genre = null)
    {
        $genreName = $genre['name'];
        $response  = $this->httpClient->get("?method=tag.gettopartists&tag=$genreName&api_key=$this->apiKey&format=json&limit=50");
        $artists   = $response['topartists']['artist'];
        $names     = [];
        $formatted = [];

        foreach($artists as $artist) {
            if ( ! $this->collectionContainsArtist($artist['name'], $formatted)) {
                $formatted[] = [
                    'name' => $artist['name'],
                ];
                $names[] = $artist['name'];
            }
        }

        $existing = Artist::whereIn('name', $names)->get();

        $insert = array_filter($formatted, function($artist) use ($existing) {
            return ! $this->collectionContainsArtist($artist['name'], $existing);
        });

        Artist::insert($insert);
        $artists = Artist::whereIn('name', $names)->get();
        $genre->artists()->syncWithoutDetaching($artists->pluck('id')->toArray());

        return $artists;
    }

    private function collectionContainsArtist($name, $collection) {
        foreach ($collection as $artist) {
            if ($this->normalizeName($name) === $this->normalizeName($artist['name'])) {
                return true;
            }
        }
        return false;
    }

    private function normalizeName($name)
    {
        return trim(Str::ascii(mb_strtolower($name)));
    }
}