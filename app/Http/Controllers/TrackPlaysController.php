<?php namespace App\Http\Controllers;

use App;
use App\Services\Tracks\LogTrackPlay;
use App\Services\Tracks\Queries\HistoryTrackQuery;
use App\Track;
use App\TrackPlay;
use Carbon\Carbon;
use Common\Core\BaseController;
use Common\Database\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrackPlaysController extends BaseController
{
    /**
     * @var TrackPlay
     */
    private $trackPlay;

    /**
     * @var Request
     */
    private $request;

    /**
     * @param TrackPlay $trackPlay
     * @param Request $request
     */
    public function __construct(TrackPlay $trackPlay, Request $request)
	{
        $this->request = $request;
        $this->trackPlay = $trackPlay;
    }

    public function index($userId)
    {
        $orderBy = $this->request->get('orderBy');
        $orderDir = $this->request->get('orderDir');
        // prevent ambiguous column db error
        if ($orderBy === 'created_at') {
            $orderBy = 'track_plays.created_at';
        }

        $query = (new HistoryTrackQuery([
            'orderBy' => $orderBy,
            'orderDir' => $orderDir,
        ]))->get($userId);
        $query->groupBy('tracks.id');
        $paginator = (new Paginator($query, $this->request->all()));
        $paginator->dontSort = true;
        $paginator->defaultPerPage = 30;

        $paginator->searchCallback = function(Builder $builder, $query) {
            $builder->where('tracks.name', 'LIKE', $query.'%');
        };

        $pagination = $paginator->paginate();

        $pagination->transform(function(Track $track) {
            $track->added_at_relative = $track->added_at ? (new Carbon($track->added_at))->diffForHumans() : null;
            return $track;
        });

        return $this->success(['pagination' => $pagination]);

    }

    /**
     * @param Track $track
     * @return JsonResponse
     */
    public function create(Track $track)
    {
        $this->authorize('show', $track);

        $play = app(LogTrackPlay::class)->execute($track);

        return $this->success(['play' => $play]);
    }
}
