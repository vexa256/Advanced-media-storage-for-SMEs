<?php namespace App;

use App\Services\Artists\NormalizesArtist;
use App\Traits\DeterminesArtistType;
use App\Traits\OrdersByPopularity;
use Common\Comments\Comment;
use Common\Tags\Tag;
use Eloquent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Filesystem\FilesystemAdapter;
use Storage;

/**
 * App\Track
 *
 * @property int $id
 * @property string $name
 * @property string $album_name
 * @property int $number
 * @property int $duration
 * @property string|null $youtube_id
 * @property int $spotify_popularity
 * @property int $album_id
 * @property string|null $temp_id
 * @property boolean $auto_update
 * @property string|null $url
 * @property-read Album $album
 * @property-read Collection|Playlist[] $playlists
 * @property-read Collection|User[] $users
 * @method Track orderByPopularity(string $direction)
 * @property boolean local_only
 * @mixin Eloquent
 */
class Track extends Model {
    use OrdersByPopularity, NormalizesArtist, DeterminesArtistType;

    /**
     * @var array
     */
    protected $guarded = [
        'id',
        'formatted_duration',
        'plays',
        'lyric'
    ];

    /**
     * @var array
     */
    protected $hidden = [
        'fully_scraped',
        'temp_id',
        'pivot',
        'artists_legacy'
    ];

    /**
     * @var array
     */
    protected $casts = [
        'id'       => 'integer',
        'album_id' => 'integer',
        'number'   => 'integer',
        'spotify_popularity' => 'integer',
        'duration' => 'integer',
        'auto_update' => 'boolean',
        'position' => 'integer',
        'local_only' => 'boolean',
    ];

    protected $appends = ['model_type', 'created_at_relative'];

    /**
     * @return BelongsToMany
     */
    public function likes()
    {
        return $this->morphToMany(User::class, 'likeable', 'likes')
            ->withTimestamps();
    }

    /**
     * @return MorphMany|Comment
     */
    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable')
            ->orderBy('created_at', 'desc');
    }

    /**
     * @return MorphMany
     */
    public function reposts()
    {
        return $this->morphMany(Repost::class, 'repostable');
    }

    /**
     * @return BelongsTo
     */
    public function album()
    {
        return $this->belongsTo(Album::class);
    }

    /**
     * @return BelongsToMany
     */
    public function artists()
    {
        $artistType = $this->determineArtistType();
        $selects = $artistType === User::class ?
            ['users.id', 'first_name', 'last_name', 'email', 'username', 'avatar'] :
            ['artists.id', 'artists.name', 'artists.image_small'];
        return $this->morphedByMany($artistType, 'artist', 'artist_track')
            ->select($selects);
    }

    public function plays()
    {
        return $this->hasMany(TrackPlay::class);
    }

    public function setRelation($relation, $value)
    {
        if ($relation === 'artists') {
            $value = $value->map(function($model) {
               return $this->normalizeArtist($model);
            });
        }
        parent::setRelation($relation, $value);
    }

    /**
     * @return MorphToMany
     */
    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable')
            ->select('tags.name', 'tags.display_name', 'tags.id');
    }

    /**
     * @return MorphToMany
     */
    public function genres()
    {
        return $this->morphToMany(Genre::class, 'genreable')
            ->select('genres.name', 'genres.display_name', 'genres.id');
    }

    /**
     * @return BelongsToMany
     */
    public function playlists()
    {
        return $this->belongsToMany('App\Playlist')->withPivot('position');
    }

    /**
     * @return HasOne
     */
    public function lyric()
    {
        return $this->hasOne('App\Lyric');
    }

    /**
     * @return string
     */
    public function getCreatedAtRelativeAttribute()
    {
        return $this->created_at ? $this->created_at->diffForHumans() : null;
    }

    /**
     * @return string
     */
    public function getModelTypeAttribute()
    {
        return Track::class;
    }

    /**
     * @return FilesystemAdapter
     */
    public function getWaveStorageDisk()
    {
        return Storage::disk(config('common.site.wave_storage_disk'));
    }
}
