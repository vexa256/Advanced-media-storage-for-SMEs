<?php

namespace App\Policies;

use App\Track;
use App\User;
use Common\Core\Policies\BasePolicy;
use Common\Settings\Settings;

class TrackPolicy extends BasePolicy
{
    public function index(User $user)
    {
        return $user->hasPermission('tracks.view');
    }

    public function show(User $user)
    {
        return $user->hasPermission('tracks.view');
    }

    public function store(User $user)
    {
        // user can't create tracks at all
        if ( ! $user->hasPermission('tracks.create')) {
            return false;
        }

        // user is admin, can ignore count restriction
        if ($user->hasPermission('admin')) {
            return true;
        }

        // user does not have any restriction on track minutes
        $maxMinutes = $user->getRestrictionValue('tracks.create', 'minutesx');
        if (is_null($maxMinutes)) {
            return true;
        }

        $usedMS = $user->uploadedTracks()->sum('duration');
        $usedMinutes = floor($usedMS / 60000);

        // check if user did not go over their max quota
        if ($usedMinutes >= $maxMinutes) {
            $this->deny(__('policies.minutes_exceeded'), ['showUpgradeButton' => true]);
        }

        return true;
    }

    public function update(User $user, Track $track)
    {
        if ($user->hasPermission('tracks.update')) {
            return true;
        }

        $owner = $track->artists()->wherePivot('owner', true)->find($user->id);
        if ($owner && $owner->id === $user->id) {
            return true;
        }

        return false;
    }

    public function destroy(User $user, $trackIds)
    {
        if ($user->hasPermission('tracks.delete')) {
            return true;
        } else {
            $dbCount = $user->uploadedTracks()->whereIn('tracks.id', $trackIds)->count();
            return $dbCount === count($trackIds);
        }
    }

    public function download(User $user, Track $track)
    {
        return app(Settings::class)->get('player.enable_download') && $user->hasPermission('tracks.download');
    }
}
