<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Domain\Finance\Services\CurrencyService;
use App\Domain\Finance\Services\ExchangeRateService;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

/**
 * Monthly FX entry. The client can enter the rate directly
 * (1 base = X foreign) or derive it from a local amount + base amount.
 */
class ExchangeRateEditor extends Component
{
    use AuthorizesRequests;

    public string $period = '';

    /** @var array<string, string> */
    public array $rates = [];

    /** @var array<string, string> */
    public array $localAmounts = [];

    /** @var array<string, string> */
    public array $baseAmounts = [];

    public function mount(ExchangeRateService $rates): void
    {
        $this->authorize('viewAny', User::class);
        $this->period = now()->format('Y-m');
        $this->loadRates($rates);
    }

    public function updatedPeriod(): void
    {
        $this->period = substr(trim($this->period), 0, 7);
        $this->loadRates(app(ExchangeRateService::class));
    }

    public function save(ExchangeRateService $rates, CurrencyService $currencies): void
    {
        $this->authorize('viewAny', User::class);

        $rules = [];
        foreach (array_keys($this->rates) as $code) {
            $rules['rates.'.$code] = ['nullable', 'numeric', 'min:0.000001'];
        }

        $this->validate($rules);

        $period = $this->normalizePeriod();
        $rates->setRates($period, $this->rates);
        $rates->flush();

        $this->loadRates($rates);
        session()->flash('status', __('app.saved'));
    }

    public function copyPrevious(ExchangeRateService $rates): void
    {
        $this->authorize('viewAny', User::class);

        $period = $this->normalizePeriod();
        $previous = date('Y-m', strtotime($period.'-01 -1 month'));

        $rates->copyPeriod($previous, $period);
        $rates->flush();
        $this->loadRates($rates);
        session()->flash('status', __('finance.copied_previous'));
    }

    public function derive(string $code, ExchangeRateService $rates): void
    {
        $this->authorize('viewAny', User::class);

        $local = (float) ($this->localAmounts[$code] ?? 0);
        $base = (float) ($this->baseAmounts[$code] ?? 0);

        if ($local <= 0 || $base <= 0) {
            $this->addError('rates.'.$code, __('finance.errors.derive_needs_amounts'));

            return;
        }

        $this->rates[$code] = (string) round($local / $base, 6);
    }

    protected function normalizePeriod(): string
    {
        $period = substr(trim($this->period), 0, 7);

        return preg_match('/^\d{4}-\d{2}$/', $period) === 1 ? $period : now()->format('Y-m');
    }

    protected function loadRates(ExchangeRateService $rates): void
    {
        $period = $this->normalizePeriod();
        $this->period = $period;
        $this->rates = [];
        $this->localAmounts = [];
        $this->baseAmounts = [];

        foreach ($this->currencies() as $currency) {
            $code = $currency['code'];

            if ($currency['is_base']) {
                $this->rates[$code] = '1';

                continue;
            }

            $rate = $rates->rate($code, $period);
            $this->rates[$code] = $rate !== null ? (string) $rate : '';
        }
    }

    /** @return array<int, array{code:string,name:string,is_base:bool}> */
    protected function currencies(): array
    {
        $service = app(CurrencyService::class);
        $base = $service->base();

        $rows = [];
        foreach ($service->all() as $currency) {
            $rows[] = [
                'code' => $currency->code,
                'name' => $currency->displayName(),
                'is_base' => $currency->code === $base,
            ];
        }

        // Currencies used in vehicles but missing from the catalogue.
        foreach ($this->usedCurrencies() as $code) {
            if (! in_array($code, array_column($rows, 'code'), true)) {
                $rows[] = ['code' => $code, 'name' => $code, 'is_base' => $code === $base];
            }
        }

        return $rows;
    }

    /** @return array<int, string> */
    protected function usedCurrencies(): array
    {
        try {
            if (! Schema::hasTable('vehicles')) {
                return [];
            }

            $used = [];
            foreach (['purchase_currency', 'shipping_currency', 'sale_currency'] as $column) {
                foreach (DB::table('vehicles')->whereNotNull($column)->distinct()->pluck($column) as $code) {
                    $code = strtoupper(trim((string) $code));
                    if ($code !== '') {
                        $used[] = $code;
                    }
                }
            }

            return array_values(array_unique($used));
        } catch (\Throwable) {
            return [];
        }
    }

    public function render(ExchangeRateService $rates, CurrencyService $currencies)
    {
        $this->authorize('viewAny', User::class);

        $period = $this->normalizePeriod();
        $used = $this->usedCurrencies();
        $missing = $rates->missingForPeriod($period, array_merge($used, $currencies->codes()));

        return view('livewire.admin.exchange-rate-editor', [
            'currencies' => $this->currencies(),
            'missing' => $missing,
            'previousPeriod' => date('Y-m', strtotime($period.'-01 -1 month')),
            'base' => $currencies->base(),
        ]);
    }
}
