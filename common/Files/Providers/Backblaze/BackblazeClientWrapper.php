<?php

namespace Common\Files\Providers\Backblaze;

use BackblazeB2\Client as BackblazeClient;
use BackblazeB2\Exceptions\NotFoundException;
use League\Flysystem\FileNotFoundException;

class BackblazeClientWrapper
{
    /**
     * @var BackblazeClient
     */
    protected $client;

    /**
     * @var array
     */
    private $constructorParams;

    public function __construct()
    {
        $this->constructorParams = func_get_args();
    }

    /**
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        // only call backblaze API if we're actually doing some operation and not on newing up the class
        if ( ! $this->client) {
            $this->client = new BackblazeClient(...$this->constructorParams);
        }

        try {
            return $this->client->$method(...$parameters);
        } catch (NotFoundException $e) {
            throw new FileNotFoundException($parameters[0]['FileName']);
        }
    }
}
