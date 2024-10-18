<?php

namespace Ka4ivan\Sluggable\Models\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
//use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use function App\Models\Traits\app;
use function App\Models\Traits\config;

trait HasSlug
{
    /** @var string[] Define in your model */
//    public $slugSourceColumns = ['name'];
//    public $slugGroups = ['uk', 'en'];
//    public $slugDefaultGroup = 'uk';
//    public $slugSeparator = '-';
//    public $slugMaxLength = 255;
//    public $slugGenerateIfEmptySource = true;
//    public $slugMultiGroups = true;

    protected static function bootHasSlug()
    {
//        static::retrieved(function($model) {
//            foreach ($model->appends as $append) {
//                $model->original[$append] = $model->$append;
//                $model->attributes[$append] = $model->$append;
//            }
//        });

//        static::retrieved(function($model) {
//            static::addGlobalScope('slug', function ($builder) use ($model) {
//                $builder->join($model->getSlugTable(), $model->getTable() . '.' . $model->getKeyName(), '=', $model->getSlugTable() . '.model_id')
//                    ->select($model->getTable() . '.*', $model->getSlugTable() . '.value')
//                    ->where($model->getTable() . '.' . $model->getKeyName(), $model->getKey());
//            });
//        });

//        static::addGlobalScope('slug', function ($builder) {
//            $self = new static;
//
//            $builder->join($self->getSlugTable(), $self->getTable() . '.id', '=', $self->getSlugTable() . '.model_id')
//                ->select($self->getTable() . '.*', $self->getSlugTable() . '.value');
//        });

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

    public function slugs(): MorphMany
    {
        return $this->morphMany(self::getSlugModel(), 'model');
    }

    /**
     * @param string|null $group
     * @return MorphOne
     */
    public function slugable(?string $group = null): MorphOne
    {
        return $this->morphOne(self::getSlugModel(), 'model')
            ->where('group', $group ?: $this->getDefaultGroup());
    }

//    protected function slug(): Attribute
//    {
//        return Attribute::make(
//            get: fn () => $this->slug($this->getDefaultGroup())->value('value'),
//        );
//    }

    protected function getSlugAttribute(): string|null
    {
        return $this->slugable?->value;
    }

    public function getSlugSourceColumns(): array
    {
        if (empty($this->slugSourceColumns)) {
            return config('slug.source_columns');
        }

        return $this->slugSourceColumns;
    }

    public function getSlugSeparator(): string
    {
        if (empty($this->slugSeparator)) {
            return config('slug.slug_separator', '-');
        }

        return $this->slugSeparator;
    }

    public function getSlugMaxLength(): int
    {
        if (empty($this->slugMaxLength)) {
            return config('slug.max_length', 255);
        }

        return $this->slugMaxLength;
    }

    public function getSlugGroups(): array
    {
        if (!(isset($this->slugGroups) && is_array($this->slugGroups))) {
            return config('slug.groups.list', ['uk', 'en']);
        }

        return $this->slugGroups;
    }

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

    public function isSlugGenerateIfEmptySource(): bool
    {
        if (isset($this->slugGenerateIfEmptySource)) {
            return $this->slugGenerateIfEmptySource;
        }

        return config('slug.generate_if_empty_source', true);
    }

    public function isSlugMultiGroups(): bool
    {
        if (isset($this->slugMultiGroups)) {
            return $this->slugMultiGroups;
        }

        return config('slug.groups.active', false);
    }

    public function makeSlugRawStr(): string
    {
        return implode($this->getSlugSeparator(), $this->only($this->getSlugSourceColumns()));
    }

    public static function slugGenerate(string $slug, ?Model $model = null, string $group = null)
    {
        $model = $model ?: new static();
        $nonUniqueSlug = static::makeNonUniqueSlug($slug, $model);

        return $model->makeUniqueSlug($nonUniqueSlug, $model, $group);
    }

    protected static function makeNonUniqueSlug(string $slug, $model): string
    {
        return Str::slug(static::getClippedSlugWithPrefixSuffix($slug, $model), $model->getSlugSeparator(), App::getLocale());
    }

    protected static function getClippedSlugWithPrefixSuffix(string $slug, $model): string
    {
        return Str::limit($slug, $model->getSlugMaxLength(), '');
    }

    protected function makeUniqueSlug(string $slug, $model, string $group = null): string
    {
        $originalSlug = $slug;
        $i = 1;
        while (static::otherRecordExistsWithSlug($slug, $model, $group) || $slug === '') {
            $slug = $originalSlug . $this->getSlugSeparator() . $i++;
        }

        return $slug;
    }

    protected static function otherRecordExistsWithSlug(string $slug, $model, string $group = null): bool
    {
        $query = self::getSlugModel()->where('value', $slug);

        if (!config('slug.unique_for_all_models', false)) {
            $query->where('model_type', $model->getMorphClass());
        }

        if (!config('slug.unique_for_groups', false) && $model->isSlugMultiGroups()) {
                $query->where('group', $group);
        }

        $query->whereNot(function ($q) use ($model, $group) {
            $q->where('model_id', $model->getKey())
                ->where('model_type', $model->getMorphClass())
                ->when($group && $model->isSlugMultiGroups(), fn($q2) => $q2->where('group', $group));
        });

        return $query->exists();
    }

    public function scopeWhereSlug(Builder $query, $value, string $method = 'whereHas', string $operator = '=')
    {
        return $query->{$method}('slugs', function (Builder $query) use ($value, $operator) {
            $query->where(self::getSlugTable().'.value', $operator, $value);
        });
    }

    protected static function getSlugModel()
    {
        return config('slug.model', \Ka4ivan\Sluggable\Models\Slug::class);
    }

    protected static function getSlugTable(): string
    {
        return app()->make(self::getSlugModel())->getTable();
    }
}
