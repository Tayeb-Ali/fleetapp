<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Domain\Finance\Models\Currency;
use App\Domain\Finance\Services\CurrencyService;
use App\Domain\Finance\Services\ExchangeRateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExchangeRateTest extends TestCase
{
    use RefreshDatabase;

    public function test_base_currency_defaults_to_usd_and_converts_at_one(): void
    {
        $currencies = app(CurrencyService::class);
        $rates = app(ExchangeRateService::class);

        $this->assertSame('USD', $currencies->base());
        $this->assertSame(1.0, $rates->rate('USD', '2026-10'));
        $this->assertSame(150.0, $rates->convertToBase(150.0, 'USD', '2026-10-15'));
    }

    public function test_monthly_rate_converts_native_amount_to_base(): void
    {
        $rates = app(ExchangeRateService::class);
        $rates->setRate('AED', '2026-10', 3.6725);

        $this->assertSame(3.6725, $rates->rate('AED', '2026-10'));
        $this->assertSame(100.0, $rates->convertToBase(367.25, 'AED', '2026-10-15'));
    }

    public function test_missing_month_falls_back_to_latest_prior_rate(): void
    {
        $rates = app(ExchangeRateService::class);
        $rates->setRate('AED', '2026-08', 3.6);

        $this->assertSame(3.6, $rates->rateForDate('AED', '2026-10-01'));
        $this->assertNull($rates->rate('AED', '2026-10'));
    }

    public function test_missing_rate_returns_null_instead_of_guessing(): void
    {
        $rates = app(ExchangeRateService::class);

        $this->assertNull($rates->rateForDate('EUR', '2026-10-01'));
        $this->assertNull($rates->convertToBase(500.0, 'EUR', '2026-10-01'));
        $this->assertSame(['EUR'], $rates->missingForPeriod('2026-10', ['USD', 'EUR']));
    }

    public function test_unknown_currency_is_created_on_first_use(): void
    {
        $currencies = app(CurrencyService::class);

        $this->assertNull($currencies->find('QAR'));

        $created = $currencies->ensure('qar');

        $this->assertInstanceOf(Currency::class, $created);
        $this->assertSame('QAR', $created->code);
        $this->assertNotNull($currencies->find('QAR'));
    }

    public function test_inactive_currency_stays_selectable_for_existing_values(): void
    {
        $currencies = app(CurrencyService::class);
        $currencies->ensure('QAR')->update(['is_active' => false]);

        $this->assertArrayNotHasKey('QAR', $currencies->options());
        $this->assertArrayHasKey('QAR', $currencies->options(true, ['QAR']));
    }

    public function test_base_currency_is_locked_after_rates_exist(): void
    {
        $currencies = app(CurrencyService::class);
        $rates = app(ExchangeRateService::class);

        $this->assertTrue($currencies->canChangeBase());

        $rates->setRate('AED', '2026-10', 3.6725);

        $this->assertFalse($currencies->canChangeBase());
        $this->expectException(\RuntimeException::class);
        $currencies->changeBase('AED');
    }

    public function test_copy_period_duplicates_previous_month(): void
    {
        $rates = app(ExchangeRateService::class);
        $rates->setRates('2026-09', ['AED' => 3.6, 'SAR' => 3.75]);

        $copied = $rates->copyPeriod('2026-09', '2026-10');

        $this->assertSame(2, $copied);
        $this->assertSame(['AED' => 3.6, 'SAR' => 3.75], $rates->forPeriod('2026-10'));
    }
}
