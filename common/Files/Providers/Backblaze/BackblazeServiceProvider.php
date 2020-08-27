<?php

namespace Common\Files\Providers\Backblaze;

use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use Storage;

class BackblazeServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        Storage::extend('backblaze', function ($app, $config) {
            $client = new BackblazeClientWrapper($config['account_id'], $config['application_key']);
            return new Filesystem(new BackblazeAdapter($client, $config['bucket']));
        });
    }

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
