<?php namespace App\Http\Controllers\UserLibrary;

use App\Services\Tracks\Queries\LibraryTracksQuery;
use App\Track;
use App\User;
use Auth;
use Carbon\Carbon;
use Common\Database\Paginator;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Common\Core\BaseController;
use Illuminate\Support\Collection;

class UserLibraryTracksController extends BaseController {

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Track
     */
    private $track;

    /**
     * @param Request $request
     * @param Track $track
     */
    public function __construct(Request $request, Track $track)
    {
        $this->middleware('auth');

        $this->request = $request;
        $this->track = $track;
    }

    /**
     * @return JsonResponse
     */
    public function create()
    {
        $likeables = collect($this->request->get('likeables'))
            ->map(function($likeable) {
                $likeable['user_id'] = Auth::user()->id;
                $likeable['created_at'] = Carbon::now();
                return $likeable;
            });
        DB::table('likes')->insert($likeables->toArray());
        return $this->success();
    }

    /**
     * @return JsonResponse
     */
    public function destroy()
    {
        $this->validate($this->request, [
            'likeables.*.likeable_id' => 'required|int',
            'likeables.*.likeable_type' => 'required|in:App\Track,App\Album,App\Artist',
        ]);

        $userId = Auth::id();
        $values = collect($this->request->get('likeables'))->map(function($likeable) use($userId) {
            $likeableType = str_replace('\\', '\\\\', $likeable['likeable_type']);
            return "('$userId', '{$likeable['likeable_id']}', '{$likeableType}')";
        })->implode(', ');
        DB::table('likes')->whereRaw("(user_id, likeable_id, likeable_type) in ($values)")->delete();
        return $this->success();
    }

    /**
     * @return JsonResponse
     */
    public function index()
    {
        $query = (new LibraryTracksQuery([
            'orderBy' => $this->request->get('orderBy'),
            'orderDir' => $this->request->get('orderDir'),
        ]))->get(Auth::id());
        $paginator = (new Paginator($query, $this->request->all()));
        $paginator->dontSort = true;
        $paginator->defaultPerPage = 30;

        $paginator->searchCallback = function(Builder $builder, $query) {
            $builder->where(function($builder) use($query) {
                $builder->where('name', 'LIKE', $query.'%');
                $builder->orWhereHas('album', function(Builder $q) use($query) {
                    return $q->where('name', 'LIKE', $query.'%')
                        ->orWhereHas('artist', function(Builder $q) use($query) {
                            return $q->where('name', 'LIKE', $query.'%');
                        });
                });
            });
        };

        $pagination = $paginator->paginate();

        $pagination->transform(function(Track $track) {
            $track->added_at_relative = $track->added_at ? (new Carbon($track->added_at))->diffForHumans() : null;
            return $track;
        });

        return $this->success(['pagination' => $pagination]);
    }
}
