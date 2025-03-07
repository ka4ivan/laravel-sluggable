<?php

namespace Ka4ivan\Sluggable\Models\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

trait HasSlugs
{
/**     @var string[] Define in your model      */
//    public $slugSourceColumns = ['name'];
//    public $slugGroups = ['uk', 'en'];
//    public $slugDefaultGroup = 'uk';
//    public $slugSeparator = '-';
//    public $slugMaxLength = 255;
//    public $slugGenerateIfEmptySource = true;
//    public $slugMultiGroups = true;


    /**
     * Boot the trait to handle slug generation on model events.
     * It automatically creates slugs when a model is created and deletes them when a model is deleted.
     */
    protected static function bootHasSlugs()
    {
        static::created(function ($model) {
            $self = new static;

            if (!$model->slugs()->count() && (!empty($model->makeSlugRawStr()) || $model->isSlugGenerateIfEmptySource())) {
                if ($model->isSlugMultiGroups()) {
                    $groups = $self->getSlugGroups();

                    foreach ($groups as $group) {
                        $model->slugs()->create([
                            'value' => self::slugGenerate($model->makeSlugRawStr(), $model, $group),
                            'group' => $group,
                        ]);
                    }
                } else {
                    $model->slugs()->create(['value' => self::slugGenerate($model->makeSlugRawStr(), $model)]);
                }
            }
        });

        self::deleting(function ($model) {
            $model->slugs()->delete();
        });
    }

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

    /**
     * Accessor for the slug attribute.
     *
     * @return string|null
     */
    protected function getSlugAttribute(): string|null
    {
        return $this->slugable?->value;
    }

    /**
     * Resolve model binding for routes.
     * Supports searching by ID or slug.
     *
     * @param mixed $value The value used for lookup
     * @param string|null $field The field to search by
     * @return Model|null
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        if ($field !== 'slug') {
            return parent::resolveRouteBinding($value, $field);
        }

        return $this->whereHas('slugable', function ($query) use ($value) {
            $query->where('value', $value);
        })->first();
    }

    /**
     * Get the columns used to generate the slug.
     *
     * @return array
     */
    public function getSlugSourceColumns(): array
    {
        if (empty($this->slugSourceColumns)) {
            return config('slug.source_columns');
        }

        return $this->slugSourceColumns;
    }

    /**
     * Get the separator used in the slug.
     *
     * @return string
     */
    public function getSlugSeparator(): string
    {
        if (empty($this->slugSeparator)) {
            return config('slug.slug_separator', '-');
        }

        return $this->slugSeparator;
    }

    /**
     * Get the maximum allowed length for the slug.
     *
     * @return int
     */
    public function getSlugMaxLength(): int
    {
        if (empty($this->slugMaxLength)) {
            return config('slug.max_length', 255);
        }

        return $this->slugMaxLength;
    }

    /**
     * Get the available slug groups.
     *
     * @return array
     */
    public function getSlugGroups(): array
    {
        if (!(isset($this->slugGroups) && is_array($this->slugGroups))) {
            return config('slug.groups.list', ['uk', 'en']);
        }

        return $this->slugGroups;
    }

    /**
     * Get the default slug group.
     *
     * @return string|null
     */
    public function getDefaultGroup(): string|null
    {
        if (!$this->isSlugMultiGroups()) {
            return null;
        }

        if (empty($this->slugDefaultGroup)) {
            return Arr::first($this->getSlugGroups()) ?: config('slug.groups.default', App::getLocale());
        }

        return $this->slugDefaultGroup;
    }

    /**
     * Determine if a slug should be generated when no source value exists.
     *
     * @return bool
     */
    public function isSlugGenerateIfEmptySource(): bool
    {
        if (isset($this->slugGenerateIfEmptySource)) {
            return $this->slugGenerateIfEmptySource;
        }

        return config('slug.generate_if_empty_source', true);
    }

    /**
     * Determine if multiple slug groups are enabled.
     *
     * @return bool
     */
    public function isSlugMultiGroups(): bool
    {
        if (isset($this->slugMultiGroups)) {
            return $this->slugMultiGroups;
        }

        return config('slug.groups.active', false);
    }

    /**
     * Create a raw string for slug generation.
     *
     * @return string
     */
    public function makeSlugRawStr(): string
    {
        return implode($this->getSlugSeparator(), $this->only($this->getSlugSourceColumns()));
    }

    /**
     * Generate a unique slug.
     *
     * @param string $slug
     * @param Model|null $model
     * @param string|null $group
     * @return string
     */
    public static function slugGenerate(string $slug, ?Model $model = null, string $group = null)
    {
        $model = $model ?: new static();
        $nonUniqueSlug = static::makeNonUniqueSlug($slug, $model);

        return $model->makeUniqueSlug($nonUniqueSlug, $model, $group);
    }

    /**
     * Convert a raw slug to a slug format.
     */
    protected static function makeNonUniqueSlug(string $slug, $model): string
    {
        return Str::slug(static::getClippedSlugWithPrefixSuffix($slug, $model), $model->getSlugSeparator(), App::getLocale());
    }

    /**
     * Clips the slug to the maximum allowed length defined in the model.
     *
     * @param string $slug
     * @param Model $model
     * @return string
     */
    protected static function getClippedSlugWithPrefixSuffix(string $slug, $model): string
    {
        return Str::limit($slug, $model->getSlugMaxLength(), '');
    }

    /**
     * Generates a unique slug by appending a numerical index if the slug already exists.
     *
     * @param string $slug
     * @param Model $model
     * @param string|null $group
     * @return string
     */
    protected function makeUniqueSlug(string $slug, $model, string $group = null): string
    {
        $originalSlug = $slug;
        $i = 1;
        while (static::otherRecordExistsWithSlug($slug, $model, $group) || $slug === '') {
            $slug = $originalSlug . $this->getSlugSeparator() . $i++;
        }

        return $slug;
    }

    /**
     * Checks whether a given slug already exists in the database.
     *
     * @param string $slug
     * @param Model $model
     * @param string|null $group
     * @return bool
     */
    protected static function otherRecordExistsWithSlug(string $slug, $model, string $group = null): bool
    {
        $query = self::getSlugModel()::where('value', $slug);

        if (!config('slug.unique_for_all_models', false)) {
            $query->where('model_type', $model->getMorphClass());
        }

        if (!config('slug.groups.unique', false) && $model->isSlugMultiGroups()) {
            $query->where('group', $group);
        }

        $query->whereNot(function ($q) use ($model, $group) {
            $q->where('model_id', $model->getKey())
                ->where('model_type', $model->getMorphClass())
                ->when($group && $model->isSlugMultiGroups(), fn($q2) => $q2->where('group', $group));
        });

        return $query->exists();
    }

    /**
     * Adds a filter by slug to the query.
     *
     * @param Builder $query
     * @param string $value
     * @param string $method
     * @param string $operator
     * @return Builder
     */
    public function scopeWhereSlug(Builder $query, $value, string $method = 'whereHas', string $operator = '=')
    {
        return $query->{$method}('slugs', function (Builder $query) use ($value, $operator) {
            $query->where(self::getSlugTable().'.value', $operator, $value);
        });
    }

    /**
     * Retrieves the model class used for storing slugs.
     *
     * @return string
     */
    protected static function getSlugModel()
    {
        return config('slug.model', \Ka4ivan\Sluggable\Models\Slug::class);
    }

    /**
     * Retrieves the name of the table used for storing slugs.
     *
     * @return string
     */
    protected static function getSlugTable(): string
    {
        return app()->make(self::getSlugModel())->getTable();
    }
}
