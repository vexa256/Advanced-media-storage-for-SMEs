<?php

namespace App;

use Eloquent;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * App\BioImage
 *
 * @property int $id
 * @property int $artist_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @mixin Eloquent
 */
class BioImage extends Model
{
    protected $guarded = ['id'];

     protected $casts = [
         'id' => 'integer',
         'artist_id' => 'integer',
     ];
}
