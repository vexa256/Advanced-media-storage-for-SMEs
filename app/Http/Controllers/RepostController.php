<?php

namespace App\Http\Controllers;

use Auth;
use App\Album;
use App\Repost;
use Common\Core\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RepostController extends BaseController
{
    /**
     * @var Repost
     */
    private $repost;

    /**
     * @var Request
     */
    private $request;

    /**
     * @param Repost $repost
     * @param Request $request
     */
    public function __construct(Repost $repost, Request $request)
    {
        $this->middleware('auth');

        $this->repost = $repost;
        $this->request = $request;
    }

    /**
     * @return JsonResponse
     */
    public function repost()
    {
        $userId = Auth::id();
        $repostableType = $this->request->get('repostable_type');

        $table = $repostableType === Album::class ? 'albums' : 'tracks';
        $this->validate($this->request, [
            'repostable_type' => 'required',
            'repostable_id' => "required|exists:$table,id",
        ]);

        $existingRepost = $this->repost
            ->where('user_id', $userId)
            ->where('repostable_id', $this->request->get('repostable_id'))
            ->where('repostable_type', $repostableType)
            ->first();

        if ($existingRepost) {
            $existingRepost->delete();
            return $this->success(['action' => 'removed']);
        } else {
            $newRepost = $this->repost->create([
                'user_id' => $userId,
                'repostable_id' => $this->request->get('repostable_id'),
                'repostable_type' => $repostableType,
            ]);
            return $this->success(['action' => 'added', 'repost' => $newRepost]);
        }
    }
}
