<?php

namespace App\Traits;

use App\Artist;
use App\User;
use Common\Settings\Settings;

trait DeterminesArtistType
{
    protected function determineArtistType()
    {
        switch (app(Settings::class)->get('player.artist_type')) {
            case 'artist':
                return Artist::class;
                break;
            case 'user':
                return User::class;
                break;
            default:
                if ($this->exists && $this->local_only) {
                    return User::class;
                } else {
                    return Artist::class;
                }
        }
    }
}