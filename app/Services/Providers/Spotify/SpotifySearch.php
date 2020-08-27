<?php namespace App\Services\Providers\Spotify;

use App;
use App\Album;
use App\Artist;
use App\Services\Search\SearchInterface;
use App\Track;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Arr;
use Log;

class SpotifySearch implements SearchInterface {

    /**
     * @var SpotifyHttpClient
     */
    private $httpClient;

    /**
     * @var SpotifyNormalizer
     */
    private $normalizer;

    /**
     * @param SpotifyHttpClient $spotifyHttpClient
     * @param SpotifyNormalizer $normalizer
     */
    public function __construct(SpotifyHttpClient $spotifyHttpClient, SpotifyNormalizer $normalizer) {
        $this->httpClient = $spotifyHttpClient;
        $this->normalizer = $normalizer;
    }

    /**
     * @param string  $query
     * @param int     $limit
     * @param array  $modelTypes
     * @return array
     */
    public function search($query, $limit = 10, $modelTypes = [Artist::class, Album::class, Track::class])
    {
        $spotifyTypes = collect($modelTypes)->map(function($modelType) {
            return strtolower(str_replace(app()->getNamespace(), '', $modelType));
        })->filter(function($type) {
            return in_array($type, ['artist', 'album', 'track']);
        })->implode(',');

        try {
            $response = $this->httpClient->get("search?q=$query&type=$spotifyTypes&limit=$limit");
        } catch(RequestException $e) {
            Log::error($e->getResponse()->getBody()->getContents(), ['query' => $query]);
            return ['albums' => collect([]), 'tracks' => collect([]), 'artists' => collect([])];
        }

        return $this->formatResponse($response);
    }

    /**
     * @param array   $response
     * @return array
     */
    private function formatResponse($response)
    {
        $artists = collect(Arr::get($response, 'artists.items', []))->map(function($spotifyArtist) {
            return $this->normalizer->artist($spotifyArtist);
        });
        $albums = collect(Arr::get($response, 'albums.items', []))->map(function($spotifyAlbum) {
            return $this->normalizer->album($spotifyAlbum);
        });
        $tracks = collect(Arr::get($response, 'tracks.items', []))->map(function($spotifyTrack) {
            return $this->normalizer->track($spotifyTrack);
        });
        return ['albums' => $albums, 'tracks' => $tracks, 'artists' => $artists];
    }
}
