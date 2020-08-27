<?php namespace App;

use App\Traits\OrdersByPopularity;
use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * App\Artist
 *
 * @property int $id
 * @property string $name
 * @property string $spotify_id
 * @property int|null $spotify_followers
 * @property int $spotify_popularity
 * @property string $image_small
 * @property string|null $image_large
 * @property int $fully_scraped
 * @property Carbon|null $updated_at
 * @property boolean $auto_update
 * @property-read Collection|Album[] $albums
 * @property-read Collection|Genre[] $genres
 * @property-read string $image_big
 * @property-read Collection|Artist[] $similar
 * @method Artist orderByPopularity(string $direction)
 * @mixin Eloquent
 */
class Artist extends Model {
    use OrdersByPopularity;

    /**
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'spotify_popularity' => 'integer',
        'fully_scraped' => 'boolean',
        'auto_update' => 'boolean'
    ];

    protected $hidden = ['fully_scraped', 'temp_id', 'pivot'];
    protected $appends = ['model_type'];
    protected $guarded = ['id', 'views'];

    public function albums()
    {
    	return $this->hasMany(Album::class);
    }

    public function topTracks()
    {
        return $this->belongsToMany(Track::class)
            ->orderByPopularity('desc')
            ->with(['album', 'artists' => function(BelongsToMany $builder) {
                return $builder->select('artists.name', 'artists.id');
            }])
            ->limit(20);
    }

    /**
     * @return BelongsToMany
     */
    public function tracks()
    {
        return $this->belongsToMany(Track::class)
            ->orderByPopularity('desc')
            ->with(['album', 'artists' => function(BelongsToMany $builder) {
                return $builder->select('artists.name', 'artists.id');
            }]);
    }

    public function similar()
    {
        return $this->belongsToMany(Artist::class, 'similar_artists', 'artist_id', 'similar_id')
            ->orderByPopularity('desc');
    }

    /**
     * @return MorphToMany
     */
    public function genres()
    {
        return $this->morphToMany(Genre::class, 'genreable')
            ->select('genres.name', 'genres.id');
    }

    /**
     * @return HasOne
     */
    public function bio()
    {
        return $this->hasOne(ArtistBio::class);
    }

    /**
     * @return HasMany
     */
    public function bioImages()
    {
        return $this->hasMany(BioImage::class);
    }

    /**
     * Get small artist image or default image.
     *
     * @param $value
     * @return string
     */
    public function getImageSmallAttribute($value)
    {
        if ($value) return $value;

        return asset('client/assets/images/default/artist_small.jpg');
    }

    /**
     * Get large artist image or default image.
     *
     * @param $value
     * @return string
     */
    public function getImageLargeAttribute($value)
    {
        if ($value) return $value;

        return asset('client/assets/images/default/artist-big.jpg');
    }

    /**
     * @return string
     */
    public function getModelTypeAttribute()
    {
        return Artist::class;
    }
}
