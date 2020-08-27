<?php namespace App\Http\Controllers;

use App;
use App\Artist;
use App\Http\Requests\ModifyArtists;
use App\Jobs\IncrementModelViews;
use App\Services\Artists\CrupdateArtist;
use Common\Database\Paginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\Artists\ArtistsRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Common\Core\BaseController;

class ArtistController extends BaseController {

    /**
     * @var Request
     */
    private $request;

    /**
     * @var ArtistsRepository
     */
    private $repository;

    /**
     * @param Request $request
     * @param ArtistsRepository $repository
     */
	public function __construct(Request $request, ArtistsRepository $repository)
	{
        $this->request = $request;
        $this->repository = $repository;
    }

	/**
	 * @return JsonResponse
	 */
	public function index()
	{
        $this->authorize('index', Artist::class);

        $pagination = (new Paginator(app(Artist::class), $this->request->all(), 'pagination.artist_count'))
            ->withCount('albums')
            ->paginate();

	    return $this->success(['pagination' => $pagination]);
	}

    /**
     * @param integer $id
     * @return JsonResponse
     */
    public function show($id)
    {
        $this->authorize('show', Artist::class);

        $data = $this->repository->getById($id, $this->request->all());

        dispatch(new IncrementModelViews($data['artist']['id'], 'artist'));

        return $this->success($data);
    }

    /**
     * @param ModifyArtists $validate
     * @return JsonResponse
     */
    public function store(ModifyArtists $validate)
    {
        $this->authorize('store', Artist::class);

        $artist = app(CrupdateArtist::class)->execute($this->request->all());

        return $this->success(['artist' => $artist]);
    }

    /**
     * @param Artist $artist
     * @param ModifyArtists $validate
     * @return JsonResponse
     */
	public function update(Artist $artist, ModifyArtists $validate)
	{
		$this->authorize('update', $artist);

        $artist = app(CrupdateArtist::class)->execute($this->request->all(), $artist);

        return $this->success(['artist' => $artist]);
	}

    /**
     * Remove specified artists from database.
     *
     * @return JsonResponse
     */
	public function destroy()
	{
		$this->authorize('destroy', Artist::class);

	    $this->validate($this->request, [
		    'ids'   => 'required|array',
		    'ids.*' => 'required|integer'
        ]);

	    $this->repository->delete($this->request->get('ids'));

		return $this->success();
	}
}
