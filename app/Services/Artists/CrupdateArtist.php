<?php

namespace App\Services\Artists;

use App\Artist;
use App\Genre;
use Arr;

class CrupdateArtist
{
    /**
     * @var Artist
     */
    private $artist;

    /**
     * @var Genre
     */
    private $genre;

    /**
     * @param Artist $artist
     * @param Genre $genre
     */
    public function __construct(Artist $artist, Genre $genre)
    {
        $this->artist = $artist;
        $this->genre = $genre;
    }

    /**
     * @param array $data
     * @param Artist|null $artist
     * @return Artist
     */
    public function execute($data, Artist $artist = null)
    {
        if ( ! $artist) {
            $artist = $this->artist->newInstance();
        }

        $artist->fill([
            'name' => $data['name'],
            'image_small' => $data['image_small'],
            'spotify_popularity' => $data['spotify_popularity'],
            'auto_update' => $data['auto_update'],
        ])->save();

        $genreIds = $this->genre->insertOrRetrieve(Arr::get($data, 'genres'))->pluck('id');
        $artist->genres()->sync($genreIds);

        $artist->bio()->updateOrCreate(['artist_id' => $artist->id], ['content' => $data['bio']]);
        $artist->bioImages()->delete();
        $artist->bioImages()->createMany($data['bio_images']);

        return $artist->load('albums.tracks', 'genres', 'bio', 'bioImages');
    }
}
