<?php namespace App\Services\Artists;

use App\Artist;
use App\Genre;
use App\Services\Providers\SaveOrUpdate;
use App\Services\Providers\Spotify\SpotifyTrackSaver;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class ArtistSaver {

    use SaveOrUpdate;

    /**
     * @param  array $data
     * @return Artist
     */
    public function save($data)
    {
        $data['mainInfo']['updated_at'] = Carbon::now();
        $this->saveOrUpdate([$data['mainInfo']], 'artists');
        $artist = app(Artist::class)->where('spotify_id', $data['mainInfo']['spotify_id'])->first();

        $this->saveAlbums($data['albums'], $artist);
        $artist->load('albums');

        if (isset($data['albums'])) {
            app(SpotifyTrackSaver::class)->save($data['albums'], $artist->albums);
        }

        if (isset($data['similar'])) {
            $this->saveSimilar($data['similar'], $artist);
        }

        if (isset($data['genres']) && ! empty($data['genres'])) {
            $this->saveGenres($data['genres'], $artist);
        }

        return $artist;
    }

    /**
     * Save and attach artist genres.
     *
     * @param array $genres
     * @param Artist $artist
     */
    public function saveGenres($genres, $artist) {

        $existing = Genre::whereIn('name', $genres)->get();
        $ids = [];

        foreach($genres as $genre) {
            $dbGenre = $existing->filter(function($item) use($genre) { return $item->name === $genre; })->first();

            //genre doesn't exist in db yet, so we need to insert it
            if ( ! $dbGenre) {
                try {
                    $dbGenre = Genre::create(['name' => $genre]);
                } catch(Exception $e) {
                    continue;
                }
            }

            $ids[] = $dbGenre->id;
        }

        //attach genres to artist
        $artist->genres()->sync($ids, false);
    }

    /**
     * @param Collection $similar
     * @param $artist
     * @return void
     */
    public function saveSimilar($similar, $artist)
    {
        $spotifyIds = $similar->pluck('spotify_id');

        // insert similar artists that don't exist in db yet
        $this->saveOrUpdate($similar, 'artists', true);

        // get ids in database for artist we just inserted
        $ids = Artist::whereIn('spotify_id', $spotifyIds)->pluck('id');

        // attach ids to given artist
        $artist->similar()->sync($ids);
    }

    /**
     * @param Collection $albums
     * @param Artist|null $artist
     * @return void
     */
    public function saveAlbums($albums, $artist = null)
    {
        if ($albums->isNotEmpty()) {
            $albums = $albums->map(function($album) use($artist) {
                if ( ! Arr::get($album, 'artist_id')) {
                    $album['artist_id'] = $artist ? $artist->id : 0;
                }

                return $album;
            });
            $this->saveOrUpdate($albums, 'albums');
        }
    }
}
