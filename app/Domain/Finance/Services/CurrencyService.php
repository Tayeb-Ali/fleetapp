<?php

declare(strict_types=1);

namespace App\Domain\Finance\Services;

use App\Domain\Finance\Models\Currency;
use App\Domain\Identity\Services\SettingsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Currency catalogue + base-currency rules.
 *
 * The client defines which currencies exist. The base currency is the one
 * configured in settings and always converts at 1.
 */
class CurrencyService
{
    public function __construct(protected SettingsService $settings) {}

    public function base(): string
    {
        try {
            $code = strtoupper(trim($this->settings->getCurrency()));
        } catch (\Throwable) {
            $code = '';
        }

        return $code !== '' ? $code : SettingsService::DEFAULT_CURRENCY;
    }

    /** @return Collection<int, Currency> */
    public function all(bool $activeOnly = false): Collection
    {
        if (! $this->tableExists()) {
            return collect();
        }

        $query = Currency::query()->orderBy('sort_order')->orderBy('code');

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->get();
    }

    /**
     * @param  array<int, string>  $include  codes to keep even when inactive (existing values)
     * @return array<string, string> code => localized name
     */
    public function options(bool $activeOnly = true, array $include = []): array
    {
        $include = array_map(fn ($code) => $this->normalizeCode((string) $code), $include);

        $options = [];
        foreach ($this->all() as $currency) {
            if ($activeOnly && ! $currency->is_active && ! in_array($currency->code, $include, true)) {
                continue;
            }

            $options[$currency->code] = $currency->code.' — '.$currency->displayName();
        }

        // The base currency must always be selectable, even before seeding.
        $base = $this->base();
        if (! array_key_exists($base, $options)) {
            $options = [$base => $base] + $options;
        }

        return $options;
    }

    /** @return array<int, string> */
    public function codes(bool $activeOnly = true): array
    {
        return array_keys($this->options($activeOnly));
    }

    public function find(string $code): ?Currency
    {
        $code = $this->normalizeCode($code);

        if ($code === '' || ! $this->tableExists()) {
            return null;
        }

        return Currency::query()->find($code);
    }

    /** Create the currency on first use (import / free-text entry). */
    public function ensure(string $code, bool $active = true): ?Currency
    {
        $code = $this->normalizeCode($code);

        if ($code === '' || ! $this->tableExists()) {
            return null;
        }

        return Currency::query()->firstOrCreate(
            ['code' => $code],
            [
                'name_ar' => null,
                'name_en' => $code,
                'symbol' => null,
                'decimals' => 2,
                'is_active' => $active,
                'sort_order' => 999,
            ],
        );
    }

    public function isBase(string $code): bool
    {
        return $this->normalizeCode($code) === $this->base();
    }

    /**
     * The base may only change while no financial history exists, because
     * every stored base amount was converted against the previous base.
     */
    public function canChangeBase(): bool
    {
        try {
            if (Schema::hasTable('exchange_rates') && DB::table('exchange_rates')->exists()) {
                return false;
            }

            if (Schema::hasTable('vehicles') && DB::table('vehicles')->whereNotNull('purchase_base_amount')->exists()) {
                return false;
            }
        } catch (\Throwable) {
            return false;
        }

        return true;
    }

    /**
     * @throws \RuntimeException when history already exists
     */
    public function changeBase(string $code): void
    {
        $code = $this->normalizeCode($code);

        if ($code === '' || ! $this->find($code)) {
            throw new \RuntimeException(__('finance.errors.unknown_currency'));
        }

        if (! $this->canChangeBase()) {
            throw new \RuntimeException(__('finance.errors.base_locked'));
        }

        $this->settings->update(['currency' => $code]);
    }

    public function normalizeCode(?string $code): string
    {
        return strtoupper(substr(trim((string) $code), 0, 8));
    }

    protected function tableExists(): bool
    {
        try {
            return Schema::hasTable('currencies');
        } catch (\Throwable) {
            return false;
        }
    }
}
