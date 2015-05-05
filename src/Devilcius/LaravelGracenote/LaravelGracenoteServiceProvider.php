<?php

namespace Devilcius\LaravelGracenote;

use Illuminate\Support\ServiceProvider;

class LaravelGracenoteServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            base_path('vendor/devilcius/laravel-gracenote/src/Devilcius/config/config.php') => config_path('gracenote.php'),
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
                base_path('vendor/devilcius/laravel-gracenote/src/Devilcius/config/config.php'), 'gracenote'
        );
    }

}
