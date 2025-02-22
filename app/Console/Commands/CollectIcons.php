<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CollectIcons extends Command
{
    protected $signature = 'icons:collect {names} {--dir=}';

    protected $description = 'Gather all specified icons to a specific folder.';

    public function handle()
    {
        $names = collect(explode(',', $this->argument('names')))->filter();

        $storage = Storage::disk('export');
        $path = $this->option('dir') ?: now()->format('YmdHis');
        $storage->deleteDirectory($path);

        foreach ($names as $name) {
            $svg = svg($name);
            $storage->put($path . '/' . $name . '.svg', $svg->contents());
        }

        $this->info("Done! Exported " . $names->count() . " icons to '$path'");
    }
}
