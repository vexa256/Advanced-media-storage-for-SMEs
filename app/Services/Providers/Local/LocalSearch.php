<?php namespace App\Services\Providers\Local;

use App\Album;
use App\Services\Search\UserSearch;
use App\Track;
use App\Artist;
use App\Services\Search\SearchInterface;
use App\Traits\DeterminesArtistType;
use App\User;
use Common\Settings\Settings;
use Illuminate\Database\Eloquent\Builder;

class LocalSearch implements SearchInterface {

    use DeterminesArtistType;

    /**
     * @param string $q
     * @param int $limit
     * @param array $modelTypes
     * @return array
     */
    public function search($q, $limit, $modelTypes) {
        $q = urldecode($q);
        $limit = $limit ?: 10;

        $results = [];

        foreach ($modelTypes as $modelType) {
            if ($modelType === Artist::class) {
                $results['artists'] = $this->findArtists($q, $limit);
            } else if ($modelType === Album::class) {
                $results['albums'] = Album::with('artist')
                    ->where('name' ,'like', $q.'%')
                    ->orWhereHas('tags', function (Builder $builder) use($q) {
                        return $builder->where('name', 'like', "$q%");
                    })
                    ->limit($limit)
                    ->get();
            } else if ($modelType === Track::class) {
                $results['tracks'] = Track::with('album', 'artists')
                    ->where('name', 'like', $q.'%')
                    ->orWhereHas('tags', function (Builder $builder) use($q) {
                        return $builder->where('name', 'like', "$q%");
                    })
                    ->limit($limit)
                    ->get();
            }
        }

        return $results;
    }

    private function findArtists($q, $limit)
    {
        if ($this->determineArtistType() === User::class) {
            return app(UserSearch::class)->search($q, $limit);
        } else {
            return Artist::where('name', 'like', $q.'%')->limit($limit)->get();
        }
    }
}
