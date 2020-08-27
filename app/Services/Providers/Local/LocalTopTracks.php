<?php namespace App\Services\Providers\Local;

use App\Track;
use App\Services\Providers\ContentProvider;
use Illuminate\Database\Eloquent\Collection;

class LocalTopTracks implements ContentProvider {

    /**
     * @return Collection
     */
    public function getContent() {
        return app(Track::class)
            ->with('album', 'artists')
            ->withCount('plays')
            ->orderBy('plays_count', 'desc')
            ->limit(50)
            ->get();
    }
}