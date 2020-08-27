<?php

namespace App\Listeners;

use App\Channel;
use Common\Admin\Appearance\AppearanceSaver;
use Common\Admin\Appearance\Events\AppearanceSettingSaved;

class UpdateChannelSeoFields
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
     * Handle the event.
     *
     * @param  AppearanceSettingSaved  $event
     * @return void
     */
    public function handle(AppearanceSettingSaved $event)
    {
        if ($event->type === AppearanceSaver::ENV_SETTING && $event->key === 'app_name') {
            $this->channel
                ->where('seo_title', 'like', "%$event->previousValue%")
                ->update(['seo_title' => \DB::raw("REPLACE(seo_title, '$event->previousValue', '$event->value')")]);
        }
    }
}
