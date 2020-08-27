<?php

namespace App\Services\Providers\Spotify;

use App\Artist;
use App\Services\Providers\SaveOrUpdate;
use App\Track;
use Illuminate\Support\Collection;

class SpotifyTrackSaver
{
    use SaveOrUpdate;

    /**
     * @param Collection $spotifyAlbums
     * @param Collection $savedAlbums
     */
    public function save($spotifyAlbums, $savedAlbums)
    {
        $spotifyTracks = $spotifyAlbums->map(function($spotifyAlbum) use($savedAlbums) {
            $albumId = $savedAlbums->where('spotify_id', $spotifyAlbum['spotify_id'])->first()->id;
            return $spotifyAlbum['tracks']->map(function($albumTrack) use($albumId) {
                $albumTrack['album_id'] = $albumId;
                return $albumTrack;
            });
        })->flatten(1);

        $this->saveOrUpdate($spotifyTracks, 'tracks');

        // attach artists to tracks
        $artists = collect($spotifyTracks)->pluck('artists')->flatten(1)->unique('spotify_id');

        $this->saveOrUpdate($artists, 'artists');
        $savedArtists = app(Artist::class)->whereIn('spotify_id', $artists->pluck('spotify_id'))->get(['spotify_id', 'id', 'name']);
        $savedTracks = app(Track::class)->whereIn('spotify_id', $spotifyTracks->pluck('spotify_id'))->get(['name', 'album_name', 'spotify_id', 'id']);

        $pivots = collect($spotifyTracks)->map(function($normalizedTrack) use($savedArtists, $savedTracks) {
            return $normalizedTrack['artists']->map(function($normalizedArtist) use($normalizedTrack, $savedArtists, $savedTracks) {
                $savedTrack = $savedTracks->where('spotify_id', $normalizedTrack['spotify_id'])->first();
                $savedArtist = $savedArtists->where('spotify_id', $normalizedArtist['spotify_id'])->first();
                if ( ! $savedTrack) {
                    $savedTrack = $savedTracks->where('name', $normalizedTrack['name'])
                        ->where('album_name', $normalizedTrack['album_name'])
                        ->first();
                }
                if ( ! $savedArtist) {
                    $savedArtist = $savedArtists->where('name', $normalizedArtist['name'])
                        ->first();
                }
                return [
                    'track_id' => $savedTrack->id,
                    'artist_id' => $savedArtist->id,
                ];
            });
        })->flatten(1);

        $this->saveOrUpdate($pivots, 'artist_track');
    }
}
