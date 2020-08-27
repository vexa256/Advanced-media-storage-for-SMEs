<?php namespace App\Http\Controllers\UserLibrary;

use App\Track;
use Auth;
use Common\Database\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Common\Core\BaseController;

class UserLibraryAlbumsController extends BaseController {

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
    public function index()
    {
        $paginator = (new Paginator(Auth::user()->likedAlbums(), $this->request->all()))
            ->with('artist');

        $paginator->searchCallback = function(MorphToMany $builder, $query) {
            $builder->where('name', 'LIKE', $query.'%');

                //TODO: need to use whereMorphHas with laravel 5.8
//            $builder->orWhereHas('artist', function(Builder $q) use($query) {
//                return $q->where('name', 'LIKE', $query.'%');
//            });
        };

        $paginator->defaultPerPage = 25;
        $paginator->setDefaultOrderColumns('likes.created_at', 'desc');
        $pagination = $paginator->paginate();

        return $this->success(['pagination' => $pagination]);
    }
}
