<?php

namespace App\Services\Tracks;

use App\Album;
use App\Artist;
use App\Genre;
use App\Notifications\ArtistUploadedMedia;
use App\Services\Providers\SaveOrUpdate;
use App\Track;
use App\User;
use Common\Tags\Tag;
use DB;
use Exception;
use Illuminate\Support\Arr;
use Notification;
use Storage;

class CrupdateTrack
{
    use SaveOrUpdate;

    /**
     * @var Track
     */
    private $track;

    /**
     * @var Tag
     */
    private $tag;

    /**
     * @var Genre
     */
    private $genre;

    /**
     * @param Track $track
     * @param Tag $tag
     * @param Genre $genre
     */
    public function __construct(Track $track, Tag $tag, Genre $genre)
    {
        $this->track = $track;
        $this->tag = $tag;
        $this->genre = $genre;
    }

    /**
     * @param array $data
     * @param Track|null $initialTrack
     * @param Album|array|null $album
     * @param bool $loadRelations
     * @return Track
     */
    public function execute($data, Track $initialTrack = null, $album = null, $loadRelations = true)
    {
        $track = $initialTrack ?
            $initialTrack :
            $this->track->newInstance();

        $inlineData = Arr::except($data, ['artists', 'tags', 'genres', 'album', 'waveData']);
        $inlineData['spotify_popularity'] = Arr::get($data, 'spotify_popularity') ?: 50;

        if ($album) {
            $inlineData['album_name'] = $album['name'];
            $inlineData['album_id'] = $album['id'];
        }

        $newArtists = collect($this->getArtists($data, $album) ?: []);

        // need to cast to int, otherwise eloquent "isDirty" will not work: https://github.com/laravel/framework/issues/8972
        $inlineData['local_only'] = (int) $newArtists->where('artist_type', User::class)->isNotEmpty();
        $track->fill($inlineData)->save();

        // make sure we're only attaching new artists to avoid too many db queries
        if ($track->relationLoaded('artists')) {
            $newArtists = $newArtists->filter(function($newArtist) use ($track) {
                $table = $newArtist['artist_type'] === Artist::class ? 'artists' : 'users';
                return !$track->artists()->where("$table.id", $newArtist['id'])->where('artist_type', $newArtist['artist_type'])->first();
            });
        }

        if ($newArtists->isNotEmpty()) {
            $pivots = $newArtists->map(function($artist, $index) use($track) {
                return [
                    'artist_id' => $artist['id'],
                    'artist_type' => $artist['artist_type'],
                    'track_id' => $track['id'],
                    'primary' => $index === 0,
                ];
            });

            DB::table('artist_track')->where('track_id', $track->id)->delete();
            DB::table('artist_track')->insert($pivots->toArray());
        }

        $tags = Arr::get($data, 'tags', []);
        $tagIds = $this->tag->insertOrRetrieve($tags)->pluck('id');
        $track->tags()->sync($tagIds);

        $genres = Arr::get($data, 'genres', []);
        $genreIds = $this->genre->insertOrRetrieve($genres)->pluck('id');
        $track->genres()->sync($genreIds);

        if ($loadRelations) {
            $track->load('artists', 'tags', 'genres');
        }

        if ( ! $initialTrack && ! $album) {
            $artist = $track->artists->first();
            if ($artist['artist_type'] === User::class) {
                $followerIds = DB::table('follows')
                    ->where('followed_id', $artist['id'])
                    ->pluck('follower_id');
                $followers = app(User::class)->whereIn('id', $followerIds)->compact()->get();

                try {
                    Notification::send($followers, new ArtistUploadedMedia($track));
                } catch (Exception $e) {
                    //
                }
            }
        }

        if ($waveData = Arr::get($data, 'waveData')) {
            $this->track->getWaveStorageDisk()->put("waves/{$track->id}.json", json_encode($waveData));
        }

        return $track;
    }

    /**
     * @param array $trackData
     * @param Album|array|null $album
     * @return array|void
     */
    private function getArtists($trackData, $album = null)
    {
        if ($trackArtists = Arr::get($trackData, 'artists')) {
            return $trackArtists;
        } else if ($album) {
            return [[
                'id' => $album['artist_id'],
                'artist_type' => $album['artist_type']
            ]];
        }
    }
}
