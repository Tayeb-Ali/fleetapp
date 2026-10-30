<?php

declare(strict_types=1);

namespace App\Domain\Vehicle\Support;

/**
 * Immutable cost breakdown for one vehicle.
 *
 * Native amounts keep the currency the client actually paid; base amounts are
 * converted with the FX rate captured at transaction time.
 */
final readonly class CostBreakdown
{
    /**
     * @param  array<int, string>  $missingRates  keys: purchase, shipping, sale
     */
    public function __construct(
        public ?float $purchaseNative,
        public string $purchaseCurrency,
        public ?float $purchaseRate,
        public ?float $purchaseBase,
        public ?float $shippingNative,
        public string $shippingCurrency,
        public ?float $shippingRate,
        public ?float $shippingBase,
        public ?float $landedBase,
        public ?float $modificationBase,
        public float $totalCost,
        public ?float $saleNative,
        public string $saleCurrency,
        public ?float $saleRate,
        public ?float $saleBase,
        public ?float $profit,
        public array $missingRates = [],
    ) {}

    public function hasMissingRates(): bool
    {
        return $this->missingRates !== [];
    }

    public function hasAnyCost(): bool
    {
        return $this->purchaseNative !== null
            || $this->shippingNative !== null
            || $this->landedBase !== null
            || $this->modificationBase !== null;
    }

    /**
     * Flat representation used by reports (merged into the vehicle array).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'purchase_native' => $this->purchaseNative,
            'purchase_currency' => $this->purchaseCurrency,
            'purchase_rate' => $this->purchaseRate,
            'purchase_base' => $this->purchaseBase,
            'shipping_native' => $this->shippingNative,
            'shipping_currency' => $this->shippingCurrency,
            'shipping_rate' => $this->shippingRate,
            'shipping_base' => $this->shippingBase,
            'landed_base' => $this->landedBase,
            'modification_base' => $this->modificationBase,
            'total_cost' => $this->totalCost,
            'sale_native' => $this->saleNative,
            'sale_currency' => $this->saleCurrency,
            'sale_rate' => $this->saleRate,
            'sale_base' => $this->saleBase,
            'profit' => $this->profit,
            'fx_missing' => $this->missingRates,
        ];
    }
}
