<?php

namespace Common\Files\Providers\Backblaze;

use League\Flysystem\Config;
use Mhetreramesh\Flysystem\BackblazeAdapter as BaseBackblazeAdapter;

class BackblazeAdapter extends BaseBackblazeAdapter
{
    public function __construct($client, $bucketName)
    {
        $this->client = $client;
        $this->bucketName = $bucketName;
    }

    public function getUrl($path)
    {
        return "https://f001.backblazeb2.com/file/{$this->bucketName}/$path";
    }

    /**
     * {@inheritdoc}
     */
    public function writeStream($path, $resource, Config $config)
    {
        $file = $this->getClient()->upload([
            'BucketName' => $this->bucketName,
            'FileName'   => $path,
            'FileContentType' => $config->get('mimetype'),
            'Body'       => $resource,
        ]);

        return $this->getFileInfo($file);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDir($path)
    {
        // no folders in backblaze, need to specify full path to file
        $path = "$path/$path";
        return $this->getClient()->deleteFile(['FileName' => $path, 'BucketName' => $this->bucketName]);
    }
}
