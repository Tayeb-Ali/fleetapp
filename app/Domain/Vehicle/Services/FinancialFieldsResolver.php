<?php

declare(strict_types=1);

namespace App\Domain\Vehicle\Services;

use App\Domain\Finance\Services\CurrencyService;
use App\Domain\Finance\Services\ExchangeRateService;

/**
 * Resolves currency, FX rate and converted base amount for each financial
 * component before a vehicle is written.
 *
 * The rate captured with the transaction wins; the monthly table is only the
 * default when the client leaves the rate empty.
 */
class FinancialFieldsResolver
{
    public function __construct(
        protected ExchangeRateService $rates,
        protected CurrencyService $currencies,
    ) {}

    /**
     * @param  array<string, mixed>  $data  incoming (validated) payload
     * @param  array<string, mixed>  $existing  current vehicle values
     * @return array<string, mixed>
     */
    public function apply(array $data, array $existing = []): array
    {
        $base = $this->currencies->base();

        $components = [
            ['cost', 'purchase_currency', 'purchase_fx_rate', 'purchase_base_amount', 'purchased_at'],
            ['shipping_cost', 'shipping_currency', 'shipping_fx_rate', 'shipping_base_amount', 'shipped_at'],
            ['sale_price', 'sale_currency', 'sale_fx_rate', 'sale_base_amount', 'sold_at'],
        ];

        foreach ($components as [$nativeKey, $currencyKey, $rateKey, $baseKey, $dateKey]) {
            $hasNative = array_key_exists($nativeKey, $data);
            $hasCurrency = array_key_exists($currencyKey, $data);
            $hasRate = array_key_exists($rateKey, $data);

            if (! $hasNative && ! $hasCurrency && ! $hasRate) {
                continue;
            }

            $native = $hasNative ? $data[$nativeKey] : ($existing[$nativeKey] ?? null);

            $currency = strtoupper(trim((string) ($hasCurrency
                ? $data[$currencyKey]
                : ($existing[$currencyKey] ?? ''))));
            if ($currency === '') {
                $currency = $base;
            }

            $rate = $hasRate ? $data[$rateKey] : ($existing[$rateKey] ?? null);
            $rate = ($rate === null || $rate === '') ? null : (float) $rate;

            if ($currency === $base) {
                $rate = 1.0;
            }

            $date = $data[$dateKey] ?? ($existing[$dateKey] ?? null);
            if ($rate === null && $native !== null && $native !== '') {
                $rate = $this->rates->rateForDate($currency, is_string($date) ? $date : null);
            }

            $data[$currencyKey] = $currency;
            if ($rate !== null && $rate > 0) {
                $data[$rateKey] = $rate;
            }

            if ($native === null || $native === '') {
                $data[$baseKey] = null;
            } elseif ((float) $native === 0.0) {
                $data[$baseKey] = 0.0;
            } elseif ($rate !== null && $rate > 0) {
                $data[$baseKey] = round(((float) $native) / $rate, 2);
            } else {
                $data[$baseKey] = null;
            }
        }

        $mod = array_key_exists('mod', $data)
            ? $this->normalizeMod($data['mod'])
            : (array_key_exists('mod', $existing) ? $this->normalizeMod($existing['mod']) : null);

        if (array_key_exists('mod', $data)) {
            $data['mod'] = $mod;
        }

        if ($mod === 'No' && array_key_exists('modification_cost', $data)) {
            $data['modification_cost'] = null;
        }

        if (array_key_exists('bl_release', $data)) {
            $data['bl_release'] = self::normalizeYesNo($data['bl_release']);
        }

        return $data;
    }

    /**
     * Native amount in a foreign currency that has no usable rate in the
     * resolved payload — saving it would silently store a null base amount.
     *
     * @param  array<string, mixed>  $data
     * @return array{0: string, 1: string}|null [rate key, currency]
     */
    public function missingRate(array $data): ?array
    {
        $base = $this->currencies->base();

        foreach ([
            ['cost', 'purchase_currency', 'purchase_fx_rate'],
            ['shipping_cost', 'shipping_currency', 'shipping_fx_rate'],
            ['sale_price', 'sale_currency', 'sale_fx_rate'],
        ] as [$nativeKey, $currencyKey, $rateKey]) {
            $native = $data[$nativeKey] ?? null;
            if ($native === null || $native === '' || (float) $native === 0.0) {
                continue;
            }

            $currency = strtoupper(trim((string) ($data[$currencyKey] ?? ''))) ?: $base;
            if ($currency === $base) {
                continue;
            }

            $rate = $data[$rateKey] ?? null;
            if ($rate === null || $rate === '' || (float) $rate <= 0) {
                return [$rateKey, $currency];
            }
        }

        return null;
    }

    public function normalizeMod(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return self::isModified($value) ? 'Yes' : 'No';
    }

    /**
     * Canonical Yes/No value; unknown answers are left null so the importer
     * can warn instead of silently recording "No".
     */
    public static function normalizeYesNo(mixed $value): ?string
    {
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $text = strtolower(trim((string) $value));

        if (self::isYes($text)) {
            return 'Yes';
        }

        if (str_starts_with($text, 'no') || in_array($text, ['n', '0', 'false'], true)) {
            return 'No';
        }

        return null;
    }

    /**
     * The tracker uses qualified answers such as "Yes - Workshop"; any value
     * starting with "yes" counts as modified/released.
     */
    public static function isYes(mixed $value): bool
    {
        $text = strtolower(trim((string) $value));

        return $text !== '' && (str_starts_with($text, 'yes') || in_array($text, ['y', '1', 'true', 'released'], true));
    }

    public static function isModified(mixed $value): bool
    {
        return self::isYes($value);
    }
}
