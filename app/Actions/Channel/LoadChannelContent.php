<?php

namespace App\Actions\Channel;

use App\Album;
use App\Artist;
use App\Channel;
use App\Genre;
use App\MixedArtist;
use App\Playlist;
use App\Services\Artists\NormalizesArtist;
use App\Track;
use App\Traits\DeterminesArtistType;
use App\User;
use Cache;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Collection;

class LoadChannelContent
{
    use NormalizesArtist, DeterminesArtistType;

    /**
     * @param Channel $channel
     * @param int $limit
     * @return Collection
     */
    public function execute(Channel $channel, $limit = 50)
    {
        $content = Cache::remember("channels.$channel->id", 1440, function() use($channel, $limit) {
            return $this->load($channel);
        });

        return $content->slice(0, $limit);
    }

    /**
     * @param Channel $channel
     * @return Collection
     */
    private function load(Channel $channel)
    {
        $query = DB::table('channelables')
            ->where('channel_id', $channel->id)
            ->orderBy('order', 'asc');
        if ($channel->content_type && $channel->content_type !== 'mixed') {
            if ($channel->content_type === 'artist') {
                $channelableType = $this->determineArtistType();
            } else {
                $channelableType = 'App\\' . ucfirst($channel->content_type);
            }
            $query->where('channelable_type', $channelableType);
        }
        $channelables = $query->limit(50)->get();

        return $channelables->groupBy('channelable_type')->map(function(Collection $channelableGroup, $channelableModel) {
            switch ($channelableModel) {
                case Track::class:
                    $tracks = app($channelableModel)
                        ->with('album.artist', 'artists', 'genres')
                        ->withCount('plays')
                        ->whereIn('id', $channelableGroup->pluck('channelable_id'))
                        ->get();
                    $tracks->load('artists');
                    return $tracks;
                case Album::class:
                    return app($channelableModel)
                        ->with('artist')
                        ->whereIn('id', $channelableGroup->pluck('channelable_id'))
                        ->get();
                case Artist::class:
                    return app($channelableModel)
                        ->select(['name', 'id', 'image_small'])
                        ->whereIn('id', $channelableGroup->pluck('channelable_id'))
                        ->get()
                        ->map(function(Artist $user) {
                            return $this->normalizeArtist($user);
                        });
                case User::class:
                    return app($channelableModel)
                        ->select(['id', 'email', 'first_name', 'last_name', 'username', 'avatar'])
                        ->whereIn('id', $channelableGroup->pluck('channelable_id'))
                        ->get()
                        ->map(function(User $user) {
                            return $this->normalizeArtist($user);
                        });
                case Genre::class:
                    return app($channelableModel)
                        ->whereIn('id', $channelableGroup->pluck('channelable_id'))
                        ->get();
                case Playlist::class:
                    return app($channelableModel)
                        ->with('editors')
                        ->whereIn('id', $channelableGroup->pluck('channelable_id'))
                        ->get();
                case Channel::class:
                    $channels = app($channelableModel)
                        ->whereIn('id', $channelableGroup->pluck('channelable_id'))
                        ->get();
                    $channels->transform(function(Channel $channel) {
                        // only load 10 items per nested channel
                        $channel->setRelation('content', $this->execute($channel, 10));
                        return $channel;
                    });
                    return $channels;
            }
        })
        ->flatten(1)
        ->map(function($contentItem) use($channelables) {
            $channelable = $channelables->first(function($channelable) use($contentItem) {
                $modelType = $contentItem['model_type'] === MixedArtist::class ? $contentItem['artist_type'] : $contentItem['model_type'];
                return (int) $channelable->channelable_id === $contentItem['id'] && $channelable->channelable_type === $modelType;
            });
            $contentItem['channelable_id'] = $channelable->id;
            $contentItem['channelable_order'] = $channelable->order;
            return $contentItem;
        })
        ->sortBy('channelable_order')
        ->values();
    }
}
