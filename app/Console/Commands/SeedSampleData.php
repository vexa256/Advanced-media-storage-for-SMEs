<?php

namespace App\Console\Commands;

use App\Album;
use App\Artist;
use App\Genre;
use App\Services\Providers\SaveOrUpdate;
use App\Track;
use App\TrackPlay;
use App\User;
use App\UserLink;
use App\UserProfile;
use Artisan;
use Carbon\Carbon;
use Common\Auth\Permissions\Permission;
use Common\Comments\Comment;
use Common\Tags\Tag;
use DB;
use File;
use Hash;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Storage;

class SeedSampleData extends Command
{
    use SaveOrUpdate, ConfirmableTrait;

    /**
     * @var string
     */
    protected $signature = 'music:sample {--force : Force the operation to run when in production.}';

    /**
     * @var string
     */
    protected $description = 'Seed site with sample music data.';

    /**
     * @var array
     */
    private $trackNames;

    /**
     * @var Collection
     */
    private $genres;

    /**
     * @var array
     */
    private $albumNames;

    /**
     * @var array
     */
    private $artistNames;

    /**
     * @var array
     */
    private $albumImages;

    /**
     * @var array
     */
    private $comments;

    /**
     * @var Collection
     */
    private $commentUsers;

    /**
     * @var array
     */
    private $artistImages;

    /**
     * @var Collection
     */
    private $artists;

    /**
     * @var array
     */
    private $trackTempIds;

    /**
     * @var array
     */
    private $albumTempIds;

    /**
     * @var Tag[]|Collection
     */
    private $tags;

    /**
     * @return void
     */
    public function handle()
    {
        if ( ! $this->confirmToProceed()) {
            return;
        }

        Artisan::call('music:truncate', ['--force' => true]);

        $this->createAdminAccount();
        $this->loadSampleData();

        $this->artists = $this->createUsers(true);

        $this->commentUsers = $this->createUsers();
        $this->createFollows();

        $bar = $this->output->createProgressBar(count($this->artists));
        foreach ($this->artists as $artist) {
            $this->seedArtistData($artist);
            $bar->advance();
        }
        $bar->finish();

        $this->info('Decorating albums');
        $bar = $this->output->createProgressBar(
            app(Album::class)->whereIn('temp_id', $this->albumTempIds)->count() / 100
        );

        app(Album::class)
            ->whereIn('temp_id', $this->albumTempIds)
            ->chunkById(100, function(Collection $albums) use($bar) {
                $this->createLikesAndReposts($albums);
                $this->attachGenresToModels($albums);
                $bar->advance();
            });
        $bar->finish();

        $this->info('Decorating tracks');
        $bar = $this->output->createProgressBar(
            app(Track::class)->whereIn('temp_id', $this->trackTempIds)->count() / 100
        );

        app(Track::class)
            ->whereIn('temp_id', $this->trackTempIds)
            ->chunkById(100, function(Collection $tracks) use($bar) {
                $this->createTrackWaves($tracks);
                $this->createTrackComments($tracks);
                $this->createTrackPlays($tracks);
                $this->createLikesAndReposts($tracks);
                $this->attachGenresToModels($tracks);
                $this->attachTagsToTracks($tracks);
                $bar->advance();
            });
        $bar->finish();

        File::deleteDirectory(public_path('storage/samples'));
        File::copyDirectory(base_path('../samples/tracks'), public_path('storage/samples'));

        Artisan::call('channels:update');
        Artisan::call('cache:clear');
    }

    private function createTrackPlays(Collection $createdTracks)
    {
        $plays = $createdTracks->map(function ($track) {
            return factory(TrackPlay::class, rand(50, 845))->make()->map(function ($play) use ($track) {
                $play['user_id'] = $this->commentUsers->random()->id;
                $play['track_id'] = $track['id'];
                return $play;
            });
        })->flatten(1);
        $plays->chunk(500)->each(function($chunk) {
            DB::table('track_plays')->insert($chunk->toArray());
        });
    }

    private function seedArtistData(User $artist)
    {
        $trackTempId = Str::random(8);
        $this->trackTempIds[] = $trackTempId;

        // create tracks without album
        $tracks = $this->makeTracks(rand(30, 50), $trackTempId);
        app(Track::class)->insert($tracks);

        // create and load albums
        $albums = $this->makeAlbums($artist);
        app(Album::class)->insert($albums);
        $createdAlbumIds = app(Album::class)
            ->where('temp_id', $albums[0]['temp_id'])
            ->pluck('id');

        // create album tracks
        $albumTracks = $createdAlbumIds->map(function($albumId) use($trackTempId) {
            return $this->makeTracks(rand(3, 10), $trackTempId, $albumId);
        })->flatten(1);
        app(Track::class)->insert($albumTracks->toArray());

        $createdTrackIds = app(Track::class)
            ->where('temp_id', $trackTempId)
            ->pluck('id');
        $artist->uploadedTracks()->attach($createdTrackIds->toArray(), ['primary' => true]);
    }

