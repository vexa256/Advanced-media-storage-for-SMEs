<?php namespace App\Http\Controllers\UserLibrary;

use App\Services\Artists\NormalizesArtist;
use App\Track;
use App\User;
use Auth;
use Common\Core\BaseController;
use Common\Database\Paginator;
use Common\Settings\Settings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserLibraryArtistsController extends BaseController {

    use NormalizesArtist;

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
        $paginator = (new Paginator(Auth::user()->likedArtists(), $this->request->all()));

        // TODO: if order col created_at order by likes.created_at

        $paginator->defaultPerPage = 25;
        $paginator->setDefaultOrderColumns('likes.created_at', 'desc');

        $paginator->searchCallback = function($builder, $query) {
            if (app(Settings::class)->get('player.artist_type') === 'user') {
                $builder->where(function($builder) use($query) {
                    $builder->where('username', 'LIKE', "$query%")
                        ->orWhere('first_name', 'LIKE', "$query%");
                });
            } else {
                $builder->where('name', 'LIKE', $query.'%');
            }
        };

        $pagination = $paginator->paginate();

        if ($pagination->first()['model_type'] === User::class) {
            $pagination->transform(function(User $artist) {
                $artist->setGravatarSize(220);
                return $artist;
            });
        }

        $pagination->transform(function($artist) {
            return $this->normalizeArtist($artist);
        });

        return $this->success(['pagination' => $pagination]);
    }
}
