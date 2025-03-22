# Model Sluggable (slugs) for Laravel Framework

[![License](https://img.shields.io/packagist/l/ka4ivan/laravel-sluggable.svg?style=for-the-badge)](https://packagist.org/packages/ka4ivan/laravel-sluggable)
[![Build Status](https://img.shields.io/github/stars/ka4ivan/laravel-sluggable.svg?style=for-the-badge)](https://github.com/ka4ivan/laravel-sluggable)
[![Latest Stable Version](https://img.shields.io/packagist/v/ka4ivan/laravel-sluggable.svg?style=for-the-badge)](https://packagist.org/packages/ka4ivan/laravel-sluggable)
[![Total Downloads](https://img.shields.io/packagist/dt/ka4ivan/laravel-sluggable.svg?style=for-the-badge)](https://packagist.org/packages/ka4ivan/laravel-sluggable)

## 📖 Table of Contents
- [Installation](#installation)
- [Usage](#usage)
    - [Preparing your model](#preparing-your-model)
    - [Base relationships](#base-relationships)
    - [Route binding](#route-binding)
    - [Base model usage](#base-model-usage)
      - [Individual settings for the model](#individual-settings-for-the-model)
      - [Search by slug](#search-by-slug)
    - [Slug generate command](#slug-generate-command)

## Installation

1) Require this package with composer
```shell
composer require ka4ivan/laravel-sluggable
```

2) Publish package resource:
```shell
php artisan vendor:publish --provider="Ka4ivan\Sluggable\ServiceProvider"
```
- config
- migration

#### This is the default content of the config file:
```php
<?php

return [

    /**
     * Models for which slugs will be created using the command
     */
    'models' => [
//        \App\Models\Page::class,
//        \App\Models\Product::class,
    ],

    /**
     * Slug model
     */
    'model' => \Ka4ivan\Sluggable\Models\Slug::class,

    /**
     * What attributes do we use to build the slug?
     * This can be a single field, like "name" which will build a slug from:
     *
     *     $model->name;
     *
     * Or it can be an array of fields, like ["name", "company"], which builds a slug from:
     *
     *     $model->name . ' ' . $model->company;
     */
    'source_columns' => ['name'],

    /**
     * The sign with which the slag will be divided
     */
    'slug_separator' => '-',

    /**
     * The maximum length of the slug
     */
    'max_length' => 255,

    /**
     * Do you need to generate a slug if the 'source_columns' is empty?
     */
    'generate_if_empty_source' => true,

    /**
     * Is the slug unique among all models?
     */
    'unique_for_all_models' => false,

    'groups' => [

        /**
         * Do you need groups for slugs (multilingualism, etc.)?
         */
        'active' => false,

        /**
         * Is the slug unique in groups of one model?
         */
        'unique' => false,

        'list' => ['uk', 'en', 'es'],

        'default' => 'en',
    ],
];
```

3) Run migration:
```shell
php artisan migrate
```

## Usage

### Preparing your model

To associate slugs with a model, the model must implement the following trait: `HasSlugs`.
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Ka4ivan\ModelReleases\Models\Traits\HasReleases;

class Article extends Model
{
    use HasSlugs;
}
```

### Base relationships
```php
/**
 * Define a morph many relationship for slugs.
 */
public function slugs(): MorphMany
{
    return $this->morphMany(self::getSlugModel(), 'model');
}

/**
 * Define a morph one relationship for the main slug.
 *
 * @param string|null $group The group of the slug (optional)
 * @return MorphOne
 */
public function slugable(?string $group = null): MorphOne
{
    return $this->morphOne(self::getSlugModel(), 'model')
        ->where('group', $group ?: $this->getDefaultGroup());
}
```

### Route binding
You can use route binding if you wish.

```php
// Routes
Route::get('aricles/{aricle:slug}', [\App\Http\Client\Api\Controllers\ArticleController::class, 'show']);

// Controller
public function show(Request $request, Article $article)
{
    return ArticleResource::make($article);
}
```

### Base model usage

#### Individual settings for the model
By default, all settings are taken from the `slug` config. However, each model can be configured separately if necessary.
```php
/**     @var string[] Define in your model      */
//    public $slugSourceColumns = ['name'];
//    public $slugGroups = ['uk', 'en'];
//    public $slugDefaultGroup = 'uk';
//    public $slugSeparator = '-';
//    public $slugMaxLength = 255;
//    public $slugGenerateIfEmptySource = true;
//    public $slugMultiGroups = true;
```


#### Search by slug
```php
Article::whereSlug('article-1')->first()
```

### Slug generate command
```shell
php artisan slug:generate
```

Or with an additional `force` parameter to regenerate all slugs, even for models that already had them before.

```shell
php artisan slug:generate --force=true
```
