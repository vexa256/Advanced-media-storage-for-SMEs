<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group(['prefix' => 'secure'], function () {
    // LANDING
    Route::get('landing/channels', 'LandingPageChannelController@index');

    // UPLOAD
    Route::post('music/upload', 'MusicUploadController@upload');

    // SEARCH
    Route::get('search/audio/{trackId}/{artistName}/{trackName}', 'SearchController@searchAudio');
    Route::get('search', 'SearchController@index');

    // YOUTUBE
    Route::post('youtube/log-client-error', 'YoutubeLogController@store');

    // ALBUMS
    Route::get('albums', 'AlbumController@index');
    Route::get('albums/{album}', 'AlbumController@show');
    Route::post('albums', 'AlbumController@store');
    Route::put('albums/{album}', 'AlbumController@update');
    Route::delete('albums', 'AlbumController@destroy');

    // ARTISTS
    Route::post('player/tracks', 'PlayerTracksController@index');
    Route::get('artists', 'ArtistController@index');
    Route::post('artists', 'ArtistController@store');
    Route::put('artists/{artist}', 'ArtistController@update');
    Route::get('artists/{nameOrId}', 'ArtistController@show');
    Route::get('artists/{id}/albums', 'ArtistAlbumsController@index');
    Route::delete('artists', 'ArtistController@destroy');
    
    // TRACKS
    Route::get('tracks/{track}/comments', 'TrackCommentsController@index');
    Route::get('tracks/{id}/wave', 'WaveController@show');
    Route::get('tracks', 'TrackController@index');
    Route::get('tracks/{id}/download', 'DownloadLocalTrackController@download');
    Route::post('tracks', 'TrackController@store');
    Route::put('tracks/{id}', 'TrackController@update');
    Route::get('tracks/top', 'TopTracksController@index');
    Route::get('tracks/{id}', 'TrackController@show');
    Route::delete('tracks', 'TrackController@destroy');

    // TRACK PLAYS
    Route::get('track/plays/{userId}', 'TrackPlaysController@index');
    Route::post('track/plays/{track}/log', 'TrackPlaysController@create');

    // LYRICS
    Route::get('lyrics', 'LyricsController@index');
    Route::post('lyrics', 'LyricsController@store');
    Route::delete('lyrics', 'LyricsController@destroy');
    Route::get('tracks/{id}/lyrics', 'LyricsController@show');
    Route::put('lyrics/{id}', 'LyricsController@update');

    // RADIO
    Route::get('radio/artist/{id}', 'ArtistRadioController@getRecommendations');
    Route::get('radio/track/{id}', 'TrackRadioController@getRecommendations');

    // GENRES
    Route::get('genres', 'GenreController@index');
    Route::post('genres', 'GenreController@store');
    Route::put('genres/{id}', 'GenreController@update');
    Route::delete('genres', 'GenreController@destroy');
    Route::get('genres/{name}', 'GenreController@show');

    // TAGS
    Route::get('tags/{tagName}/{mediaType}', 'TagMediaController@index');

    // USER LIBRARY
    Route::post('user/likeables', 'UserLibrary\UserLibraryTracksController@create');
    Route::delete('user/likeables', 'UserLibrary\UserLibraryTracksController@destroy');
    Route::get('user/library/tracks', 'UserLibrary\UserLibraryTracksController@index');
    Route::get('user/library/albums', 'UserLibrary\UserLibraryAlbumsController@index');
    Route::get('user/library/artists', 'UserLibrary\UserLibraryArtistsController@index');

    // USER PROFILE
    Route::get('user-profile/{id}', 'UserProfileController@show');
    Route::get('user-profile/{user}/load-more/{type}', 'UserProfileController@loadMore');
    Route::put('user-profile/{user}', 'UserProfileController@update');

    // USER FOLLOWERS
    Route::post('users/{id}/follow', 'UserFollowersController@follow');
    Route::post('users/{id}/unfollow', 'UserFollowersController@unfollow');

    // PLAYLISTS
    Route::get('playlists/{id}', 'PlaylistController@show');
    Route::get('playlists', 'PlaylistController@index');
    Route::get('user/{id}/playlists', 'UserPlaylistsController@index');
    Route::put('playlists/{id}', 'PlaylistController@update');
    Route::post('playlists', 'PlaylistController@store');
    Route::delete('playlists', 'PlaylistController@destroy');
    Route::post('playlists/{id}/follow', 'UserPlaylistsController@follow');
    Route::post('playlists/{id}/unfollow', 'UserPlaylistsController@unfollow');
    Route::get('playlists/{id}/tracks', 'PlaylistTracksController@index');
    Route::post('playlists/{id}/tracks/add', 'PlaylistTracksController@add');
    Route::post('playlists/{id}/tracks/remove', 'PlaylistTracksController@remove');
    Route::post('playlists/{id}/tracks/order', 'PlaylistTracksOrderController@change');

    // REPOSTS
    Route::post('repost', 'RepostController@repost');

    // ADMIN
    Route::post('admin/sitemap/generate', 'SitemapController@generate');

    // CHANNELS
    Route::post('channel/{channel}/detach-item', 'ChannelController@detachItem');
    Route::post('channel/{channel}/attach-item', 'ChannelController@attachItem');
    Route::post('channel/{channel}/change-order', 'ChannelController@changeOrder');
    Route::post('channel/{channel}/auto-update-content', 'ChannelController@autoUpdateChannelContents');
    Route::apiResource('channel', 'ChannelController');

    // NOTIFICATIONS
    Route::get('notifications', 'NotificationController@index');
    Route::post('notifications/mark-as-read', 'NotificationController@markAsRead');
});

//LEGACY
Route::get('track/{id}/{mime}/stream', 'TrackStreamController@stream');

//FRONT-END ROUTES THAT NEED TO BE PRE-RENDERED
Route::get('/', '\Common\Core\Controllers\HomeController@show')->middleware('prerenderIfCrawler');
Route::get('artist/{name}', 'ArtistController@show')->middleware('prerenderIfCrawler');
Route::get('artist/{id}/{name}', 'ArtistController@show')->middleware('prerenderIfCrawler');
Route::get('album/{album}/{artistId}/{albumName}', 'AlbumController@show')->middleware('prerenderIfCrawler');
Route::get('track/{id}', 'TrackController@show')->middleware('prerenderIfCrawler');
Route::get('track/{id}/{name}', 'TrackController@show')->middleware('prerenderIfCrawler');
Route::get('playlists/{id}', 'PlaylistController@show')->middleware('prerenderIfCrawler');
Route::get('playlists/{id}/{name}', 'PlaylistController@show')->middleware('prerenderIfCrawler');
Route::get('user/{id}', '\Common\Auth\Controllers\UserController@show')->middleware('prerenderIfCrawler');
Route::get('user/{id}/{name}', '\Common\Auth\Controllers\UserController@show')->middleware('prerenderIfCrawler');
Route::get('genre/{name}', 'GenreController@show')->middleware('prerenderIfCrawler');
Route::get('search/{query}', 'SearchController@index')->middleware('prerenderIfCrawler');
Route::get('search/{query}/{tab}', 'SearchController@index')->middleware('prerenderIfCrawler');

//CATCH ALL ROUTES AND REDIRECT TO HOME
Route::get('{all}', '\Common\Core\Controllers\HomeController@show')->where('all', '.*');
