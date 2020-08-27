<?php

namespace App\Policies;

use Common\Auth\BaseUser;
use App\Channel;
use Common\Core\Policies\BasePolicy;

class ChannelPolicy extends BasePolicy
{
    public function index(BaseUser $user, $userId = null)
    {
        return $user->hasPermission('channels.view') || $user->id === (int) $userId;
    }

    public function show(BaseUser $user, Channel $channel)
    {
        return $user->hasPermission('channels.view') || $channel->user_id === $user->id;
    }

    public function store(BaseUser $user)
    {
        return $user->hasPermission('channels.create');
    }

    public function update(BaseUser $user, Channel $channel)
    {
        return $user->hasPermission('channels.update') || $channel->user_id === $user->id;
    }

    public function destroy(BaseUser $user, $channelIds)
    {
        if ($user->hasPermission('channels.delete')) {
            return true;
        } else {
            $dbCount = app(Channel::class)
                ->whereIn('id', $channelIds)
                ->where('user_id', $user->id)
                ->count();
            return $dbCount === count($channelIds);
        }
    }
}
