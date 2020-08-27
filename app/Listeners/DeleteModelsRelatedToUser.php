<?php

namespace App\Listeners;

use App\Services\Albums\DeleteAlbums;
use App\Services\Playlists\DeletePlaylists;
use App\TrackPlay;
use App\UserLink;
use App\UserProfile;
use Common\Auth\Events\UsersDeleted;
use Common\Comments\Comment;
use DB;

class DeleteModelsRelatedToUser
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  UsersDeleted  $event
     * @return void
     */
    public function handle(UsersDeleted $event)
    {
        $userIds = $event->users->pluck('id');

        // delete albums
        $albumIds = $event->users->load('albums')->pluck('albums.*.id')->flatten(1);
        app(DeleteAlbums::class)->execute($albumIds);

        // detach user from comments
        Comment::whereIn('user_id', $userIds)->update(['user_id' => 0]);

        // clean up follows table
        DB::table('follows')->whereIn('follower_id', $userIds)->orWhereIn('followed_id', $userIds)->delete();

        // likes
        DB::table('likes')->whereIn('user_id', $userIds)->delete();

        // playlists
        $playlists = $event->users->load('playlists')->pluck('playlists')->flatten(1);
        app(DeletePlaylists::class)->execute($playlists);
        DB::table('playlist_user')->whereIn('user_id', $userIds)->delete();

        // reposts
        DB::table('reposts')->whereIn('user_id', $userIds)->delete();

        // detach user from track plays
        TrackPlay::whereIn('user_id', $userIds)->update(['user_id' => 0]);

        // profiles
        UserProfile::whereIn('user_id', $userIds)->delete();
        UserLink::whereIn('user_id', $userIds)->delete();
    }
}
