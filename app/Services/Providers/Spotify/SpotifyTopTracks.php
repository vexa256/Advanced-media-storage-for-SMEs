<?php namespace App\Services\Providers\Spotify;

use App;
use App\Album;
use App\Artist;
use App\Services\HttpClient;
use App\Services\Providers\ContentProvider;
use App\Services\Providers\SaveOrUpdate;
use App\Track;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class SpotifyTopTracks implements ContentProvider {

    use SaveOrUpdate;

    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var SpotifyNormalizer
     */
    private $normalizer;

    /**
     * @param SpotifyNormalizer $normalizer
     */
    public function __construct(SpotifyNormalizer $normalizer)
    {
        $this->httpClient    = App::make('SpotifyHttpClient');
        $this->normalizer = $normalizer;

        ini_set('max_execution_time', 0);

    }

    public function getContent()
    {
        $csv = $this->getSpotifyChartsCsv();
        $split = explode("\n", $csv);
        $ids = '';

        foreach ($split as $k => $line) {
            if ($k === 0) continue;
            if ($k > 50) break;

            preg_match('/.+?\/track\/(.+)/', $line, $matches);

            if (isset($matches[1])) {
                $ids .= $matches[1].',';
            }
        }

        $ids = trim($ids, ',');
        $response = $this->httpClient->get('tracks?ids='.$ids);

        return $this->saveAndLoad($response['tracks']);
    }

    public function saveAndLoad($spotifyTracks, $limit = 50)
    {
        $normalizedTracks = collect($spotifyTracks)->map(function($track) {
            return $this->normalizer->track($track);
        });

        $savedArtists = $this->saveArtists($normalizedTracks);
        $savedAlbums = $this->saveAlbums($normalizedTracks, $savedArtists);

        return $this->saveTracks($normalizedTracks, $savedAlbums, $savedArtists, $limit)->values();

    }

    /**
     * @param Collection $normalizedTracks
     * @return Artist[]|\Illuminate\Database\Eloquent\Collection
     */
    private function saveArtists($normalizedTracks)
    {
        $normalizedArtists = $normalizedTracks
            ->pluck('artists')
            ->flatten(1)
            ->unique('spotify_id');

        $this->saveOrUpdate($normalizedArtists, 'artists');
        return app(Artist::class)->whereIn('spotify_id', $normalizedArtists->pluck('spotify_id'))->get();
    }

    /**
     * @param Collection $normalizedTracks
     * @param Collection $savedArtists
     * @return Album[]|\Illuminate\Database\Eloquent\Collection
     */
    private function saveAlbums($normalizedTracks, $savedArtists)
    {
        $normalizedAlbums = $normalizedTracks->map(function($normalizedTrack) use($savedArtists) {
            $normalizedAlbum = $normalizedTrack['album'];
            $normalizedAlbum['artist_id'] = $savedArtists->where('spotify_id', $normalizedTrack['artists'][0]['spotify_id'])->first()->id;
            return $normalizedAlbum;
        });

        $this->saveOrUpdate($normalizedAlbums, 'albums');

        return app(Album::class)->with(['artist' => function(BelongsTo $q) {
            $q->select('artists.id', 'artists.name');
        }])->whereIn('spotify_id', $normalizedAlbums->pluck('spotify_id'))->get();
    }

    /**
     * @param Collection $normalizedTracks
     * @param Collection $savedAlbums
     * @param Collection $artists
     * @param int $limit
     * @return Track[]|\Illuminate\Database\Eloquent\Collection
     */
    private function saveTracks($normalizedTracks, $savedAlbums, $artists, $limit = 50)
    {
        $originalOrder = [];

        $tracksForInsert = $normalizedTracks->map(function($track, $k) use($savedAlbums, &$originalOrder) {
            // spotify sometimes has multiple albums with same name for same artist
            $album = $savedAlbums->where('spotify_id', $track['album']['spotify_id'])->first();
            if ( ! $album) {
                $album = $savedAlbums
                    ->where('name', $track['album']['name'])
                    ->where('artist.name', $track['album']['artist']['name'])
                    ->first();
            }

            $track['album_id'] = $album->id;
            $originalOrder[$track['name']] = $k;
            return $track;
        });

        $this->saveOrUpdate($tracksForInsert, 'tracks');

        $loadedTracks = app(Track::class)->whereIn('spotify_id', $tracksForInsert->pluck('spotify_id'))->limit($limit)->get();

        // attach artists to tracks
        $pivots = $tracksForInsert->map(function($trackForInsert) use($normalizedTracks, $loadedTracks, $artists) {
            $tempArtists = $normalizedTracks->where('spotify_id', $trackForInsert['spotify_id'])->first()['artists'];
            return $tempArtists->map(function($artist) use($artists, $trackForInsert, $loadedTracks) {
                $artist = $artists->first(function($a) use($artist) {
                    return $a['spotify_id'] === $artist['spotify_id'];
                });
                return [
                    'artist_id' => $artist['id'],
                    'track_id' => $loadedTracks->where('spotify_id', $trackForInsert['spotify_id'])->first()->id,
                ];
            });
        })->flatten(1);

        $this->saveOrUpdate($pivots, 'artist_track');

        $loadedTracks->load(['artists', 'album.artist' => function(BelongsTo $q) {
            return $q->select('id', 'name');
        }]);

        return $loadedTracks->sort(function($a, $b) use ($originalOrder) {
            $originalAIndex = isset($originalOrder[$a->name]) ? $originalOrder[$a->name] : 0;
            $originalBIndex = isset($originalOrder[$b->name]) ? $originalOrder[$b->name] : 0;

            if ($originalAIndex == $originalBIndex) {
                return 0;
            }
            return ($originalAIndex < $originalBIndex) ? -1 : 1;
        });
    }

    /**
     * @return string
     */
    private function getSpotifyChartsCsv()
    {
        $ch = curl_init('https://spotifycharts.com/regional/global/daily/latest/download');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
}
