<?php

namespace App\Actions\Channel;

use App\Services\Channel\UpdateChannelContent;
use Auth;
use App\Channel;
use DB;
use Illuminate\Support\Arr;

class CrupdateChannel
{
    /**
     * @var Channel
     */
    private $channel;

    /**
     * @param Channel $channel
     */
    public function __construct(Channel $channel)
    {
        $this->channel = $channel;
    }

    /**
     * @param array $data
     * @param Channel $initialChannel
     * @return Channel
     */
    public function execute($data, $initialChannel = null)
    {
        if ( ! $initialChannel) {
            $channel = $this->channel->newInstance([
                 'user_id' => Auth::id(),
            ]);
        } else {
            $initialAutoUpdate =$initialChannel->auto_update;
            $channel = $initialChannel;
        }

        $attributes = [
            'name' => $data['name'],
            'auto_update' => $data['auto_update'],
            'hide_title' => $data['hide_title'],
            'content_type' => $data['content_type'],
            'layout' => $data['layout'],
            'slug' => $data['slug'],
            'seo_title' => $data['seo_title'],
            'seo_description' => $data['seo_description'],
        ];

        $channel->fill($attributes)->save();

        if ( ! $initialChannel && $channelContent = Arr::get($data, 'content')) {
            $pivots = collect($channelContent)
                ->map(function($item, $i) use($channel) {
                    return [
                        'channel_id' => $channel->id,
                        'channelable_id' => $item['id'],
                        'channelable_type' => $item['model_type'],
                        'order' => $i
                    ];
                })
                ->filter(function($item) use($channel) {
                    // channels should not be attached to themselves
                    return $item['channelable_type'] !== Channel::class || $item['channel_id'] !== $channel->id;
                });
            DB::table('channelables')->insert($pivots->toArray());
        }

        if (isset($initialAutoUpdate) && $initialAutoUpdate !== $channel->auto_update) {
            app(UpdateChannelContent::class)
                ->execute($channel);
        }

        return $channel;
    }
}
