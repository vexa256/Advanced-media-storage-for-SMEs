<?php

//TODO: for json+ld
//@foreach($data['tracks'] as $track)
//        <meta property="music:song" content="{{ $utils->getTrackUrl($track) }}">
//    @endforeach

return [
    [
        'property' => 'og:url',
        'content' =>  '{{URL.PLAYLIST}}',
    ],
    [
        'property' => 'og:title',
        'content' => '{{PLAYLIST.NAME}} by {{PLAYLIST.EDITORS.0.DISPLAY_NAME}}',
    ],
    [
        'property' => 'og:description',
        'content' => '{{PLAYLIST.DESCRIPTION}}',
    ],
    [
        'property' => 'og:type',
        'content' => 'music.playlist',
    ],
    [
        'property' => 'og:image',
        'content' => '{{PLAYLIST.IMAGE}}',
    ],
    [
        'property' => 'og:image:width',
        'content' => '300',
    ],
    [
        'property' => 'og:image:height',
        'content' => '300',
    ],
];