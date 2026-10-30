<?php

declare(strict_types=1);

namespace App\Domain\Finance\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Monthly exchange rate: units of `currency` per 1 unit of the base currency.
 *
 * Example: 1 USD = 3.6725 AED → currency=AED, period=2026-10, rate=3.6725.
 */
class ExchangeRate extends Model
{
    protected $table = 'exchange_rates';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:6',
        ];
    }
}
