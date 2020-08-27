<?php

namespace App\Services\Lyrics;

use App\Services\HttpClient;

class RapidApiLyricsProvider
{
    /**
     * @var HttpClient
     */
    private $httpClient;

    public function __construct()
    {
        $key = config('common.site.rapidapi.key');
        $this->httpClient = new HttpClient([
            'headers' => [
                'x-rapidapi-host' => 'mourits-lyrics.p.rapidapi.com',
                'x-rapidapi-key' => $key,
            ]
        ]);
    }

    /**
     * @param string $artistName
     * @param string $trackName
     * @return string
     */
    public function getLyrics($artistName, $trackName)
    {
        $response = $this->httpClient->get(
            "https://mourits-lyrics.p.rapidapi.com/?artist=$artistName&song=$trackName&separator=<br>"
        );

        if (isset($response['result']['lyrics'])) {
            return $response['result']['lyrics'];
        } else {
            return null;
        }
    }
}
