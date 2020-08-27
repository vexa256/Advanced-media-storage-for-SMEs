<?php

namespace App\Http\Controllers;

use App\Album;
use App\Artist;
use App\MixedArtist;
use App\Playlist;
use App\Services\Tracks\Queries\AlbumTrackQuery;
use App\Services\Tracks\Queries\ArtistTrackQuery;
use App\Services\Tracks\Queries\BaseTrackQuery;
use App\Services\Tracks\Queries\HistoryTrackQuery;
use App\Services\Tracks\Queries\LibraryTracksQuery;
use App\Services\Tracks\Queries\PlaylistTrackQuery;
use App\User;
use Common\Core\BaseController;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PlayerTracksController extends BaseController
{
    /**
     * @var Artist
     */
    private $artist;

    /**
     * @var User
     */
    private $user;
    /**
     * @var Request
     */
    private $request;

    private $queryMap = [
        Playlist::class => PlaylistTrackQuery::class,
        Artist::class => ArtistTrackQuery::class,
        User::class => LibraryTracksQuery::class,
        Album::class => AlbumTrackQuery::class,
        MixedArtist::class => ArtistTrackQuery::class,
    ];

    /**
     * @param Artist $artist
     * @param User $user
     * @param Request $request
     */
    public function __construct(Artist $artist, User $user, Request $request)
    {
        $this->artist = $artist;
        $this->user = $user;
        $this->request = $request;
    }
    
    public function index()
    {
        $queueId = $this->request->get('queueId');
        $perPage = (int) $this->request->get('perPage', 25);
        list($modelType, $modelId, $queueType, $queueOrder) = array_pad(explode('.', $queueId), 4, null);

        $trackQuery = $this->getTrackQuery($modelType, $queueOrder, $queueType);

        if ( ! $trackQuery) {
            return $this->success(['tracks' => []]);
        }

        $dbQuery = $trackQuery->get($modelId);
        $order = $trackQuery->getOrder();

        if ($lastTrack = $this->request->get('lastTrack')) {
            $whereCol = $order['col'] === 'added_at' ? 'likes.created_at' : $order['col'];
            $this->applyCursor($dbQuery, [$whereCol => $order['dir'], 'tracks.id' => 'desc'], [$lastTrack[$order['col']], $lastTrack['id']]);
            // TODO: check if playlist position should be asc or desc
        }

        return $this->success(['tracks' => $dbQuery->limit($perPage)->get()]);
    }

    /**
     * @param string $modelType
     * @param string|null $order
     * @param string $queueType
     * @return BaseTrackQuery|void
     */
    private function getTrackQuery($modelType, $order, $queueType)
    {
        $params = [];
        if ($order) {
            $parts = explode('|', $order);
            $params['orderBy'] = $parts[0];
            $params['orderDir'] = $parts[1];
        }

        if ($modelType === User::class) {
            return $queueType === 'playHistory' ?
                new HistoryTrackQuery($params) :
                new LibraryTracksQuery($params);
        }

        if (isset($this->queryMap[$modelType])) {
            return new $this->queryMap[$modelType]($params);
        }
    }

    private function applyCursor(Builder $query, $columns, $cursor)
    {
        $query->where(function (Builder $query) use ($columns, $cursor) {
            $column = key($columns);
            $direction = array_shift($columns);
            $value = array_shift($cursor);

            $query->where($column, $direction === 'asc' ? '>' : '<', (is_null($value) ? 0 : $value));

            if ( ! empty($columns)) {
                $query->orWhere($column, (is_null($value) ? 0 : $value));
                $this->applyCursor($query, $columns, $cursor);
            }
        });
    }
}
