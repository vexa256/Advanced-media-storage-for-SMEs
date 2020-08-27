<?php namespace App\Http\Controllers;

use App;
use App\User;
use App\Playlist;
use Common\Database\Paginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Common\Core\BaseController;

class UserPlaylistsController extends BaseController
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var Playlist
     */
    private $playlist;

    /**
     * @var App\User
     */
    private $user;

    /**
     * PlaylistController constructor.
     *
     * @param Request $request
     * @param Playlist $playlist
     * @param App\User $user
     */
    public function __construct(Request $request, Playlist $playlist, User $user)
    {
        $this->request = $request;
        $this->playlist = $playlist;

        $this->middleware('auth', ['only' => ['follow', 'unfollow']]);
        $this->user = $user;
    }

    /**
     * @param integer $userId
     * @return JsonResponse
     */
    public function index($userId)
    {
        $this->authorize('index', [Playlist::class, $userId]);

        if ($userId) {
            $user = $this->user->find($userId);
        } else {
            $user = $this->request->user();
        }

        $query = $user
            ->playlists()
            ->withCount('tracks')
            ->with(['tracks' => function (BelongsToMany $q) {
                return $q->with('album')->limit(1);
            }, 'editors']);

        $pagination = (new Paginator($query, $this->request->all()))
            ->paginate();

        return $this->success(['pagination' => $pagination]);
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
