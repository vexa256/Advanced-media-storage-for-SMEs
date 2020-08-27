<?php

namespace App\Services\Lyrics;

use App\Services\HttpClient;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class AzLyricsProvider
{
    /**
     * @var HttpClient
     */
    private $httpClient;

    private $delimiter = '<!-- Usage of azlyrics.com content by any third-party lyrics provider is prohibited by our licensing agreement. Sorry about that. -->';

    /**
     * @param HttpClient $httpClient
     */
    public function __construct(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @param string $artistName
     * @param string $trackName
     * @return string|void
     */
    public function getLyrics($artistName, $trackName)
    {
        $results = $this->getResults($artistName, $trackName);

        if ( ! empty($results)) {
            $html = $this->httpClient->get($results[0]['link']);
            $crawler = new Crawler($html);

            $result = null;
            $crawler->filter('.main-page .row .text-center div')->each(function(Crawler $node) use(&$result) {
                $text = $node->html();
                if ( ! $result && Str::contains($text, $this->delimiter)) {
                    $result = trim(str_replace($this->delimiter, '', $text));
                }
            });
            return $result;
        }
    }

    private function getResults($artistName, $trackName)
    {
        $html = $this->httpClient->get("https://search.azlyrics.com/search.php?q=$artistName $trackName");
        $crawler = new Crawler($html);

        $results = [];
        $crawler->filter('td')->slice(0, 3)->each(function(Crawler $node) use(&$results) {
            $results[] = [
                'track' => trim(head($node->filter('b')->eq(0)->extract(['_text'])), '"'),
                'artist' => trim(head($node->filter('b')->eq(1)->extract(['_text'])), '"'),
                'link' => head($node->filter('a')->extract(['href'])),
            ];
        });

        return $results;
    }
}
