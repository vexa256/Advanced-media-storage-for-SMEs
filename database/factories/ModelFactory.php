<?php

use App\Album;
use App\Track;
use App\TrackPlay;
use App\User;
use App\UserProfile;
use Common\Comments\Comment;
use Faker\Generator;
use Illuminate\Database\Eloquent\Factory;
use Jenssegers\Agent\Agent;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

/** @var Factory $factory */
$factory->define(App\User::class, function (Faker\Generator $faker) {
    static $password;

    return [
        'first_name' => $faker->firstName,
        'last_name' => $faker->lastName,
        'username' => $faker->userName,
        'language' => $faker->languageCode,
        'country' => $faker->country,
        'timezone' => $faker->timezone,
        'email' => $faker->unique()->safeEmail,
        'password' => $password ?: $password = bcrypt('secret'),
        'remember_token' => \Str::random(10),
    ];
});

$factory->define(UserProfile::class, function (Faker\Generator $faker) {
    return [
        'country' => $faker->country,
        'city' => $faker->country,
        'description' => $faker->realText(),
    ];
});

$factory->define(Track::class, function (Faker\Generator $faker) {
    $sampleNumber = rand(1,10);
    return [
        'name' => $faker->words(rand(2, 5), true),
        'number' => rand(1, 10),
        'duration' => 323000 + rand(1, 1000),
        'local_only' => true,
        'image' => $faker->imageUrl(240, 240),
        'url' => "storage/samples/{$sampleNumber}.mp3",
        'created_at' => $faker->dateTimeBetween('-1 year', 'now'),
        'description' => $faker->text(750) . "\n\n Visit: demo-url.com
Visit my bandcamp: demo.bandcamp.com
See me on instagram: www.instagram.com/demo
Read me on twitter: www.twitter.com/demo"
    ];
});

$factory->define(Album::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->words(rand(2, 5), true),
        'release_date' => $faker->dateTimeBetween('-4 months', 'now')->format('Y-m-d'),
        'image' => $faker->imageUrl(240, 240),
        'local_only' => true,
        'created_at' => $faker->dateTimeBetween('-4 months', 'now'),
    ];
});

$factory->define(Comment::class, function (Faker\Generator $faker) {
    return [
        'content' => $faker->realText(),
        'commentable_type' => Track::class,
        'position' => $faker->numberBetween(0, 100),
    ];
});


$factory->define(TrackPlay::class, function (Faker\Generator $faker) {
    return [
        'created_at' => $faker->dateTimeBetween('-4 months', 'now'),
        'platform' => array_random(array_keys(Agent::getPlatforms())),
        'device' => array_random(['mobile', 'tablet', 'desktop']),
        'browser' => array_random(array_keys(Agent::getBrowsers())),
        'location' => $faker->countryCode,
    ];
});
