<?php

namespace App\Services\Artists;

use App\Artist;
use App\MixedArtist;
use App\User;

trait NormalizesArtist
{
    /**
     * @param Artist|User $model
     * @return array
     */
    public function normalizeArtist($model)
    {
        $type = get_class($model);
        return [
            'id' => $model->id,
            'artist_type' => $type,
            'model_type' => MixedArtist::class,
            'name' => $type === User::class ? $model->display_name : $model->name,
            'image' => $type === User::class ? $model->avatar : $model->image_small
        ];
    }
}