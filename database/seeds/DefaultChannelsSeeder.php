<?php

use App\Channel;
use App\Services\Channel\UpdateChannelContent;
use Common\Settings\Settings;
use Illuminate\Database\Seeder;

class DefaultChannelsSeeder extends Seeder
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
     * @return void
     */
    public function run()
    {
        if ($this->channel->count() > 0) return;

        $popularAlbums = $this->channel->create([
            'name' => 'Popular Albums',
            'slug' => 'popular-albums',
            'auto_update' => 'local:album:top',
            'layout' => 'carousel',
            'content_type' => 'album',
            'user_id' => 1,
            'seo_title' => 'Popular Albums',
            'seo_description' => 'Most popular albums from hottest artists today.',
        ]);

        $newReleases = $this->channel->create([
            'name' => 'New Releases',
            'slug' => 'new-releases',
            'auto_update' => 'local:album:new',
            'layout' => 'carousel',
            'content_type' => 'album',
            'user_id' => 1,
            'seo_title' => 'Latest Releases',
            'seo_description' => 'Browse and listen to newest releases from popular artists.',
        ]);

        $genres = $this->channel->create([
            'name' => 'Genres',
            'slug' => 'genres',
            'auto_update' => 'local:genre:top',
            'layout' => 'grid',
            'content_type' => 'genre',
            'user_id' => 1,
            'seo_title' => 'Popular Genres',
            'seo_description' => 'Browse popular genres to discover new music.',
        ]);

        $tracks = $this->channel->create([
            'name' => 'Popular Tracks',
            'slug' => 'popular-tracks',
            'auto_update' => 'local:track:top',
            'layout' => 'trackTable',
            'content_type' => 'track',
            'user_id' => 1,
            'seo_title' => 'Popular Tracks',
            'seo_description' => 'Global Top 50 chart of most popular songs.',
        ]);

        $discover = $this->channel->create([
            'name' => 'Discover',
            'slug' => 'discover',
            'hide_title' => true,
            'auto_update' => null,
            'layout' => null,
            'content_type' => 'channel',
            'user_id' => 1,
            'seo_title' => "{{SITE_NAME}} - Listen to music for free",
            'seo_description' => "Find and listen to millions of songs, albums and artists, all completely free on {{SITE_NAME}}.",
        ]);

        DB::table('channelables')->insert([
            ['channel_id' => $discover->id, 'channelable_type' => Channel::class, 'channelable_id' => $popularAlbums->id, 'order' => 1],
            ['channel_id' => $discover->id, 'channelable_type' => Channel::class, 'channelable_id' => $tracks->id, 'order' => 2],
            ['channel_id' => $discover->id, 'channelable_type' => Channel::class, 'channelable_id' => $newReleases->id, 'order' => 3],
            ['channel_id' => $discover->id, 'channelable_type' => Channel::class, 'channelable_id' => $genres->id, 'order' => 4],
        ]);

        app(Settings::class)->save([
            'homepage.type' => 'Channels',
            'homepage.value' => $discover->id,
        ]);

        collect([$newReleases, $tracks, $genres, $popularAlbums])->each(function(Channel $channel) {
            app(UpdateChannelContent::class)->execute($channel);
        });
    }
}
