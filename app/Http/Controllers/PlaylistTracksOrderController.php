<?php namespace App\Http\Controllers;

use DB;
use App\Playlist;
use Illuminate\Http\Request;
use Common\Core\BaseController;

class PlaylistTracksOrderController extends BaseController {

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Playlist
     */
    private $playlist;

    /**
     * @param Request $request
     * @param Playlist $playlist
     */
    public function __construct(Request $request, Playlist $playlist)
    {
        $this->request = $request;
        $this->playlist = $playlist;
    }

    /**
     * Change playlist tracks order.
     *
     * @param integer $playlistId
     * @return \Illuminate\Http\JsonResponse
     */
    public function change($playlistId) {

        $this->authorize('update', $this->playlist->with('editors')->find($playlistId));

        $this->validate($this->request, [
            'ids'   => 'array|min:1',
            'ids.*' => 'integer'
        ]);

        $queryPart = '';
        foreach($this->request->get('ids') as $position => $id) {
            $position++;
            $queryPart .= " when track_id=$id then $position";
        }

        DB::table('playlist_track')
            ->where('playlist_id', $playlistId)
            ->update(['position' => DB::raw("(case $queryPart end)")]);

        return $this->success([], 200);
    }
}
