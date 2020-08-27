<?php

namespace App;

class AlbumWithTracks extends Album
{
    protected $table = 'albums';
    protected $with = ['tracks', 'artist'];
}