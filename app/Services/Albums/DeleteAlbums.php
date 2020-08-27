<?php

namespace App\Services\Albums;

use App\Album;
use App\Track;
use Illuminate\Support\Collection;

class DeleteAlbums
{
    /**
     * @param array[]|Collection $albumIds
     */
    public function execute($albumIds)
    {
        app(Album::class)->whereIn('id', $albumIds)->delete();

        $trackIds = app(Track::class)->whereIn('album_id', $albumIds)->pluck('id');
        app(Track::class)->whereIn('id', $trackIds)->delete();

        // delete waves
        $paths = $trackIds->map(function($id) {
            return "waves/{$id}.json";
        });
        app(Track::class)->getWaveStorageDisk()->delete($paths->toArray());
    }
}
