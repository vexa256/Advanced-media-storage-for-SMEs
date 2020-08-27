<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserLink extends Model
{
    protected $guarded = ['id'];

    public function getUrlAttribute($value)
    {
        return parse_url($value, PHP_URL_SCHEME) === null ? "https://$value" : $value;
    }
}
