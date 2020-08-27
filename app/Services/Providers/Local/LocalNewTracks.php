<?php namespace App\Services\Providers\Local;

use App\Services\Providers\ContentProvider;
use App\Track;
use Illuminate\Database\Eloquent\Collection;

class LocalNewTracks implements ContentProvider {

    /**
     * @return Collection
     */
    public function getContent() {
        return app(Track::class)
            ->with('album', 'artists')
            ->withCount('plays')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
    }
}
