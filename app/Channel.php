<?php

namespace App;

use App\Actions\Channel\LoadChannelContent;
use App\Traits\DeterminesArtistType;
use Eloquent;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * App\Channel
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $slug
 * @property string $auto_update
 * @property string content_type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @mixin Eloquent
 */
class Channel extends Model
{
    use DeterminesArtistType;

    protected $guarded = ['id'];
    protected $appends = ['model_type'];

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'hide_title' => 'boolean',
    ];

    /**
     * @return MorphToMany
     */
    public function tracks()
    {
        return $this->morphedByMany(Track::class, 'channelable');
    }

    /**
     * @return MorphToMany
     */
    public function albums()
    {
        return $this->morphedByMany(Album::class, 'channelable');
    }

    /**
     * @return MorphToMany
     */
    public function artists()
    {
        $artistType = $this->determineArtistType();
        return $this->morphedByMany($artistType, 'channelable');
    }

    /**
     * @return MorphToMany
     */
    public function users()
    {
        return $this->morphedByMany(User::class, 'channelable');
    }

    /**
     * @return MorphToMany
     */
    public function genres()
    {
        return $this->morphedByMany(Genre::class, 'channelable');
    }

    /**
     * @return MorphToMany
     */
    public function playlists()
    {
        return $this->morphedByMany(Playlist::class, 'channelable');
    }

    /**
     * @return MorphToMany
     */
    public function channels()
    {
        return $this->morphedByMany(Channel::class, 'channelable');
    }

    /**
     * @return string
     */
    public function getModelTypeAttribute()
    {
        return Channel::class;
    }

    /**
     * @return $this
     */
    public function loadContent()
    {
        $channelContent = app(LoadChannelContent::class)->execute($this);
        $this->setRelation('content', $channelContent);
        return $this;
    }
}
