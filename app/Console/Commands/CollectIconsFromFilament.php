<?php

namespace App\Console\Commands;

use BladeUI\Icons\Exceptions\SvgNotFound;
use BladeUI\Icons\Factory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class CollectIconsFromFilament extends Command
{
    protected $signature = 'icons:collect:filament {base_path}
     {--replace : Replace the custom icons collection in that project with the collected ones.}
     {--output= : Export to this directory, either relative to storage/export or an absolute path to another Laravel project.}
     {--exclude=heroicon : Exclude these collections from the generated one, use the prefix comma-separated.}';

    protected $description = 'Gather all specified icons to a specific folder.';

    public function handle()
    {
        $dir = $this->argument('base_path');
        $excluded = collect(explode(',', $this->option('exclude')))->filter();

        $sets = collect(app(Factory::class)->all())
            ->pluck('prefix')
            ->diff($excluded)
            ->implode('|');
        $grep_command = "grep -hroE '\<($sets)-[a-zA-Z0-9-]+\>' --color=never $dir/app/Filament $dir/resources/views";
        // -h: Does not report the file
        // -o: output only the matched string, not the whole line
        // -E: use regex on Mac OS
        $icons = Process::fromShellCommandline($grep_command)->mustRun()->getOutput();
        $icons = array_filter(explode("\n", $icons));

        if ($this->option('replace')) {
            $path = $dir . '/resources/svg';
        } else {
            $path = $this->option('output') ?: now()->format('YmdHis');
        }
        if (!str($path)->startsWith('/')) {
            $storage = Storage::disk('export');
            $storage->makeDirectory($path);
            $path = $storage->path($path);
        }
        File::cleanDirectory($path);

        foreach ($icons as $icon) {
            try {
                $svg = svg($icon);
                file_put_contents($path . '/' . $icon . '.svg', $svg->contents());
            } catch (SvgNotFound $e) {
                $this->error("'$icon': " . $e->getMessage());
            }
        }

        $this->info("Done! Exported " . count($icons) . " SVGs to '$path'");
        $this->info("Excluded: " . $excluded->implode('|'));
    }
}
