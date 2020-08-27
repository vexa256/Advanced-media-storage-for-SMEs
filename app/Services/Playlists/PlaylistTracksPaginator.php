<?php namespace App\Services\Playlists;

use App\Services\Tracks\Queries\PlaylistTrackQuery;
use App\Track;
use Common\Database\Paginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class PlaylistTracksPaginator
{
    /**
     * @var Track
     */
    private $track;

    /**
     * @var Request
     */
    private $request;

    /**
     * @param Track $track
     * @param Request $request
     */
    public function __construct(Track $track, Request $request)
    {
        $this->track = $track;
        $this->request = $request;
    }

    /**
     * @param integer $playlistId
     * @return LengthAwarePaginator
     */
    public function paginate($playlistId)
    {
        $query = (new PlaylistTrackQuery([
            'orderBy' => $this->request->get('orderBy'),
            'orderDir' => $this->request->get('orderDir'),
        ]))->get($playlistId);

        $paginator = (new Paginator($query, $this->request->all()));
        $paginator->searchColumn = 'tracks.name';
        $paginator->defaultPerPage = 30;
        $paginator->dontSort = true;

        return $paginator->paginate();
    }
}
