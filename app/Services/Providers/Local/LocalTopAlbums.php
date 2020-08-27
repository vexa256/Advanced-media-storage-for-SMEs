<?php namespace App\Services\Providers\Local;

use App\Album;
use App\Services\Providers\ContentProvider;
use Illuminate\Database\Eloquent\Collection;

class LocalTopAlbums implements ContentProvider
{
    /**
     * @return Collection
     */
    public function getContent() {
        return Album::with('artist', 'tracks.artists')
            ->has('tracks', '>=', 5)
            ->withCount('plays')
            ->orderBy('plays_count', 'desc')
            ->limit(40)
            ->get();
    }
}