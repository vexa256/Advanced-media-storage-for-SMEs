<?php namespace App\Services\Providers\Local;

use App\Artist;
use App\Services\Providers\ContentProvider;
use App\Track;
use App\Traits\DeterminesArtistType;
use App\User;
use Illuminate\Database\Eloquent\Collection;

class LocalTopArtists implements ContentProvider
{
    use DeterminesArtistType;

    /**
     * @return Collection
     */
    public function getContent() {
        if ($this->determineArtistType() === Artist::class) {
            return Artist::orderBy('views', 'desc')
                ->limit(40)
                ->get();
        } else {
            return app(Track::class)
                ->with('artists')
                ->withCount('plays')
                ->orderBy('plays_count', 'desc')
                ->limit(100)
                ->get()
                ->map(function(Track $track) {
                    return $track->artists->map(function($model) {
                        $model['model_type'] = User::class;
                        return $model;
                    });
                })->flatten(1)->unique('id')->take(40);
        }
    }
}
