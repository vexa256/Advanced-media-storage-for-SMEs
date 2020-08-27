<?php namespace App;

use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Str;

/**
 * App\Playlist
 *
 * @property int $id
 * @property string $name
 * @property string $image
 * @property int $public
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection|Track[] $tracks
 * @method static Builder|Playlist whereCreatedAt($value)
 * @method static Builder|Playlist whereId($value)
 * @method static Builder|Playlist whereName($value)
 * @method static Builder|Playlist wherePublic($value)
 * @method static Builder|Playlist whereUpdatedAt($value)
 * @mixin Eloquent
 * @property-read Collection|User[] $editors
 */
class Playlist extends Model {
    protected $guarded = ['id'];
    protected $hidden = ['pivot'];
    protected $appends = ['model_type'];

    protected $casts = [
        'id'     => 'integer',
        'public' => 'integer',
    ];

    public function getImageAttribute($value)
    {
        if ( ! $value || Str::contains($value, 'images/default')) return null;
        return $value;
    }

    /**
     * @return BelongsToMany
     */
    public function editors()
    {
        return $this->belongsToMany(User::class)->wherePivot('owner', 1);
    }

    /**
     * @return BelongsToMany
     */
    public function tracks()
    {
        return $this->belongsToMany(Track::class);
    }

    /**
     * @return string
     */
    public function getModelTypeAttribute()
    {
        return Playlist::class;
    }
}
