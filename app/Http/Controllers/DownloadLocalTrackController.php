<?php

namespace App\Http\Controllers;

use App\Track;
use Common\Files\FileEntry;
use Common\Files\Response\FileResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Common\Core\BaseController;
use Common\Settings\Settings;

class DownloadLocalTrackController extends BaseController
{
    /**
     * @var Track
     */
    private $track;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Settings
     */
    private $settings;

    /**
     * @var FileEntry
     */
    private $fileEntry;

    /**
     * @param Track $track
     * @param Request $request
     * @param Settings $settings
     * @param FileEntry $fileEntry
     */
    public function __construct(Track $track, Request $request, Settings $settings, FileEntry $fileEntry)
    {
        $this->track = $track;
        $this->request = $request;
        $this->settings = $settings;
        $this->fileEntry = $fileEntry;
    }

    public function download($id) {
        $track = $this->track->findOrFail($id);

        $this->authorize('download', $track);

        if ( ! $track->url) {
            abort(404);
        }

        preg_match('/.+?\/storage\/track_media\/(.+?\.[a-z0-9]+)/', $track->url, $matches);

        // track is local
        if (isset($matches[1])) {
            $entry = $this->fileEntry->where('file_name', $matches[1])->firstOrFail();

            $ext = pathinfo($track->url, PATHINFO_EXTENSION);
            $trackName = str_replace('%', '', Str::ascii($track->name)).".$ext";
            $entry->name = $trackName;

            return app(FileResponseFactory::class)->create($entry, 'attachment');

        // track is remote
        } else {
            $response = response()->stream(function() use($track) {
                echo file_get_contents($track->url);
            });
            $disposition = $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                "$track->name.mp3",
                str_replace('%', '', Str::ascii("$track->name.mp3"))
            );
            $response->headers->set('Content-Disposition', $disposition);
            return $response;
        }
    }
}
