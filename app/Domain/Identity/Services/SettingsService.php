<?php

declare(strict_types=1);

namespace App\Domain\Identity\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Company settings (company, currency, per-transition permissions).
 *
 * Mirrors the legacy server.js SET object ({company, currency, perm}) which
 * was persisted as one JSON row in the settings table. Here each concern is
 * stored as its own key/value row; the merged result is cached forever.
 */
class SettingsService
{
    public const CACHE_KEY = 'fleet.settings';

    public const DEFAULT_COMPANY = 'RASD Tech';

    public const DEFAULT_CURRENCY = 'USD';

    /**
     * Fallback per-transition permission catalogue (legacy server.js T).
     * Used until a TransitionRegistry class exists.
     *
     * @var array<string, array{label: string, roles: string[], notify: string[]}>
     */
    public const FALLBACK_TRANSITIONS = [
        'ship' => ['label' => 'Register shipment', 'roles' => ['purchaser', 'tracker'], 'notify' => ['tracker']],
        'arrive' => ['label' => 'Confirm arrival at port', 'roles' => ['tracker'], 'notify' => ['customs']],
        'clear1' => ['label' => 'Start customs clearance', 'roles' => ['customs'], 'notify' => []],
        'clear2' => ['label' => 'Complete customs clearance', 'roles' => ['customs'], 'notify' => ['warehouse']],
        'receive' => ['label' => 'Receive into Warehouse / Showroom', 'roles' => ['warehouse'], 'notify' => ['sales']],
        'sell' => ['label' => 'Issue sale order', 'roles' => ['sales'], 'notify' => ['warehouse', 'admin']],
        'deliver' => ['label' => 'Deliver to buyer', 'roles' => ['warehouse'], 'notify' => ['admin', 'sales']],
    ];

    /**
     * @return array{company: string, currency: string, perm: array<string, array{roles: string[], notify: string[]}>}
     */
    public function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            $stored = $this->stored();

            return [
                'company' => (string) ($stored['company'] ?? self::DEFAULT_COMPANY),
                'currency' => (string) ($stored['currency'] ?? self::DEFAULT_CURRENCY),
                'perm' => is_array($stored['perm'] ?? null) ? $stored['perm'] : [],
            ];
        });
    }

    public function getCompany(): string
    {
        return $this->all()['company'];
    }

    public function getCurrency(): string
    {
        return $this->all()['currency'];
    }

    /** @return array<string, array{roles: string[], notify: string[]}> */
    public function getTransitionPermissions(): array
    {
        return $this->all()['perm'];
    }

    /**
     * @param  array{company?: string, currency?: string, perm?: array}  $data
     */
    public function update(array $data): void
    {
        $rows = [];

        if (array_key_exists('company', $data)) {
            $rows['company'] = substr(trim((string) $data['company']), 0, 80);
        }

        if (array_key_exists('currency', $data)) {
            $rows['currency'] = substr(strtoupper(trim((string) $data['currency'])), 0, 8);
        }

        if (array_key_exists('perm', $data) && is_array($data['perm'])) {
            $rows['perm'] = $this->normalizePerm($data['perm']);
        }

        foreach ($rows as $key => $value) {
            $encoded = json_encode($value);
            $updated = DB::table('settings')->where('key', $key)->update([
                'value' => $encoded,
                'updated_at' => now(),
            ]);

            if ($updated === 0) {
                DB::table('settings')->insert([
                    'key' => $key,
                    'value' => $encoded,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        Cache::forget(self::CACHE_KEY);
    }

    /** @return array<string, array{roles: string[], notify: string[]}> */
    protected function normalizePerm(array $perm): array
    {
        $valid = array_keys(User::ROLES);
        $out = [];

        foreach ($perm as $key => $entry) {
            if (! is_array($entry)) {
                continue;
            }
            // Accept both the new {roles, notify} shape and the legacy {roles, n} shape.
            $roles = $entry['roles'] ?? [];
            $notify = $entry['notify'] ?? $entry['n'] ?? [];
            $out[(string) $key] = [
                'roles' => array_values(array_intersect((array) $roles, $valid)),
                'notify' => array_values(array_intersect((array) $notify, $valid)),
            ];
        }

        return $out;
    }

    /** @return array<string, mixed> */
    protected function stored(): array
    {
        if (! Schema::hasTable('settings')) {
            return [];
        }

        $out = [];
        foreach (DB::table('settings')->pluck('value', 'key') as $key => $value) {
            $decoded = json_decode((string) $value, true);
            $out[$key] = $decoded ?? $value;
        }

        return $out;
    }
}
