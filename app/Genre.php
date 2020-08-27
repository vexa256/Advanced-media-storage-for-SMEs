<?php namespace App;

use Carbon\Carbon;
use Common\Tags\Tag;
use Eloquent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Arr;
use Str;

/**
 * App\Genre
 *
 * @property int $id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection|Artist[] $artists
 * @mixin Eloquent
 */
class Genre extends Model
{
    protected $guarded = ['id'];
    protected $hidden = ['pivot'];
    protected $appends = ['model_type'];

    /**
     * @return BelongsToMany
     */
    public function artists()
    {
        return $this->morphedByMany(Artist::class, 'genreable')
            ->orderByPopularity('desc')
            ->orderBy('spotify_followers', 'desc');
    }

    /**
     * @param \Illuminate\Support\Collection|array $genres
     * @return Collection|Tag[]
     */
    public function insertOrRetrieve($genres)
    {
        if ( ! $genres instanceof Collection) {
            $genres = collect($genres);
        }

        $genres = $genres->filter()->map(function($genre) {
            if (is_string($genre)) {
                $genre = ['name' => $genre];
            }
            if ( ! Arr::get($genre, 'display_name')) {
                $genre['display_name'] = $genre['name'];
            }
            if ( ! Arr::get($genre, 'created_at')) {
                $genre['created_at'] = Carbon::now();
            }
            return $genre;
        });

        $existing = $this->whereIn('name', $genres->pluck('name'))->get();

        $new = $genres->filter(function($genre) use($existing) {
            return !$existing->first(function($existingGenre) use($genre) {
                return slugify($existingGenre['name']) === slugify($genre['name']);
            });
        });

        if ($new->isNotEmpty()) {
            $this->insert($new->toArray());
            return $this->whereIn('name', $genres->pluck('name'))->get();
        } else {
            return $existing;
        }
    }

    /**
     * @param string|null $value
     * @return string
     */
    public function getImageAttribute($value)
    {
        // default genre image
        if ( ! $value) {
            $value = "client/assets/images/default/artist_small.jpg";
        }

        // make sure image url is absolute
        if ( ! Str::contains($value, '//')) {
            $value = url($value);
        }

        return $value;
    }

    /**
     * @return string
     */
    public function getModelTypeAttribute()
    {
        return Genre::class;
    }
}