    private function createFollows()
    {
        $follows = $this->commentUsers->map(function($follower) {
            $following = $this->artists->random(rand(3, 27));
            return $following->map(function(User $followed) use($follower) {
                return [
                    'follower_id' => $follower->id,
                    'followed_id' => $followed->id,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            });
        })->flatten(1);

        $followers = $this->commentUsers->map(function($followed) {
            $followers = $this->commentUsers->random(rand(1, 7));
            return $followers->map(function(User $follower) use($followed) {
                return [
                    'follower_id' => $follower->id,
                    'followed_id' => $followed->id,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            });
        })->flatten(1);

        DB::table('follows')->insert($follows->merge($followers)->toArray());
    }

    private function createLikesAndReposts($likeables)
    {
        $likes = collect($likeables)->map(function($likeable) {
            $users = $this->commentUsers->random(rand(5, 47));
            return $users->map(function(User $user) use($likeable) {
                return [
                    'likeable_id' => $likeable->id,
                    'likeable_type' => get_class($likeable),
                    'user_id' => $user->id,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            });
        })->flatten(1);
        DB::table('likes')->insert($likes->toArray());

        $reposts = collect($likeables)->map(function($likeable) {
            $users = $this->commentUsers->random(rand(5, 47));
            return $users->map(function(User $user) use($likeable) {
                return [
                    'repostable_id' => $likeable->id,
                    'repostable_type' => get_class($likeable),
                    'user_id' => $user->id,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            });
        })->flatten(1);
        DB::table('reposts')->insert($reposts->toArray());
    }

    private function loadSampleData()
    {
        $this->trackNames = explode(PHP_EOL, file_get_contents(base_path('../samples/track-names.txt')));
        $this->albumNames = explode(PHP_EOL, file_get_contents(base_path('../samples/album-names.txt')));
        $this->artistNames = explode(PHP_EOL, file_get_contents(base_path('../samples/artist-names.txt')));
        $this->albumImages = explode(PHP_EOL, file_get_contents(base_path('../samples/album-images.txt')));
        $this->artistImages = explode(PHP_EOL, file_get_contents(base_path('../samples/artist-images.txt')));
        $this->comments = explode(PHP_EOL, file_get_contents(base_path('../samples/comments.txt')));

        $genres = collect([
            'blues', 'rock', 'country', 'pop', 'cinematic', 'electronic', 'house', 'edm', 'reggae', 'dubstep', 'dance', 'guitar',
            'indie', 'alternative', 'folk', 'jazz', 'metal', 'punk', 'soul', 'metalcore', 'hard rock', 'piano', 'classical', 'funk', 'grunge',
        ]);
        $genres->transform(function($genreName) {
            $filename = slugify($genreName) . '.jpg';
            return ['name' => $genreName, 'image' => "client/assets/images/genres/$filename"];
        });
        $this->genres = app(Genre::class)->insertOrRetrieve($genres);

        $tags = collect(['some', 'demo', 'tags']);
        $this->tags = app(Tag::class)->insertOrRetrieve($tags);
    }

    private function createUsers($artist = false)
    {
        $users = factory(User::class, 50)->make()->toArray();
        $users = array_map(function($user) use($artist) {
            unset($user['display_name'], $user['has_password'], $user['model_type']);

            if ($artist) {
                $user['avatar'] = Arr::random($this->artistImages);
                $user['username'] = Arr::random($this->artistNames);
            } else {
                $name = Str::random();
                $rand = rand(1, 100);
                Storage::disk('public')->put("avatars/$name.jpg", file_get_contents(base_path("../samples/avatars/$rand.jpg")));
                $user['avatar'] = "avatars/$name.jpg";
            }

            return $user;
        }, $users);
        app(User::class)->insert($users);

        $users = app(User::class)->whereIn('username', Arr::pluck($users, 'username'))->get();

        if ($artist) {
            $userProfiles = $users->map(function(User $user) {
                $profile = factory(UserProfile::class)->make();
                $fileName = Str::random();
                $headerNum = rand(1, 15);
                Storage::disk('public')->put("user_header_media/$fileName.jpg", file_get_contents(base_path("../samples/headers/$headerNum.jpg")));
                $profile['header_image'] = "user_header_media/$fileName.jpg";
                $profile['user_id'] = $user->id;
                return $profile;
            });
            DB::table('user_profiles')->insert($userProfiles->toArray());

            $userLinks = $users->map(function(User $user) {
                return [
                    [
                        'user_id' => $user->id,
                        'url' => 'https://facebook.com',
                        'title' => 'Facebook',
                    ],
                    [
                        'user_id' => $user->id,
                        'url' => 'https://twitter.com',
                        'title' => 'Twitter',
                    ],
                    [
                        'user_id' => $user->id,
                        'url' => 'https://bandcamp.com',
                        'title' => 'Bandcamp',
                    ],
                ];
            })->flatten(1);
            app(UserLink::class)->insert($userLinks->toArray());
        }

        return $users;
    }

    private function createTrackWaves($createdTracks)
    {
        foreach ($createdTracks as $track) {
            $rand = rand(1, 4);
            app(Track::class)->getWaveStorageDisk()->put("waves/$track->id.json", file_get_contents(base_path("../samples/waves/$rand.json")));
        }
    }

    private function createTrackComments($createdTracks)
    {
        $comments = [];
        foreach ($createdTracks as $i => $track) {
            $trackComments = factory(Comment::class, 40)->make()->makeVisible(['user_id'])->toArray();
            $trackComments = array_map(function($comment) use($track) {
                unset($comment['depth']);
                $comment['content'] = Arr::random($this->comments);
                $comment['user_id'] = $this->commentUsers->random()->id;
                $comment['created_at'] = Carbon::now()->subHours(rand(0, 50))->toDateTimeString();
                $comment['commentable_id'] = $track->id;
                $comment['commentable_type'] = Track::class;
                return $comment;
            }, $trackComments);
            $comments = array_merge($comments, $trackComments);
        }

        app(Comment::class)->insert($comments);

        Comment::whereNull('path')->get()->each(function(Comment $comment) {
            $comment->generatePath();
        });
    }

    /**
     * @param int $count
     * @param string $tempId
     * @param null $albumId
     * @return array
     */
    private function makeTracks($count, $tempId, $albumId = null)
    {
        $tracks = factory(Track::class, $count)->make();
        return array_map(function($track) use($tempId, $albumId) {
            unset($track['model_type'], $track['created_at_relative']);
            $track['name'] = trim(Arr::random($this->trackNames));
            $track['image'] = Arr::random($this->albumImages);
            $track['temp_id'] = $tempId;
            if ($albumId) {
                $track['album_id'] = $albumId;
            }
            return $track;
        }, $tracks->toArray());
    }

    private function makeAlbums($artistModel)
    {
        $albums = factory(Album::class, 30)->make()->toArray();
        $tempId = Str::random(8);
        $this->albumTempIds[] = $tempId;
        $albums = array_map(function ($album) use($tempId, $artistModel) {
            unset($album['model_type'], $album['created_at_relative']);
            $album['name'] = trim(Arr::random($this->albumNames));
            $album['image'] = Arr::random($this->albumImages);
            $album['temp_id'] = $tempId;
            $album['artist_id'] = $artistModel->id;
            $album['artist_type'] = get_class($artistModel);
            return $album;
        }, $albums);
        return collect($albums)->unique('name')->toArray();
    }

    public function attachGenresToModels($genreables)
    {
        $pivots = collect($genreables)->map(function($genreable) {
            return $this->genres->random(3)->map(function($genre) use($genreable) {
                return ['genre_id' => $genre->id, 'genreable_type' => $genreable['model_type'], 'genreable_id' => $genreable['id']];
            });
        })->flatten(1);
        $this->saveOrUpdate($pivots, 'genreables');
    }

    public function createAdminAccount()
    {
        $user = app(User::class)->firstOrNew(['email' => 'admin@admin.com']);
        $user->username = 'admin';
        $user->password = Hash::make('admin');
        $user->api_token = Str::random(40);
        $user->save();
        $adminPermission = app(Permission::class)->firstOrCreate(
            ['name' => 'admin'],
            [
                'name' => 'admin',
                'group' => 'admin',
                'display_name' => 'Super Admin',
                'description' => 'Give all permissions to user.',
            ]
        );
        $user->permissions()->attach($adminPermission->id);
    }

    private function attachTagsToTracks(Collection $taggables)
    {
        $pivots = collect($taggables)->map(function($taggable) {
            return $this->tags->map(function($tag) use($taggable) {
                return ['tag_id' => $tag->id, 'taggable_type' => $taggable['model_type'], 'taggable_id' => $taggable['id']];
            });
        })->flatten(1);
        $this->saveOrUpdate($pivots, 'taggables');
    }
}
