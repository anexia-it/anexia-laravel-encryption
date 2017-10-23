<?php

namespace Anexia\LaravelEncryption;

use Illuminate\Support\ServiceProvider;

class DatabaseEncryptionServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('db.encryption_service', function ($app) {
            return new EncryptionServiceManager($app);
        });
    }
}