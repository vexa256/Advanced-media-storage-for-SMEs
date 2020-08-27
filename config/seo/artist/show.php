<?php

return [
    [
        'property' => 'og:url',
        'content' =>  '{{URL.ARTIST}}',
    ],
    [
        'property' => 'og:title',
        'content' => '{{ARTIST.NAME}} - {{SITE_NAME}}',
    ],
    [
        'property' => 'og:description',
        'content' => '{{ARTIST.BIO.CONTENT}}',
    ],
    [
        'property' => 'og:type',
        'content' => 'music.musician',
    ],
    [
        'property' => 'og:image',
        'content' => '{{ARTIST.IMAGE_SMALL}}',
    ],
    [
        'property' => 'og:image:width',
        'content' => '1000',
    ],
    [
        'property' => 'og:image:height',
        'content' => '667',
    ],
    [
        'nodeName' => 'script',
        'type' => 'application/ld+json',
        '_text' => [
            "@context" => "http://schema.org",
            "@type" => "MusicGroup",
            "@id" => "{{URL.ARTIST}}",
            "name" => "{{ARTIST.NAME}}",
            "url" => "{{URL.ARTIST}}",
            "description" => "{{ARTIST.BIO.BIO}}",
            "image" => "{{ARTIST.IMAGE_LARGE}}"
        ],
    ]
];
