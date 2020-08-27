<?php namespace Common\Pages;

use App\User;
use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * App\Page
 *
 * @property int $id
 * @property string $body
 * @property string $slug
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @mixin Eloquent
 */
class CustomPage extends Model
{
    const DEFAULT_PAGE_TYPE = 'default';

    protected $guarded = ['id'];

    public function setSlugAttribute($value)
    {
        $this->attributes['slug'] = Str::slug($value);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
}
