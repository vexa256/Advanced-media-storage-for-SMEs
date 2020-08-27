<?php

namespace App\Http\Controllers;

use App\Actions\Channel\LoadChannelContent;
use App\Channel;
use Common\Core\BaseController;
use Common\Settings\Settings;

class LandingPageChannelController extends BaseController
{
    /**
     * @var Channel
     */
    private $channel;

    /**
     * @var Settings
     */
    private $settings;

    /**
     * @param Channel $channel
     * @param Settings $settings
     */
    public function __construct(Channel $channel, Settings $settings)
    {
        $this->channel = $channel;
        $this->settings = $settings;
    }

    public function index()
    {
        $channelIds = $this->settings->getJson('homepage.appearance')['channelIds'];
        $channels = $this->channel->whereIn('id', $channelIds)->get();

        $channels->transform(function(Channel $channel) {
            $channelContent = app(LoadChannelContent::class)->execute($channel, 10);
            $channel->setRelation('content', $channelContent);
            return $channel;
        });

        $config = [
            'prerender.view' => 'home.show',
            'prerender.config' => 'home.show',
        ];

        return $this->success(['channels' => $channels], 200, $config);
    }
}
