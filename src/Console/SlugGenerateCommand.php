<?php

namespace Ka4ivan\Sluggable\Console;

use Illuminate\Console\Command;

class SlugGenerateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'slug:generate
                                    {--force=false : Generate slugs with force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Slug Generate For All Models.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $force = filter_var($this->option('force'), FILTER_VALIDATE_BOOLEAN);
        $models = config('slug.models', []);

        if ($force) {
            config('slug.model', \Ka4ivan\Sluggable\Models\Slug::class)::truncate();
        }

        foreach ($models as $modelNamespace) {
            $modelNamespace::chunk(100, function ($records) use ($modelNamespace) {
                $model = new $modelNamespace;
                foreach ($records as $record) {
                    if (is_null($record->slug)) {
                        if ($record->isSlugMultiGroups()) {
                            $groups = $model->getSlugGroups();

                            foreach ($groups as $group) {
                                $record->slugs()->create([
                                    'value' => $record::slugGenerate($record->makeSlugRawStr(), $model, $group),
                                    'group' => $group,
                                ]);
                            }

                        } else {
                            $record->slugs()->create(['value' => $record::slugGenerate($record->makeSlugRawStr(), $model)]);
                        }
                    }
                }
            });
        }

        $this->info(sprintf('Slugs successfully generated!'));
    }
}
