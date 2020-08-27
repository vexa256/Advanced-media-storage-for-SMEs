<?php namespace App\Services\Providers\Spotify;

use App\Artist;
use App\Services\Providers\ContentProvider;
use Illuminate\Database\Eloquent\Collection;

class SpotifyTopArtists implements ContentProvider {

    /**
     * @return Collection
     */
    public function getContent() {
        return Artist::orderBy('spotify_popularity', 'desc')
            ->limit(40)
            ->get();
    }
}
