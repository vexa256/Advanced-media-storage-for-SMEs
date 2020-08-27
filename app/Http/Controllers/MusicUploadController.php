<?php

namespace App\Http\Controllers;

use App\Album;
use App\Artist;
use App\Services\Artists\NormalizesArtist;
use Carbon\Carbon;
use Common\Core\BaseController;
use Common\Files\Actions\CreateFileEntry;
use Common\Files\Actions\UploadFile;
use Common\Files\FileEntry;
use Common\Files\Traits\GetsEntryTypeFromMime;
use getID3;
use getid3_lib;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class MusicUploadController extends BaseController
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

    public function upload()
    {
        $this->authorize('store', FileEntry::class);

        $this->validate($this->request, [
            'file' => 'required|file'
        ]);

        $fileEntry = $this->storePublicFile();


        return $this->success(['fileEntry' => $fileEntry, 'metadata' => $normalizedMetadata], 201);
    }

    /**
     * @return FileEntry
     */
    private function storePublicFile()
    {
        $uploadFile = $this->request->file('file');
        $params = $this->request->all();
        $params['diskPrefix'] = 'track_media';

        return app(UploadFile::class)->execute('public', $uploadFile, $params);
    }
}
