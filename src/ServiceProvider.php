<?php

namespace Ka4ivan\Sluggable;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/slug.php' => config_path('slug.php'),
        ]);

        if (! class_exists('CreateSlugTable')) {
            $this->publishes([
                __DIR__.'/../database/migrations/create_slugs_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time()).'_create_slugs_table.php'),
            ], 'laravel-slug-migrations');
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/slug.php', 'slug');
    }
}