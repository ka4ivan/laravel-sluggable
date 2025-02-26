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
