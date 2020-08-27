<?php namespace App\Http\Controllers;

use App;
use App\Album;
use App\Artist;
use App\Services\Artists\NormalizesArtist;
use App\Services\Providers\Local\LocalSearch;
use App\Services\Providers\ProviderResolver;
use App\Services\Search\PlaylistSearch;
use App\Services\Search\SearchSaver;
use App\Services\Search\UserSearch;
use App\Track;
use App\Traits\DeterminesArtistType;
use App\User;
use Common\Core\BaseController;
use Common\Settings\Settings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class SearchController extends BaseController
{
    use NormalizesArtist, DeterminesArtistType;

    /**
     * @var ProviderResolver
     */
    private $provider;

    /**
     * @var SearchSaver
     */
    private $saver;

    /**
     * @var UserSearch
     */
    private $userSearch;

    /**
     * @var PlaylistSearch
     */
    private $playlistSearch;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Settings
     */
    private $settings;

    /**
     * @param Request $request
     * @param Settings $settings
     * @param SearchSaver $saver
     * @param UserSearch $userSearch
     * @param ProviderResolver $provider
     * @param PlaylistSearch $playlistSearch
     */
    public function __construct(
        Request $request,
        SearchSaver $saver,
        Settings $settings,
        UserSearch $userSearch,
        ProviderResolver $provider,
        PlaylistSearch $playlistSearch
    )
    {
        $this->saver = $saver;
        $this->request = $request;
        $this->settings = $settings;
        $this->provider = $provider;
        $this->userSearch = $userSearch;
        $this->playlistSearch = $playlistSearch;
    }

    /**
     * @return JsonResponse
     */
    public function index()
    {
        $modelTypes = explode(',', $this->request->get('modelTypes'));

        // If artist type is "user" we can skip searching artists as "users" and "artists" will return the same
        $artistKey = array_search(Artist::class, $modelTypes);
        $userKey = array_search(User::class, $modelTypes);
        if ($this->determineArtistType() === User::class && $artistKey !== false && $userKey !== false) {
            unset($modelTypes[$artistKey]);
        }
        $limit = $this->request->get('limit', 3);
        $query = $this->request->get('query');
        $contentProvider = $this->provider->get('search', $this->request->get('forceLocal') ? 'local' : null);

        foreach ($modelTypes as $modelType) {
            $this->authorize('index', $modelType);
        }

        $results = $contentProvider->search($query, $limit, $modelTypes);

        if ( ! is_a($contentProvider, LocalSearch::class) ) {
            $results = $this->saver->save($results);
        }

        // search local only models
        foreach ($modelTypes as $modelType) {
            if ($modelType === App\Playlist::class) {
                $results['playlists'] = $this->playlistSearch->search($query, $limit);
            } else if ($modelType === User::class) {
                $results['users'] = $this->userSearch->search($query, $limit);
            } else if ($modelType === App\Channel::class) {
                $results['channels'] = app(App\Channel::class)
                    ->where('name', 'like', "$query%")
                    ->limit($limit)
                    ->get();
            } else if ($modelType === App\Genre::class) {
                $results['genres'] = app(App\Genre::class)
                    ->where('name', 'like', "$query%")
                    ->limit($limit)
                    ->get();
            } else if ($modelType === App\Tag::class) {
                $results['tags'] = app(App\Tag::class)
                    ->where('name', 'like', "$query%")
                    ->limit($limit)
                    ->get();
            }
        }

        if (isset($results['artists'])) {
            $results = $this->filterOutBlockedArtists($results);
            $results['artists'] = $results['artists']->map(function($artist) {
                return $this->normalizeArtist($artist);
            });
        }

        $response = [
            'query' => e($query),
            'results' =>  $this->filterOutBlockedArtists($results),
        ];

        if ($this->request->get('flatten')) {
            $response['results'] = Arr::flatten($response['results'], 1);
        }

        return $this->success($response);
    }

    /**
     * @param int $trackId
     * @param string $artistName
     * @param string $trackName
     * @return array
     */
    public function searchAudio($trackId, $artistName, $trackName)
    {
        $this->authorize('show', Track::class);

        return $this->provider->get('audio_search')->search($trackId, $artistName, $trackName, 1);
    }

    /**
     * Remove artists that were blocked by admin from search results.
     *
     * @param array $results
     * @return array
     */
    private function filterOutBlockedArtists($results)
    {
        if (($artists = $this->settings->get('artists.blocked'))) {
            $artists = json_decode($artists);

            foreach ($results['artists'] as $k => $artist) {
                if ($this->shouldBeBlocked($artist['name'], $artists)) {
                    unset($results['artists'][$k]);
                }
            }

            foreach ($results['albums'] as $k => $album) {
                if (isset($album['artist'])) {
                    if ($this->shouldBeBlocked($album['artist']['name'], $artists)) {
                        unset($results['albums'][$k]);
                    }
                }
            }

            foreach ($results['tracks'] as $k => $track) {
                if (isset($track['album']['artist'])) {
                    if ($this->shouldBeBlocked($track['album']['artist']['name'], $artists)) {
                        unset($results['tracks'][$k]);
                    }
                }
            }
        }

        return $results;
    }

    /**
     * Check if given artist should be blocked.
     *
     * @param string $name
     * @param array $toBlock
     * @return boolean
     */
    private function shouldBeBlocked($name, $toBlock)
    {
        foreach ($toBlock as $blockedName) {
            $pattern = '/' . str_replace('*', '.*?', strtolower($blockedName)) . '/i';
            if (preg_match($pattern, $name)) return true;
        }
    }
}
