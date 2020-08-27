<?php

namespace App\Services\Albums;

use App\Album;
use App\Genre;
use App\Notifications\ArtistUploadedMedia;
use App\Services\Tracks\CrupdateTrack;
use App\Track;
use App\User;
use Common\Tags\Tag;
use DB;
use Illuminate\Support\Arr;
use Notification;

class CrupdateAlbum
{
    /**
     * @var Album
     */
    private $album;

    /**
     * @var Tag
     */
    private $tag;

    /**
     * @var CrupdateTrack
     */
    private $createTrack;

    /**
     * @var Track
     */
    private $track;

    /**
     * @var Genre
     */
    private $genre;

    /**
     * @param Album $album
     * @param CrupdateTrack $createTrack
     * @param Tag $tag
     * @param Track $track
     * @param Genre $genre
     */
    public function __construct(Album $album, CrupdateTrack $createTrack, Tag $tag, Track $track, Genre $genre)
    {
        $this->album = $album;
        $this->tag = $tag;
        $this->createTrack = $createTrack;
        $this->track = $track;
        $this->genre = $genre;
    }

    /**
     * @param array $data
     * @param Album|null $initialAlbum
     * @return Album
     */
    public function execute($data, Album $initialAlbum = null)
    {
        $album = $initialAlbum ? $initialAlbum : $this->album->newInstance();

        $inlineData = Arr::except($data, ['tracks', 'tags', 'genres']);
        $inlineData['spotify_popularity'] = Arr::get($data, 'spotify_popularity') ?: 50;
        $inlineData['local_only'] = Arr::get($inlineData, 'artist_type') === User::class;

        $album->fill($inlineData)->save();

        $tags = Arr::get($data, 'tags', []);
        $tagIds = $this->tag->insertOrRetrieve($tags)->pluck('id');
        $album->tags()->sync($tagIds);

        $genres = Arr::get($data, 'genres', []);
        $genreIds = $this->genre->insertOrRetrieve($genres)->pluck('id');
        $album->genres()->sync($genreIds);

        $this->saveTracks($data, $album);

        $album->load('tracks', 'artist', 'genres', 'tags');
        $album->tracks->load('artists');

        if ( ! $initialAlbum) {
            $artist = $album->artist;
            if ($artist['artist_type'] === User::class) {
                $followerIds = DB::table('follows')
                    ->where('followed_id', $artist['id'])
                    ->pluck('follower_id');
                $followers = app(User::class)->whereIn('id', $followerIds)->compact()->get();
                Notification::send($followers, new ArtistUploadedMedia($album));
            }
        }

        return $album;
    }

    private function saveTracks($albumData, Album $album)
    {
        $tracks = collect(Arr::get($albumData, 'tracks', []));
        if ($tracks->isEmpty()) return;

        $trackIds = $tracks->pluck('id')->filter();
        $savedTracks = collect([]);
        if ($trackIds->isNotEmpty()) {
            $savedTracks = $album->tracks()->whereIn('id', $trackIds)->get();
            $savedTracks->load('artists');
        }

        $tracks->each(function($trackData) use($album, $savedTracks) {
            $trackModel = $trackData['id'] ? $savedTracks->find($trackData['id']) : null;
            $this->createTrack->execute(Arr::except($trackData, 'album'), $trackModel, $album, false);
        });
    }
}
