<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Identity\Services\SettingsService;
use Illuminate\Database\Seeder;

/** Default company settings (legacy SET defaults). */
class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        app(SettingsService::class)->update([
            'company' => SettingsService::DEFAULT_COMPANY,
            'currency' => SettingsService::DEFAULT_CURRENCY,
            'perm' => [],
        ]);
    }
}
