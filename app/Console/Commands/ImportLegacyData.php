<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Vehicle\Actions\ImportVehiclesFromExcelAction;
use App\Domain\Vehicle\Services\LegacyImporter;
use Illuminate\Console\Command;

class ImportLegacyData extends Command
{
    protected $signature = 'fleet:import-legacy
        {--seed= : Path to legacy seed.json}
        {--db= : Path to legacy SQLite DB (fleet-v2.db)}
        {--path= : Path to import file (.xlsx, .json, .db — autodetected)}
        {--dry-run : Validate without writing to the database}';

    protected $description = 'Import legacy fleet data from V2 Excel (.xlsx), seed.json and/or SQLite DB';

    public function handle(LegacyImporter $importer, ImportVehiclesFromExcelAction $excelImporter): int
    {
        $defaultSeed = base_path('../fleet-tracker/seed.json');
        $defaultDb = base_path('../fleet-tracker/data/fleet-v2.db');

        $pathOption = $this->option('path') ? (string) $this->option('path') : null;
        $dryRun = (bool) $this->option('dry-run');

        if ($pathOption) {
            return $this->importPath($pathOption, $importer, $excelImporter, $dryRun);
        }

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
            $this->warn('Nothing to import. Pass --path=, --seed= and/or --db= with explicit paths.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    protected function importPath(string $path, LegacyImporter $importer, ImportVehiclesFromExcelAction $excelImporter, bool $dryRun): int
    {
        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($ext === 'xlsx' || $ext === 'xls') {
            $this->info("Importing V2 Excel: {$path}".($dryRun ? ' (dry-run)' : ''));
            $result = $excelImporter->execute($path, $dryRun, function () {
                $this->output->write('.');
            });
            $this->newLine();
            $this->info("Excel: imported {$result['imported']}, skipped {$result['skipped']}");
            foreach (array_slice($result['errors'], 0, 20) as $error) {
                $this->line("  - {$error}");
            }
            if (count($result['errors']) > 20) {
                $this->line('  ... and '.(count($result['errors']) - 20).' more errors.');
            }

            return self::SUCCESS;
        }

        if ($ext === 'json') {
            $this->info("Importing seed JSON: {$path}");
            $result = $importer->importFromSeedJson($path);
            $this->info("Seed: imported {$result['imported']}, skipped {$result['skipped']}");

            return self::SUCCESS;
        }

        if (in_array($ext, ['db', 'sqlite', 'sqlite3'], true)) {
            $this->info("Importing SQLite DB: {$path}");
            $result = $importer->importFromSqlite($path);
            $this->info("SQLite: imported {$result['imported']}, skipped {$result['skipped']}");

            return self::SUCCESS;
        }

        $this->error("Unsupported file type [.{$ext}]. Use .xlsx, .json or .db.");

        return self::FAILURE;
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
