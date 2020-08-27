<?php namespace App\Services\Providers\Local;

use App\Album;
use App\Services\Providers\ContentProvider;
use Illuminate\Database\Eloquent\Collection;

class LocalNewAlbums implements ContentProvider {

    /**
     * @return Collection
     */
    public function getContent() {
        return Album::with('artist', 'tracks.artists')
            ->orderBy('release_date', 'desc')
            ->limit(40)
            ->get();
    }
}