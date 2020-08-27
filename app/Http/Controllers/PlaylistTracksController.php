<?php namespace App\Http\Controllers;

use App\Album;
use App\Playlist;
use App\Services\Tracks\Queries\PlaylistTrackQuery;
use App\Track;
use Common\Database\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\Playlists\PlaylistTracksPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Common\Core\BaseController;

class PlaylistTracksController extends BaseController {

    /**
     * @var Request
     */
    private $request;

    /**
     * @var PlaylistTracksPaginator
     */
    private $paginator;

    /**
     * @var Playlist
     */
    private $playlist;

    /**
     * @param Request $request
     * @param PlaylistTracksPaginator $paginator
     * @param Playlist $playlist
     */
    public function __construct(Request $request, PlaylistTracksPaginator $paginator, Playlist $playlist)
    {
        $this->request = $request;
        $this->paginator = $paginator;
        $this->playlist = $playlist;
    }

    /**
     * @param integer $playlistId
     * @return JsonResponse
     */
    public function index($playlistId) {

        $pagination = $this->paginator->paginate($playlistId);
        return $this->success(['pagination' => $pagination]);
    }

    /**
     * Add specified tracks to playlist.
     *
     * @param integer $id
     * @return Playlist
     */
    public function add($id) {
        $playlist = $this->playlist->findOrFail($id);

        $this->authorize('update', $playlist);

        $ids = $this->request->get('ids');
        $playlist->tracks()->sync($ids, false);
        $this->updateImage($playlist);

        return $playlist;
    }

    /**
     * Remove specified tracks from playlist.
     *
     * @param integer $id
     * @return Playlist
     */
    public function remove($id) {
        $playlist = $this->playlist->findOrFail($id);

        $this->authorize('update', $playlist);

        $ids = $this->request->get('ids');
        $playlist->tracks()->detach($ids);
        $this->updateImage($playlist);

        return $playlist;
    }

    private function updateImage(Playlist $playlist)
    {
        if ( ! $playlist->image && $firstTrack = $playlist->tracks()->with('album')->first()) {
            if ($firstTrack->image) {
                $playlist->image = $firstTrack->image;
            } else if ($firstTrack->album) {
                $playlist->image = $firstTrack->album->image;
            }
            $playlist->save();
        }
    }
}
