<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Vehicle\Services\LegacyImporter;
use Illuminate\Console\Command;

class ImportLegacyData extends Command
{
    protected $signature = 'fleet:import-legacy
        {--seed= : Path to legacy seed.json}
        {--db= : Path to legacy SQLite DB (fleet-v2.db)}';

    protected $description = 'Import legacy fleet data from seed.json and/or SQLite DB';

    public function handle(LegacyImporter $importer): int
    {
        $defaultSeed = base_path('../fleet-tracker/seed.json');
        $defaultDb = base_path('../fleet-tracker/data/fleet-v2.db');

        $seed = $this->option('seed') ?: $defaultSeed;
        $db = $this->option('db') ?: $defaultDb;

        $didAnything = false;

        if ($seed && is_file((string) $seed)) {
            $this->info("Importing seed JSON: {$seed}");
            $rows = $this->countSeedRows((string) $seed);
            $bar = $rows > 0 ? $this->output->createProgressBar($rows) : null;
            $bar?->start();
            $result = $importer->importFromSeedJson((string) $seed, fn () => $bar?->advance());
            $bar?->finish();
            $this->newLine();
            $this->info("Seed: imported {$result['imported']}, skipped {$result['skipped']}");
            $didAnything = true;
        } elseif ($this->option('seed')) {
            $this->error("Seed file not found: {$seed}");

            return self::FAILURE;
        } else {
            $this->line("Seed file not found at default path ({$defaultSeed}), skipping.");
        }

        if ($db && is_file((string) $db)) {
            $this->info("Importing SQLite DB: {$db}");
            $result = $importer->importFromSqlite((string) $db, function () {
                // indeterminate progress; advance a spinner via output write
            });
            $this->info("SQLite: imported {$result['imported']}, skipped {$result['skipped']}");
            $didAnything = true;
        } elseif ($this->option('db')) {
            $this->error("SQLite DB not found: {$db}");

            return self::FAILURE;
        } else {
            $this->line("SQLite DB not found at default path ({$defaultDb}), skipping.");
        }

        if (! $didAnything) {
            $this->warn('Nothing to import. Pass --seed= and/or --db= with explicit paths.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    protected function countSeedRows(string $path): int
    {
        try {
            $rows = json_decode((string) file_get_contents($path), true);

            return is_array($rows) ? count($rows) : 0;
        } catch (\Throwable) {
            return 0;
        }
    }
}
