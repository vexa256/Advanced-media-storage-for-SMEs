<?php namespace App\Services\Providers\Local;

use App\Genre;
use App\Services\Providers\ContentProvider;
use App\Services\Providers\Lastfm\LastfmTopGenres;
use Common\Settings\Settings;
use Illuminate\Database\Eloquent\Collection;

class LocalTopGenres implements ContentProvider {

    /**
     * @var Settings
     */
    private $settings;

    /**
     * @var Genre
     */
    private $genre;

    /**
     * @var LastfmTopGenres
     */
    private $lastfmGenres;

    /**
     * @param Settings $settings
     * @param Genre $genre
     * @param LastfmTopGenres $lastfmGenres
     */
    public function __construct(Settings $settings, Genre $genre, LastfmTopGenres $lastfmGenres)
    {
        $this->genre = $genre;
        $this->settings = $settings;
        $this->lastfmGenres = $lastfmGenres;
    }

    /**
     * @return Collection
     */
    public function getContent() {
        return $this->genre
            ->orderBy('popularity', 'desc')
            ->limit(50)
            ->get();
    }

    public function getGenreArtists(Genre $genre)
    {
        return null;
    }
}