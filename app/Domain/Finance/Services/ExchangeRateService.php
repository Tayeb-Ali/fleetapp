<?php

declare(strict_types=1);

namespace App\Domain\Finance\Services;

use App\Domain\Finance\Models\ExchangeRate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Monthly exchange rates + conversion to the base currency.
 *
 * Direction: `rate` = units of the foreign currency per 1 base unit.
 * Conversion: base = native / rate.
 *
 * A missing rate is never invented: callers receive null and surface a
 * data-quality warning instead of a misleading number.
 */
class ExchangeRateService
{
    public const CACHE_PREFIX = 'fleet.fx.';

    public function __construct(protected CurrencyService $currencies) {}

    public function periodFor(?string $date): string
    {
        if (is_string($date) && preg_match('/^\d{4}-\d{2}/', $date) === 1) {
            return substr($date, 0, 7);
        }

        try {
            return Carbon::parse($date ?: 'now')->format('Y-m');
        } catch (\Throwable) {
            return now()->format('Y-m');
        }
    }

    /** Exact rate for a currency/period, or null. Base always returns 1. */
    public function rate(string $currency, string $period): ?float
    {
        $currency = $this->currencies->normalizeCode($currency);

        if ($currency === '') {
            return null;
        }

        if ($this->currencies->isBase($currency)) {
            return 1.0;
        }

        if (! $this->tableExists()) {
            return null;
        }

        $key = self::CACHE_PREFIX.$currency.'.'.$period;

        $rate = Cache::rememberForever($key, function () use ($currency, $period): ?float {
            $value = ExchangeRate::query()
                ->where('currency', $currency)
                ->where('period', $period)
                ->value('rate');

            return $value === null ? null : (float) $value;
        });

        return $rate !== null && $rate > 0 ? $rate : null;
    }

    /**
     * Rate for the transaction date: exact month, then latest prior month.
     */
    public function rateForDate(string $currency, ?string $date): ?float
    {
        $currency = $this->currencies->normalizeCode($currency);
        $period = $this->periodFor($date);

        $rate = $this->rate($currency, $period);
        if ($rate !== null) {
            return $rate;
        }

        return $this->latestPrior($currency, $period);
    }

    public function latestPrior(string $currency, string $period): ?float
    {
        $currency = $this->currencies->normalizeCode($currency);

        if ($currency === '' || ! $this->tableExists()) {
            return null;
        }

        $value = ExchangeRate::query()
            ->where('currency', $currency)
            ->where('period', '<', $period)
            ->orderByDesc('period')
            ->value('rate');

        return $value === null ? null : ((float) $value > 0 ? (float) $value : null);
    }

    public function convertToBase(?float $amount, string $currency, ?string $date): ?float
    {
        if ($amount === null) {
            return null;
        }

        $rate = $this->rateForDate($currency, $date);

        if ($rate === null) {
            return null;
        }

        return round($amount / $rate, 2);
    }

    /** @return array<string, float> currency => rate */
    public function forPeriod(string $period): array
    {
        if (! $this->tableExists()) {
            return [];
        }

        return ExchangeRate::query()
            ->where('period', $period)
            ->pluck('rate', 'currency')
            ->map(fn ($rate) => (float) $rate)
            ->all();
    }

    public function setRate(string $currency, string $period, float $rate): void
    {
        $currency = $this->currencies->normalizeCode($currency);

        if ($currency === '' || $rate <= 0 || $this->currencies->isBase($currency)) {
            return;
        }

        ExchangeRate::query()->updateOrCreate(
            ['currency' => $currency, 'period' => $period],
            ['rate' => $rate],
        );

        $this->forget($currency, $period);
    }

    /** @param array<string, float|string|null> $rates */
    public function setRates(string $period, array $rates): void
    {
        foreach ($rates as $currency => $rate) {
            if ($rate === null || $rate === '') {
                continue;
            }

            $this->setRate((string) $currency, $period, (float) $rate);
        }
    }

    public function copyPeriod(string $from, string $to): int
    {
        if (! $this->tableExists() || $from === $to) {
            return 0;
        }

        $rows = ExchangeRate::query()->where('period', $from)->get();
        foreach ($rows as $row) {
            $this->setRate($row->currency, $to, (float) $row->rate);
        }

        return $rows->count();
    }

    public function forget(string $currency, string $period): void
    {
        Cache::forget(self::CACHE_PREFIX.$currency.'.'.$period);
    }

    public function flush(): void
    {
        if (! $this->tableExists()) {
            return;
        }

        foreach (ExchangeRate::query()->get(['currency', 'period']) as $row) {
            $this->forget($row->currency, $row->period);
        }
    }

    /**
     * @param  array<int, string>  $currencies
     * @return array<int, string>
     */
    public function missingForPeriod(string $period, array $currencies): array
    {
        $missing = [];

        foreach ($currencies as $currency) {
            $currency = $this->currencies->normalizeCode($currency);

            if ($currency === '' || $this->currencies->isBase($currency)) {
                continue;
            }

            if ($this->rate($currency, $period) === null) {
                $missing[] = $currency;
            }
        }

        return array_values(array_unique($missing));
    }

    protected function tableExists(): bool
    {
        try {
            return Schema::hasTable('exchange_rates');
        } catch (\Throwable) {
            return false;
        }
    }
}
