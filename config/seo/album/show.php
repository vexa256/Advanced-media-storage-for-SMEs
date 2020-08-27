<?php

return [
    [
        'property' => 'og:url',
        'content' =>  '{{URL.ALBUM}}',
    ],
    [
        'property' => 'og:title',
        'content' => '{{ALBUM.NAME}} - {{ALBUM.ARTIST.NAME}} - {{SITE_NAME}}',
    ],
    [
        'property' => 'og:description',
        'content' => '{{ALBUM.NAME}} album by {{ALBUM.ARTIST.NAME}} on {{SITE_NAME}}',
    ],
    [
        'property' => 'og:type',
        'content' => 'music.album',
    ],
    [
        'property' => 'music:release_date',
        'content' => '{{ALBUM.RELEASE_DATE}}',
    ],
    [
        'property' => 'og:image',
        'content' => '{{ALBUM.IMAGE}}',
    ],
    [
        'property' => 'og:image:width',
        'content' => '300',
    ],
    [
        'property' => 'og:image:height',
        'content' => '300',
    ],
    [
        'nodeName' => 'script',
        'type' => 'application/ld+json',
        '_text' => [
            "@context" => "http://schema.org",
            "@type" => "MusicAlbum",
            "@id" => "{{URL.ALBUM}}",
            "url" => "{{URL.ALBUM}}",
            "name" => "{{ALBUM.NAME}}",
            "description" => "{{ALBUM.NAME}} album by {{ALBUM.ARTIST.NAME}} on {{SITE_NAME}}",
            "image" => "{{ALBUM.IMAGE}}",
            "datePublished" => "{{ALBUM.RELEASE_DATE}}",
            'track' => [
                '_type' => 'loop',
                'dataSelector' => 'ALBUM.TRACKS',
                'limit' => 20,
                'template' => [
                    '@type' => 'MusicRecording',
                    'url' => '{{URL.TRACK}}',
                    'name' => '{{TRACK.NAME}}'
                ],
            ],
        ]
    ]
];