<?php namespace App\Services\Search;

use App\Album;
use App\Artist;
use App\Services\Providers\SaveOrUpdate;
use App\Track;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class SearchSaver {

    use SaveOrUpdate;

    /**
     * @param array $data
     * @return array
     */
    public function save($data)
    {
        $merged = $this->prepareData($data);
        return $this->saveAndGetData($merged, $data);
    }

    /**
     * @param array $data
     * @return array
     */
    private function prepareData($data)
    {
        $data['albums'] = $data['albums']->concat($data['tracks']->pluck('album'));

        // pull artists from albums array
        $data['artists'] = $data['artists']->concat($data['albums']->pluck('artist'));

        // pull albums and artists from tracks array
        $data['artists'] = $data['artists']->concat($data['tracks']->pluck('artists')->flatten(1));

        return $data;
    }

    /**
     * @param array $merged
     * @param array $original
     * @return array
     */
    private function saveAndGetData($merged, $original)
    {
        $savedArtists = $this->saveAndGetArtists($merged['artists']);
        $savedAlbums  = $this->saveAndGetAlbums($merged['albums'], $savedArtists);
        $savedTracks  = $this->saveAndGetTracks($merged['tracks'], $savedAlbums, $savedArtists);

        $savedArtists = $savedArtists->filter(function($savedArtist) use($original) {
            return $original['artists']->contains('spotify_id', $savedArtist['spotify_id']);
        })->values();

        $savedAlbums = $savedAlbums->filter(function($savedAlbum) use($original) {
            return $original['albums']->contains('spotify_id', $savedAlbum['spotify_id']);
        })->values();

        $savedTracks = $savedTracks->filter(function($savedTrack) use($original) {
            return $original['tracks']->contains('spotify_id', $savedTrack['spotify_id']);
        })->values();

        return ['albums' => $savedAlbums, 'tracks' => $savedTracks, 'artists' => $savedArtists];
    }

    /**
     * @param Collection $normalizedTracks
     * @param Collection $savedAlbums
     * @param Collection $savedArtists
     * @return Collection
     */
    private function saveAndGetTracks($normalizedTracks, $savedAlbums, $savedArtists)
    {
        $normalizedTracks = $normalizedTracks->map(function($normalizedTrack) use($savedAlbums) {
            try {
                $albumId = $savedAlbums
                    ->where('spotify_id', $normalizedTrack['album']['spotify_id'])
                    ->first()
                    ->id;
            } catch (\Exception $e) {
                dd($normalizedTrack, $savedAlbums->toArray());
            }
            $normalizedTrack['album_id'] = $albumId;
            return $normalizedTrack;
        });

        $this->saveOrUpdate($normalizedTracks, 'tracks');

        $savedTracks = Track::with('album', 'artists')
            ->whereIn('spotify_id', $normalizedTracks->pluck('spotify_id'))
            ->orderByPopularity('desc')
            ->get();

        $pivots = $normalizedTracks->map(function($normalizedTrack) use($savedTracks, $savedArtists) {
            $trackId = $savedTracks->where('spotify_id', $normalizedTrack['spotify_id'])->first()->id;
            return $normalizedTrack['artists']->map(function($normalizedArtist) use($trackId, $savedArtists) {
                $artistId = $savedArtists->where('spotify_id', $normalizedArtist['spotify_id'])->first()->id;
                return [
                    'track_id' => $trackId,
                    'artist_id' => $artistId,
                ];
            });
        })->flatten(1);

        $this->saveOrUpdate($pivots, 'artist_track');
        $savedTracks->load('artists');
        return $savedTracks;
    }

    /**
     * @param Collection $artists
     * @return Collection
     */
    private function saveAndGetArtists($artists)
    {
        $uniqueArtists = $artists->sortByDesc('spotify_popularity')->unique('spotify_id')->map(function($artist) {
            return [
                'name' => $artist['name'],
                'spotify_id' => $artist['spotify_id'],
                'spotify_popularity' => Arr::get($artist, 'spotify_popularity') ?: null,
                'spotify_followers' => Arr::get($artist, 'spotify_followers') ?: null,
                'image_small' => Arr::get($artist, 'image_small') ?: null,
                'image_large' => Arr::get($artist, 'image_large') ?: null,
            ];
        });

        $this->saveOrUpdate($uniqueArtists, 'artists');
        return Artist::whereIn('spotify_id', $uniqueArtists->pluck('spotify_id'))
            ->orderByPopularity('desc')
            ->get();
    }

    /**
     * @param Collection $normalizedAlbums
     * @param Collection $savedArtists
     * @return Collection
     */
    private function saveAndGetAlbums($normalizedAlbums, $savedArtists)
    {
        $normalizedAlbums = $normalizedAlbums->unique('spotify_id')->map(function($normalizedAlbum) use($savedArtists) {
            $normalizedAlbum['artist_id'] = $savedArtists->where('spotify_id', $normalizedAlbum['artist']['spotify_id'])->first()->id;
            return $normalizedAlbum;
        });

        $this->saveOrUpdate($normalizedAlbums, 'albums');

        return Album::with('artist')
            ->whereIn('spotify_id', $normalizedAlbums->pluck('spotify_id'))
            ->orderBy('spotify_popularity', 'desc')
            ->get();
    }
}
