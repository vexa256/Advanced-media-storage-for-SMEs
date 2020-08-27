<?php

namespace App\Services\Tracks;

use App\Album;
use App\Artist;
use App\Services\Artists\NormalizesArtist;
use Carbon\Carbon;
use Common\Files\Actions\CreateFileEntry;
use Common\Files\Actions\UploadFile;
use Common\Files\FileEntry;
use Common\Files\Traits\GetsEntryTypeFromMime;
use Illuminate\Http\Request;
use Arr;
use Str;
use getID3;
use getid3_lib;

class TrackUploadResponseTransformer
{
    use GetsEntryTypeFromMime, NormalizesArtist;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Artist
     */
    private $artist;

    /**
     * @var Album
     */
    private $album;

    /**
     * @param Request $request
     * @param Artist $artist
     * @param Album $album
     */
    public function __construct(Request $request, Artist $artist, Album $album)
    {
        $this->request = $request;
        $this->artist = $artist;
        $this->album = $album;
    }

    /**
     * @param array $response
     * @return array
     */
    public function transform($response)
    {
        /** @var FileEntry $fileEntry */
        $fileEntry = $response['fileEntry'];

        if ( ! $fileEntry) {
            return $response;
        }

        $autoMatch = filter_var($this->request->get('autoMatch'), FILTER_VALIDATE_BOOLEAN);

        $getID3 = new getID3;
        $metadata = $getID3->analyze(
            null, $fileEntry->file_size, $fileEntry->name, $fileEntry->getDisk()->readStream($fileEntry->getStoragePath())
        );
        getid3_lib::CopyTagsToComments($metadata);

        $normalizedMetadata = array_map(function($item) {
            return $item && is_array($item) ? Arr::first($item) : $item;
        }, Arr::except(Arr::get($metadata, 'comments', []), 'music_cd_identifier'));

        // store thumbnail
        if (isset($normalizedMetadata['picture'])) {
            $normalizedMetadata = $this->storeMetadataPicture($normalizedMetadata);
        }

        if (isset($metadata['playtime_seconds'])) {
            $normalizedMetadata['duration'] = floor($metadata['playtime_seconds']) * 1000;
        }

        if (isset($normalizedMetadata['genre'])) {
            $normalizedMetadata['genres'] = explode(',', $normalizedMetadata['genre']);
            unset($normalizedMetadata['genre']);
        }

        if (isset($normalizedMetadata['artist'])) {
            $normalizedMetadata['artist_name'] = $normalizedMetadata['artist'];
            unset($normalizedMetadata['artist']);
            if ($autoMatch) {
                $normalizedMetadata['artist'] = $this->artist->firstOrCreate(['name' => $normalizedMetadata['artist_name']]);
                if ($normalizedMetadata['artist']) {
                    $normalizedMetadata['artist'] = $this->normalizeArtist($normalizedMetadata['artist']);
                }
            }
        }

        if (isset($normalizedMetadata['album'])) {
            $normalizedMetadata['album_name'] = $normalizedMetadata['album'];
            unset($normalizedMetadata['album']);
            if ($autoMatch) {
                $normalizedMetadata['album'] = $this->album->where('name', $normalizedMetadata['album_name'])->first();
            }
        }

        if (isset($normalizedMetadata['date'])) {
            $normalizedMetadata['release_date'] = Carbon::parse($normalizedMetadata['date'])->toDateString();
            unset($normalizedMetadata['date']);
        }

        if ( ! isset($normalizedMetadata['title'])) {
            $name = pathinfo($fileEntry->name, PATHINFO_FILENAME);
            $normalizedMetadata['title'] = Str::title($name);
        }

        $response['metadata'] = $normalizedMetadata;
        return $response;
    }

    /**
     * @param array $normalizedMetadata
     * @return array
     */
    private function storeMetadataPicture($normalizedMetadata)
    {
        $mime = $normalizedMetadata['picture']['image_mime'];
        $fileData = [
            'name' => 'thumbnail.png',
            'file_name' => Str::random(40),
            'mime' => $mime,
            'type' => $this->getTypeFromMime($mime),
            'file_size' => $normalizedMetadata['picture']['datalength'],
            'extension' => last(explode('/', $mime)),
        ];

        $params = ['diskPrefix' => 'track_image_media'];
        $fileEntry = app(CreateFileEntry::class)->execute($fileData, $params);
        app(UploadFile::class)->execute('public', $normalizedMetadata['picture']['data'], $params, $fileEntry);
        unset($normalizedMetadata['picture']);
        $normalizedMetadata['image'] = $fileEntry;
        return $normalizedMetadata;
    }
}
