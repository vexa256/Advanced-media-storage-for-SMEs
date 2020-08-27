<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    protected $guarded = ['id'];

    public function getHeaderColorsAttribute($value)
    {
        if ($value) {
            return json_decode($value, true);
        } else {
            return [];
        }
    }

    public function setHeaderColorsAttribute($value)
    {
        if ( ! is_string($value)) {
            $value = json_encode($value);
        }
        $this->attributes['header_colors'] = $value;
    }
}
