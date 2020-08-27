<?php namespace App\Services;

use Common\Core\Bootstrap\BaseBootstrapData;
use DB;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class AppBootstrapData extends BaseBootstrapData
{
    public function init()
    {
        parent::init();

        if (isset($this->data['user'])) {
            $this->getUserLikes();
            $this->getUserPlaylists();
            $this->loadUserFollowedUsers();
        }

        $this->data['settings']['spotify_is_setup'] = config('common.site.spotify.id') && config('common.site.spotify.secret');
        $this->data['settings']['lastfm_is_setup'] = !!config('common.site.lastfm.key');

        return $this;
    }

    /**
     * Load users that current user is following.
     */
    private function loadUserFollowedUsers()
    {
        $this->data['user'] = $this->data['user']->load(['followedUsers' => function(BelongsToMany $q) {
            return $q->select('users.id', 'users.avatar');
        }]);
    }

    /**
     * Get ids of all tracks in current user's library.
     */
    private function getUserLikes()
    {
        $this->data['likes'] = DB::table('likes')
            ->where('user_id', $this->data['user']['id'])
            ->get(['likeable_id', 'likeable_type'])
            ->groupBy('likeable_type')
            ->map(function(Collection $likeableGroup) {
                return $likeableGroup->mapWithKeys(function($likeable) {
                    return [$likeable->likeable_id => true];
                });
            });
    }

    /**
     * Get ids of all tracks in current user's library.
     */
    private function getUserPlaylists()
    {
        $this->data['playlists'] = $this->data['user']
            ->playlists()
            ->with(['editors' => function(BelongsToMany $q) {
                return $q->compact();
            }])
            ->select('playlists.id', 'playlists.name')
            ->get()
            ->toArray();
    }
}
