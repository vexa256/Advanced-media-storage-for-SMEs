<?php

namespace App\Services\Providers\Local;

use App\Genre;
use App\Services\Providers\ContentProvider;
use Illuminate\Support\Collection;

class LocalGenreArtists implements ContentProvider
{
    /**
     * @param Genre $genre
     * @return Collection
     */
    public function getContent(Genre $genre = null)
    {
        return collect([]);
    }
}