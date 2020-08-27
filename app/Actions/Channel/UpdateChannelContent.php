<?php

namespace App\Services\Channel;

use App\Channel;
use App\Services\Providers\ProviderResolver;
use Cache;
use DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class UpdateChannelContent
{
    /**
     * @var ProviderResolver
     */
    private $provider;

    /**
     * @param ProviderResolver $provider
     */
    public function __construct(ProviderResolver $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @param Channel $channel
     */
    public function execute(Channel $channel)
    {
        // spotify:track:top
        list($provider, $contentType) = explode(':', $channel->auto_update, 2);

        $contentProvider = $this->provider->get($contentType, $provider);
        $content = $contentProvider->getContent();

        // bail if we could not fetch any content
        if ( ! $content || $content->isEmpty()) {
            return;
        }

        // detach all channel items from the channel
        DB::table('channelables')->where([
            'channel_id' => $channel->id,
        ])->delete();

        // group content by model type (track, album, playlist etc)
        // and attach each group via its own separate relation
        $groupedContent = $content->groupBy('model_type');
        $groupedContent->each(function(Collection $contentGroup, $modelType) use($channel) {
            $pivots = $contentGroup->mapWithKeys(function($item, $index) {
                return [$item['id'] => ['order' => $index]];
            });
            // App\Track => tracks
            $relation = strtolower(Str::plural(class_basename($modelType)));
            $channel->$relation()->syncWithoutDetaching($pivots->toArray());
        });

        Cache::forget("channels.$channel->id");
    }
}
