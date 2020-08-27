<?php namespace App\Services\Providers\Spotify;

use App;
use App\Album;
use App\Services\HttpClient;
use Illuminate\Support\Collection;

class SpotifyAlbum {

    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var SpotifyArtist
     */
    private $spotifyArtist;

    /**
     * @var SpotifyNormalizer
     */
    private $normalizer;

    /**
     * @param SpotifyArtist $spotifyArtist
     * @param SpotifyNormalizer $normalizer
     */
    public function __construct(SpotifyArtist $spotifyArtist, SpotifyNormalizer $normalizer) {
        $this->httpClient = App::make('SpotifyHttpClient');
        $this->spotifyArtist = $spotifyArtist;
        $this->normalizer = $normalizer;
    }

    /**
     * @param Album $album
     * @return array
     */
    public function getAlbum(Album $album) {

        if ($album->spotify_id) {
            $spotifyAlbum = $this->httpClient->get("albums/{$album->spotify_id}");
        } else {
            $spotifyAlbum = $this->findByName($album);
        }

        if ( ! $spotifyAlbum) {
            return null;
        }

        $normalizedAlbum = $this->normalizer->album($spotifyAlbum);

        // get full info objects for all tracks
        $normalizedAlbum = $this->getTracks($normalizedAlbum);
        $normalizedAlbum['fully_scraped'] = true;

        return $normalizedAlbum;
    }

    /**
     * @param array $normalizedAlbum
     * @return array
     */
    private function getTracks($normalizedAlbum)
    {
        $trackIds = $normalizedAlbum['tracks']->pluck('spotify_id')->slice(0, 50)->implode(',');

        $response = $this->httpClient->get("tracks?ids=$trackIds");

        $fullTracks = collect($response['tracks'])->map(function($spotifyTrack) {
            return $this->normalizer->track($spotifyTrack);
        });

        $normalizedAlbum['tracks'] = $normalizedAlbum['tracks']->map(function($track) use($fullTracks) {
            return $fullTracks->where('spotify_id', $track['spotify_id'])->first();
        });

        return $normalizedAlbum;
    }

    /**
     * @param Album $album
     * @return array|null
     */
    private function findByName(Album $album)
    {
        $albumName = trim(explode('(', $album->name)[0]);
        $artistName = $album->artist ? $album->artist->name : null;

        if ( ! $artistName) {
            $response = $this->fetchByAlbumNameOnly(urlencode($albumName));
        } else {
            $response = $this->httpClient->get('search?q=artist:'.$artistName.'%20album:'.str_replace(':', '', $albumName).'&type=album&limit=10');

            //if we couldn't find album with artist and album name, search only by album name
            if ( ! isset($response['albums']['items'][0])) {
                $response = $this->fetchByAlbumNameOnly(urlencode(str_replace(':', '', $albumName)));
            }
        }

        if (isset($response['albums']['items'][0])) {
            $album = false;

            //make sure we get exact name match when searching by name
            foreach ($response['albums']['items'] as $spotifyAlbum) {
                if (str_replace(' ', '', strtolower($spotifyAlbum['name'])) === str_replace(' ', '', strtolower($albumName))) {
                    $album = $spotifyAlbum; break;
                }
            }

            if ( ! $album) $album = $response['albums']['items'][0];

            $id = $album['id'];
            return $this->httpClient->get("albums/$id");
        }
    }

    private function fetchByAlbumNameOnly($albumName)
    {
        return $this->httpClient->get("search?q=album:$albumName&type=album&limit=10");
    }
}