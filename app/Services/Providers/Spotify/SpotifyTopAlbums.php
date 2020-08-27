<?php namespace App\Services\Providers\Spotify;

use App\Album;
use App\Services\Providers\ContentProvider;
use Illuminate\Database\Eloquent\Collection;

class SpotifyTopAlbums implements ContentProvider {

    /**
     * @return Collection
     */
    public function getContent() {
        return Album::with('artist', 'tracks.artists')
            ->has('tracks', '>=', 5)
            ->orderBy('spotify_popularity', 'desc')
            ->limit(40)
            ->get();
    }
}