<?php namespace App\Http\Controllers;

use App;
use App\Album;
use App\Http\Requests\ModifyAlbums;
use App\Jobs\IncrementModelViews;
use App\Services\Albums\CrupdateAlbum;
use App\Services\Albums\DeleteAlbums;
use App\Services\Albums\ShowAlbum;
use Common\Core\BaseController;
use Common\Database\Paginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlbumController extends BaseController {

    /**
     * @var Request
     */
    private $request;

    /**
     * @param Request $request
     */
	public function __construct(Request $request)
	{
        $this->request = $request;
    }

	/**
	 * @return JsonResponse
	 */
	public function index()
	{
		$this->authorize('index', Album::class);

        $paginator = (new Paginator(app(Album::class), $this->request->all(), 'pagination.album_count'));
        $paginator
            ->with('artist')
            ->withCount('tracks')
            ->setDefaultOrderColumns('release_date');

        return $this->success(['pagination' => $paginator->paginate()]);
	}

    /**
     * @param Album $album
     * @return JsonResponse
     */
    public function show(Album $album)
    {
        $this->authorize('show', $album);

        $album = app(ShowAlbum::class)
            ->execute($album, $this->request->all());

        dispatch(new IncrementModelViews($album->id, 'album'));

        return $this->success(['album' => $album]);
    }

    /**
     * @param Album $album
     * @param ModifyAlbums $validate
     * @return JsonResponse
     */
	public function update(Album $album, ModifyAlbums $validate)
	{
	    $this->authorize('update', $album);

		$album = app(CrupdateAlbum::class)->execute($this->request->all(), $album);

	    return $this->success(['album' => $album]);
	}

    /**
     * @param ModifyAlbums $validate
     * @return JsonResponse
     */
    public function store(ModifyAlbums $validate)
    {
        $this->authorize('store', Album::class);

        $album = app(CrupdateAlbum::class)->execute($this->request->all());

        return $this->success(['album' => $album]);
    }

	/**
	 *s @return mixed
	 */
	public function destroy()
	{
        $albumIds = $this->request->get('ids');
	    $this->authorize('destroy', [Album::class, $albumIds]);

        $this->validate($this->request, [
            'ids'   => 'required|array',
            'ids.*' => 'required|integer'
        ]);

        app(DeleteAlbums::class)->execute($albumIds);

	    return $this->success();
	}
}
