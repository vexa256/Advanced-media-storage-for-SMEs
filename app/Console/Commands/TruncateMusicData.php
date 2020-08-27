<?php

namespace App\Console\Commands;

use DB;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Storage;

class TruncateMusicData extends Command
{
    use ConfirmableTrait;

    /**
     * @var string
     */
    protected $signature = 'music:truncate {--force : Force the operation to run when in production.}';

    /**
     * @var string
     */
    protected $description = 'Truncate all music data on the site.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if ( ! $this->confirmToProceed()) {
            return;
        }

        DB::table('albums')->truncate();
        DB::table('artists')->truncate();
        DB::table('artist_bios')->truncate();
        DB::table('artist_track')->truncate();
        DB::table('bio_images')->truncate();
        DB::table('channelables')->truncate();
        DB::table('comments')->truncate();
        DB::table('file_entries')->truncate();
        DB::table('file_entry_models')->truncate();
        DB::table('follows')->truncate();
        DB::table('genreables')->truncate();
        DB::table('genres')->truncate();
        DB::table('likes')->truncate();
        DB::table('lyrics')->truncate();
        DB::table('playlists')->truncate();
        DB::table('playlist_track')->truncate();
        DB::table('playlist_user')->truncate();
        DB::table('reposts')->truncate();
        DB::table('similar_artists')->truncate();
        DB::table('taggables')->truncate();
        DB::table('tracks')->truncate();
        DB::table('track_plays')->truncate();
        DB::table('users')->truncate();
        DB::table('user_links')->truncate();
        DB::table('user_profiles')->truncate();
        DB::table('permissionables')->truncate();

        Storage::deleteDirectory('waves');
    }
}
