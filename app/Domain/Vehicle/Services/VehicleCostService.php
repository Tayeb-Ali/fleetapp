<?php

declare(strict_types=1);

namespace App\Domain\Vehicle\Services;

use App\Domain\Finance\Services\CurrencyService;
use App\Domain\Finance\Services\ExchangeRateService;
use App\Domain\Vehicle\Models\Vehicle;
use App\Domain\Vehicle\Support\CostBreakdown;

/**
 * Builds the cost breakdown for a vehicle from stored values.
 *
 * Precedence per component:
 *   1. stored base amount (captured at transaction time)
 *   2. native amount ÷ stored FX rate
 *   3. native amount ÷ monthly rate for the transaction month
 *   4. null + missing-rate warning (never an invented number)
 */
class VehicleCostService
{
    public function __construct(
        protected ExchangeRateService $rates,
        protected CurrencyService $currencies,
    ) {}

    /**
     * @param  Vehicle|array<string, mixed>  $vehicle
     */
    public function for(Vehicle|array $vehicle): CostBreakdown
    {
        $v = $vehicle instanceof Vehicle ? $vehicle->toArray() : $vehicle;
        $base = $this->currencies->base();

        $purchaseCurrency = $this->currency($v, 'purchase_currency', $base);
        $shippingCurrency = $this->currency($v, 'shipping_currency', $base);
        $saleCurrency = $this->currency($v, 'sale_currency', $base);

        $purchaseNative = $this->amount($v, 'cost');
        $shippingNative = $this->amount($v, 'shipping_cost');
        $saleNative = $this->amount($v, 'sale_price');

        $purchase = $this->component(
            $purchaseNative,
            $purchaseCurrency,
            $this->amount($v, 'purchase_fx_rate'),
            $this->amount($v, 'purchase_base_amount'),
            (string) ($v['purchased_at'] ?? ''),
        );

        $shipping = $this->component(
            $shippingNative,
            $shippingCurrency,
            $this->amount($v, 'shipping_fx_rate'),
            $this->amount($v, 'shipping_base_amount'),
            (string) ($v['shipped_at'] ?? ''),
        );

        $sale = $this->component(
            $saleNative,
            $saleCurrency,
            $this->amount($v, 'sale_fx_rate'),
            $this->amount($v, 'sale_base_amount'),
            (string) ($v['sold_at'] ?? ''),
        );

        $landed = $this->amount($v, 'landed_cost') ?? $this->amount($v, 'duty');

        $modified = in_array(strtolower(trim((string) ($v['mod'] ?? ''))), ['yes', 'y', '1', 'true'], true);
        $modification = $modified ? $this->amount($v, 'modification_cost') : null;

        $total = round(
            ($purchase['base'] ?? 0.0)
            + ($shipping['base'] ?? 0.0)
            + ($landed ?? 0.0)
            + ($modification ?? 0.0),
            2,
        );

        $profit = $sale['base'] !== null ? round($sale['base'] - $total, 2) : null;

        $missing = [];
        foreach (['purchase' => $purchase, 'shipping' => $shipping, 'sale' => $sale] as $key => $component) {
            if ($component['missing']) {
                $missing[] = $key;
            }
        }

        return new CostBreakdown(
            purchaseNative: $purchaseNative,
            purchaseCurrency: $purchaseCurrency,
            purchaseRate: $purchase['rate'],
            purchaseBase: $purchase['base'],
            shippingNative: $shippingNative,
            shippingCurrency: $shippingCurrency,
            shippingRate: $shipping['rate'],
            shippingBase: $shipping['base'],
            landedBase: $landed,
            modificationBase: $modification,
            totalCost: $total,
            saleNative: $saleNative,
            saleCurrency: $saleCurrency,
            saleRate: $sale['rate'],
            saleBase: $sale['base'],
            profit: $profit,
            missingRates: $missing,
        );
    }

    /**
     * @param  array<string, mixed>  $v
     */
    protected function currency(array $v, string $key, string $base): string
    {
        $code = strtoupper(trim((string) ($v[$key] ?? '')));

        return $code !== '' ? $code : $base;
    }

    /**
     * @param  array<string, mixed>  $v
     */
    protected function amount(array $v, string $key): ?float
    {
        if (! array_key_exists($key, $v) || $v[$key] === null || $v[$key] === '') {
            return null;
        }

        return (float) $v[$key];
    }

    /**
     * @return array{rate: ?float, base: ?float, missing: bool}
     */
    protected function component(?float $native, string $currency, ?float $storedRate, ?float $storedBase, string $date): array
    {
        if ($native === null && $storedBase === null) {
            return ['rate' => $storedRate, 'base' => $storedBase, 'missing' => false];
        }

        if ($storedBase !== null) {
            return ['rate' => $storedRate, 'base' => round($storedBase, 2), 'missing' => false];
        }

        $rate = $storedRate;
        if ($rate === null || $rate <= 0) {
            $rate = $this->rates->rateForDate($currency, $date);
        }

        if ($rate === null || $rate <= 0) {
            return ['rate' => null, 'base' => null, 'missing' => true];
        }

        return ['rate' => $rate, 'base' => round($native / $rate, 2), 'missing' => false];
    }
}
