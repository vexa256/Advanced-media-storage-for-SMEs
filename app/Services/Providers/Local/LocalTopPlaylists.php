<?php

namespace App\Services\Providers\Local;

use App\Album;
use App\Playlist;
use App\Services\Providers\ContentProvider;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class LocalTopPlaylists implements ContentProvider
{
    /**
     * @return Collection
     */
    public function getContent() {
        return app(Playlist::class)
            ->with(['tracks.album', 'editors' => function (BelongsToMany $q) {
                return $q->compact();
            }])
            ->has('tracks')
            ->orderBy('views', 'desc')
            ->limit(50)
            ->get();
    }
}