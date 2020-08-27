<?php namespace App\Http\Controllers;

use App;
use App\Playlist;
use App\Services\Playlists\DeletePlaylists;
use Common\Database\Paginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Jobs\IncrementModelViews;
use App\Http\Requests\ModifyPlaylist;
use Illuminate\Filesystem\FilesystemManager;
use App\Services\Playlists\PlaylistTracksPaginator;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Pagination\LengthAwarePaginator;
use Common\Core\BaseController;
use Common\Settings\Settings;


class PlaylistController extends BaseController
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var Settings
     */
    private $settings;

    /**
     * @var Playlist
     */
    private $playlist;

    /**
     * @var PlaylistTracksPaginator
     */
    private $tracksPaginator;

    /**
     * @var FilesystemManager
     */
    private $storage;

    /**
     * @param Request $request
     * @param Settings $settings
     * @param Playlist $playlist
     * @param FilesystemManager $storage
     * @param PlaylistTracksPaginator $tracksPaginator
     */
    public function __construct(
        Request $request,
        Settings $settings,
        Playlist $playlist,
        PlaylistTracksPaginator $tracksPaginator,
        FilesystemManager $storage
    )
    {
        $this->request = $request;
        $this->settings = $settings;
        $this->playlist = $playlist;
        $this->tracksPaginator = $tracksPaginator;
        $this->storage = $storage;
    }

    /**
     * @return JsonResponse
     */
    public function index()
    {
        $this->authorize('index', Playlist::class);

        $pagination = (new Paginator($this->playlist, $this->request->all()))
            ->withCount('tracks')
            ->with(['tracks' => function (BelongsToMany $q) {
                return $q->with('album')->limit(1);
            }, 'editors'])
            ->paginate();

        return $this->success(['pagination' => $pagination]);
    }

    /**
     * @param int $id
     * @return JsonResponse
     */
    public function show($id)
    {
        $playlist = $this->playlist->with('editors')->withCount('tracks')->findOrFail($id);

        $this->authorize('show', $playlist);

        $totalDuration = $playlist->tracks()->sum('tracks.duration');

        dispatch(new IncrementModelViews($playlist->id, 'playlist'));

        return $this->success([
            'playlist' => $playlist->toArray(),
            'tracks' => $this->tracksPaginator->paginate($playlist->id),
            'totalDuration' => (int) $totalDuration
        ]);
    }

    /**
     * @param ModifyPlaylist $validate
     * @return Playlist
     */
    public function store(ModifyPlaylist $validate)
    {
        $this->authorize('store', Playlist::class);

        $playlist = $this->request->user()->playlists()->create($this->request->all(), ['owner' => 1]);

        return $playlist;
    }

    /**
     * @param  int $id
     * @param ModifyPlaylist $validate
     * @return Playlist
     */
    public function update($id, ModifyPlaylist $validate)
    {
        $playlist = $this->playlist->with('editors')->findOrFail($id);

        $this->authorize('update', $playlist);

        $playlist->fill($this->request->all())->save();

        return $playlist;
    }

    /**
     * @return JsonResponse
     */
    public function destroy()
    {
        $ids = $this->request->get('ids');
        $playlists = $this->playlist->with('editors')->whereIn('id', $ids)->get();

        $this->authorize('destroy', [Playlist::class, $playlists]);

        app(DeletePlaylists::class)->execute($playlists);

        return $this->success();
    }

    /**
     * Follow playlist with currently logged in user.
     *
     * @param int $id
     * @return integer
     */
    public function follow($id)
    {
        $playlist = $this->playlist->findOrFail($id);

        $this->authorize('show', $playlist);

        return $this->request->user()->playlists()->sync([$id], false);
    }

    /**
     * Un-Follow playlist with currently logged in user.
     *
     * @param integer $id
     * @return JsonResponse
     */
    public function unfollow($id)
    {
        $playlist = $this->request->user()->playlists()->find($id);

        $this->authorize('show', $playlist);

        if ($playlist) {
            $this->request->user()->playlists()->detach($id);
        }

        return $this->success();
    }
}
