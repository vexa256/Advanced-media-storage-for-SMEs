<?php

return [
    [
        'property' => 'og:url',
        'content' =>  '{{URL.USER}}',
    ],
    [
        'property' => 'og:title',
        'content' => '{{USER.DISPLAY_NAME}}',
    ],
    [
        'property' => 'og:description',
        'content' => '{{USER.PROFILE.DESCRIPTION}} | {{SITE_NAME}}',
    ],
    [
        'property' => 'og:type',
        'content' => 'profile',
    ],
    [
        'property' => 'og:image',
        'content' => '{{USER.AVATAR}}',
    ],
    [
        'property' => 'og:image:width',
        'content' => '200',
    ],
    [
        'property' => 'og:image:height',
        'content' => '200',
    ],
];
