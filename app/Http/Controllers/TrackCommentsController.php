<?php

namespace App\Http\Controllers;

use App\Services\Tracks\PaginateTrackComments;
use App\Track;
use Common\Core\BaseController;

class TrackCommentsController extends BaseController
{
    public function index(Track $track)
    {
        $this->authorize('show', $track);

        $pagination = app(PaginateTrackComments::class)->execute($track);

        return $this->success(['pagination' => $pagination]);
    }
}