<?php namespace App\Services\Search;

use App\User;
use Illuminate\Support\Collection;

class UserSearch {


    /**
     * @param string  $q
     * @param int     $limit
     *
     * @return Collection
     */
    public function search($q, $limit = 10)
    {
        $users = User::where('email', 'like', $q.'%')
                     ->orWhere('username', 'like', $q.'%')
                     ->select('email', 'username', 'first_name', 'last_name', 'id', 'avatar')
                     ->limit($limit)
                     ->get();

        foreach($users as $user) {
            $user->followersCount;
        }

        return $users;
    }
}
