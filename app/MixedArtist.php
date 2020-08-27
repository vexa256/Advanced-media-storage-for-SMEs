<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MixedArtist extends Model
{
    protected $appends = ['model_type'];
    protected $guarded = [];

    /**
     * @return string
     */
    public function getModelTypeAttribute()
    {
        return MixedArtist::class;
    }
}