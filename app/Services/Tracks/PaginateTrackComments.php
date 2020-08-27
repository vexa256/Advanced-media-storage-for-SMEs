<?php

namespace App\Services\Tracks;

use App\Track;
use Common\Comments\Comment;
use Common\Comments\LoadChildComments;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaginateTrackComments
{
    /**
     * @param Track $track
     * @return array
     */
    public function execute(Track $track)
    {
        $pagination = $track->comments()
            ->rootOnly()
            ->with(['user' => function(BelongsTo $builder) {
                $builder->compact();
            }])
            ->paginate(25);

        $pagination->transform(function(Comment $comment) {
            $comment->relative_created_at = $comment->created_at->diffForHumans();
            return $comment;
        });

        $comments = app(LoadChildComments::class)
            ->execute($track, collect($pagination->items()));

        $pagination = $pagination->toArray();
        $pagination['data'] = $comments;

        return $pagination;
    }
}