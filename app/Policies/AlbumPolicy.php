<?php

namespace App\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AlbumPolicy
{
    use HandlesAuthorization;

    public function index(User $user)
    {
        return $user->hasPermission('albums.view');
    }

    public function show(User $user)
    {
        return $user->hasPermission('albums.view');
    }

    public function store(User $user)
    {
        return $user->hasPermission('albums.create');
    }

    public function update(User $user)
    {
        return $user->hasPermission('albums.update');
    }

    public function destroy(User $user, $albumIds)
    {
        if ($user->hasPermission('albums.delete')) {
            return true;
        } else {
            $dbCount = $user->albums()->whereIn('albums.id', $albumIds)->count();
            return $dbCount === count($albumIds);
        }
    }
}
