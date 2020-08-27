<?php

namespace Common\Files\Response;

use Common\Files\FileEntry;

class RemoteFileResponse implements FileResponse
{
    /**
     * @param FileEntry $entry
     * @param array $options
     * @return mixed
     */
    public function make(FileEntry $entry, $options)
    {
        return redirect($entry->getDisk()->url($entry->getStoragePath($options['useThumbnail'])));
    }
}
