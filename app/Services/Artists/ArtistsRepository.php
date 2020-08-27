<?php namespace App\Services\Artists;

use App\Album;
use App\Artist;
use App\Genre;
use App\Services\Providers\ProviderResolver;
use App\Track;
use Carbon\Carbon;
use Common\Settings\Settings;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Arr;

class ArtistsRepository
{
    /**
     * @var Artist
     */
    private $artist;

    /**
     * @var Settings
     */
    private $settings;

    /**
     * @var ProviderResolver
     */
    private $resolver;

    /**
     * @var ArtistSaver
     */
    private $saver;

    /**
     * @var ArtistAlbumsPaginator
     */
    private $albumsPaginator;

    /**
     * @var ArtistBio
     */
    private $bio;

    /**
     * @var Album
     */
    private $album;

    /**
     * @var Track
     */
    private $track;

    /**
     * @var Genre
     */
    private $genre;

    /**
     * @param Artist $artist
     * @param Album $album
     * @param Track $track
     * @param Genre $genre
     * @param Settings $settings
     * @param ProviderResolver $resolver
     * @param ArtistSaver $saver
     * @param ArtistAlbumsPaginator $albumsPaginator
     * @param ArtistBio $bio
     */
    public function __construct(
        Artist $artist,
        Album $album,
        Track $track,
        Genre $genre,
        Settings $settings,
        ProviderResolver $resolver,
        ArtistSaver $saver,
        ArtistAlbumsPaginator $albumsPaginator,
        ArtistBio $bio
    )
    {
        $this->bio = $bio;
        $this->saver = $saver;
        $this->artist = $artist;
        $this->settings = $settings;
        $this->resolver = $resolver;
        $this->albumsPaginator = $albumsPaginator;
        $this->album = $album;
        $this->track = $track;
        $this->genre = $genre;
    }

    /**
     * @param integer $id
     * @param array $params
     * @return array
     */
    public function getById($id, $params = [])
    {
        $artist = $this->artist->findOrFail($id);
        return $this->load($artist, $params);
    }

    /**
     * Load specified artist.
     *
     * @param Artist $artist
     * @param array $params
     * @return array|Artist
     */
    private function load(Artist $artist, $params = [])
    {
        // return only simplified version of specified artist if requested.
        if (Arr::get($params, 'simplified')) {
            return ['artist' => $artist->load(['albums' => function(HasMany $query) {
                $query->with('tracks.artists', 'artist', 'tags', 'genres')->orderBy('updated_at', 'desc');
            }, 'albums.artist', 'albums.tags', 'albums.genres', 'genres', 'bio', 'bioImages'])];
        }

        $load = array_filter(explode(',', Arr::get($params, 'with', '')));

        if ($this->needsUpdating($artist)) {
            $newArtist = $this->fetchAndStoreArtistFromExternal($artist);
            if ($newArtist) $artist = $newArtist;
        }

        $artist = $artist->load($load);

        $albums = $this->albumsPaginator->paginate($artist->id);

        $response = ['artist' => $artist, 'albums' => $albums];

        if (Arr::get($params, 'top_tracks')) {
            $artist->load('topTracks');
        }

        return $response;
    }

    /**
     * @param Artist $artist
     * @return Artist|void
     */
    public function fetchAndStoreArtistFromExternal(Artist $artist)
    {
        $spotifyArtist = $this->resolver->get('artist')->getArtist($artist);

        if ($spotifyArtist) {
            $artist = $this->saver->save($spotifyArtist);
            $artist = $this->bio->get($artist);
            unset($artist['albums']);
            return $artist;
        }
    }

    /**
     * Delete specified artists from database.
     *
     * @param array $ids
     */
    public function delete($ids)
    {
        $albumIds = $this->album->whereIn('artist_id', $ids)->pluck('id');

        $this->artist->whereIn('id', $ids)->delete();
        $this->album->whereIn('id', $albumIds)->delete();
        $this->track->whereIn('album_id', $albumIds)->delete();
    }

    /**
     * Check if specified artist needs to be updated via external site.
     *
     * @param Artist $artist
     * @return bool
     */
    public function needsUpdating(Artist $artist)
    {
        if ($this->settings->get('artist_provider', 'local') === 'local') return false;
        if ( ! $artist->auto_update) return false;
        if ( ! $artist->fully_scraped) return true;

        $updateInterval = (int) $this->settings->get('automation.artist_interval', 7);

        // 0 means that artist should never be updated from 3rd party sites
        if ($updateInterval === 0) return false;

        return !$artist->updated_at || $artist->updated_at->addDays($updateInterval) <= Carbon::now();
    }
}
