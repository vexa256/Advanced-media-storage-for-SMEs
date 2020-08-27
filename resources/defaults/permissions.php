<?php

return [
    'roles' => [
        [
            'default' => true,
            'name' => 'users',
            'extends' => 'users',
            'permissions' => [
                'artists.view',
                'albums.view',
                'tracks.view',
                'genres.view',
                'lyrics.view',
                'users.view' ,
                'playlists.create',
                'localizations.view',
                'playlists.view',
                'uploads.create',
                'channels.view',
                'comments.create',
           ]
        ],
        'guests' => [
            'guests' => true,
            'name' => 'guests',
            'extends' => 'guests',
            'permissions' => [
                'artists.view',
                'albums.view',
                'tracks.view',
                'genres.view',
                'lyrics.view',
                'users.view' ,
                'playlists.view',
                'channels.view',
            ]
        ]
    ],
    'all' => [
        //ARTISTS
        'artists' => [
            'artists.view',
            'artists.create',
            'artists.update',
            'artists.delete',
        ],

        //ALBUMS
        'albums' => [
            'albums.view',
            'albums.create',
            'albums.update',
            'albums.delete',
        ],

        //Tracks
        'tracks' => [
            'tracks.view',
            [
                'name' => 'tracks.create',
                'restrictions' => [
                    [
                        'name' => 'minutes',
                        'type' => 'number',
                        'description' => 'How many minutes all user tracks are allowed to take up. Leave empty for unlimited.',
                    ],
                ]
            ],
            'tracks.update',
            'tracks.delete',
            'tracks.download',
        ],

        //Genres
        'genres' => [
            'genres.view',
            'genres.create',
            'genres.update',
            'genres.delete',
        ],

        //Lyrics
        'lyrics' => [
            'lyrics.view',
            'lyrics.create',
            'lyrics.update',
            'lyrics.delete',
        ],

        //Playlists
        'playlists' => [
            'playlists.view',
            'playlists.create',
            'playlists.update',
            'playlists.delete',
        ],

        'channels' => [
            'channels.view',
            'channels.create',
            'channels.update',
            'channels.delete',
        ],

        'comments' => [
            'comments.view',
            'comments.create',
            'comments.update',
            'comments.delete',
        ],
    ]
];
