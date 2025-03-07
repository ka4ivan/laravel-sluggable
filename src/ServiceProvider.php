<?php

namespace Ka4ivan\Sluggable;

use Illuminate\Support\Facades\Route;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/slug.php' => config_path('slug.php'),
        ]);

        if (! class_exists('CreateSlugTable')) {
            $this->publishes([
                __DIR__.'/../database/migrations/create_slugs_table.php.stub' => database_path('migrations/0002_02_02_000001_create_slugs_table.php'),
            ], 'laravel-slug-migrations');
        }

        Route::bind('slug', function ($value) {
            $modelClass = config('slug.model', \Ka4ivan\Sluggable\Models\Slug::class);

            return $modelClass::whereHas('slugable', function ($query) use ($value) {
                $query->where('value', $value);
            })->firstOrFail();
        });
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/slug.php', 'slug');

        $this->commands([
            Console\SlugGenerateCommand::class,
        ]);
    }
}