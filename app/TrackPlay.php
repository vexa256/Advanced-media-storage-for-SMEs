<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TrackPlay extends Model
{
    const UPDATED_AT = null;
    protected $guarded = ['id'];
    protected $casts = ['user_id' => 'integer', 'track_id' => 'integer'];
}
