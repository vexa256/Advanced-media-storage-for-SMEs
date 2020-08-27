<?php namespace App\Services\Providers\Spotify;

use App\Artist;
use App\Album;
use App\Services\HttpClient;
use App\Services\Artists\ArtistSaver;
use App\Services\Providers\ContentProvider;
use App\Services\Providers\SaveOrUpdate;
use Illuminate\Support\Collection;

class SpotifyNewAlbums implements ContentProvider {

    use SaveOrUpdate;

    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var SpotifyArtist
     */
    private $spotifyArtist;

    /**
     * @var ArtistSaver
     */
    private $saver;

    /**
     * @param ArtistSaver $saver
     * @param SpotifyArtist $spotifyArtist
     * @param SpotifyHttpClient $httpClient
     */
    public function __construct(SpotifyArtist $spotifyArtist, ArtistSaver $saver, SpotifyHttpClient $httpClient)
    {
        $this->saver = $saver;
        $this->httpClient = $httpClient;
        $this->spotifyArtist = $spotifyArtist;

        ini_set('max_execution_time', 0);
    }

    public function getContent()
    {
        $response = $this->httpClient->get('browse/new-releases?country=US&limit=40');
        $spotifyAlbums = $this->spotifyArtist->getFullAlbums($response['albums']);

        $spotifyArtists = $spotifyAlbums->pluck('artist');
        $this->saveOrUpdate($spotifyArtists, 'artists');
        $savedArtists = Artist::whereIn('spotify_id', $spotifyArtists->pluck('spotify_id'))->get();

        // set "artist_id" on albums
        $spotifyAlbums = $spotifyAlbums->map(function($spotifyAlbum) use($savedArtists) {
            $artistId = $savedArtists->where('spotify_id', $spotifyAlbum['artist']['spotify_id'])->first()->id;
            $spotifyAlbum['artist_id'] = $artistId;
            return $spotifyAlbum;
        });

        $this->saver->saveAlbums($spotifyAlbums);
        $savedAlbums = app(Album::class)
            ->whereIn('spotify_id', $spotifyAlbums->pluck('spotify_id'))
            ->orderBy('release_date', 'desc')
            ->limit(40)
            ->get();

        app(SpotifyTrackSaver::class)->save($spotifyAlbums, $savedAlbums);

        return $savedAlbums
            ->load('artist', 'tracks')
            ->sortByDesc('artist.spotify_popularity')
            ->values();
    }
}