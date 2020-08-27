<?php

namespace App\Services\Albums;

use App\Album;
use App\Services\Artists\ArtistSaver;
use App\Services\Providers\ProviderResolver;
use App\Services\Providers\Spotify\SpotifyTrackSaver;
use Arr;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShowAlbum
{
    /**
     * @var ProviderResolver
     */
    private $resolver;

    /**
     * @var ArtistSaver
     */
    private $saver;

    /**
     * @param ProviderResolver $resolver
     * @param ArtistSaver $saver
     */
    public function __construct(ProviderResolver $resolver, ArtistSaver $saver)
    {
        $this->resolver = $resolver;
        $this->saver = $saver;
    }

    /**
     * @param Album $album
     * @param array $params
     * @return Album
     */
    public function execute(Album $album, $params)
    {
        $simplified = filter_var(Arr::get($params, 'simplified'), FILTER_VALIDATE_BOOLEAN);
        if ($album->needsUpdating() && !$simplified) {
            $this->updateAlbum($album);
        }

        $album->load(['artist', 'tracks' => function(HasMany $builder) {
            return $builder->with('artists')->withCount('plays');
        }, 'tags', 'genres']);

        // need to load tracks here so morphed relation works properly
        $album->tracks->load('artists');
        return $album;
    }

    /**
     * @param Album $album
     */
    private function updateAlbum(Album $album)
    {
        $spotifyAlbum = $this->resolver->get('album')->getAlbum($album);
        if ( ! $spotifyAlbum) return;

        // if album artist is not in database yet, fetch and save it
        // fetching artist will get all his albums as well
        if ($spotifyAlbum['artist'] && ! $album->artist) {
            $artist = $this->resolver->get('artist')->getArtist($spotifyAlbum['artist']['spotify_id']);
            if ($artist) $this->saver->save($artist);
        } else {
            $this->saver->saveAlbums(collect([$spotifyAlbum]), $album->artist);
            app(SpotifyTrackSaver::class)->save(collect([$spotifyAlbum]), collect([$album]));
        }
    }
}
