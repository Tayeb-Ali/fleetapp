<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Domain\Finance\Models\Currency;
use App\Domain\Finance\Services\CurrencyService;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

/** Admin screen: the client defines which currencies exist and the base one. */
class CurrencyManager extends Component
{
    use AuthorizesRequests;

    /** @var array<string, string|int|bool|null> */
    public array $form = [
        'code' => '',
        'name_ar' => '',
        'name_en' => '',
        'symbol' => '',
        'decimals' => 2,
        'is_active' => true,
        'sort_order' => 100,
    ];

    /** @var array<string, array<string, mixed>> */
    public array $rows = [];

    public string $base = '';

    public function mount(CurrencyService $currencies): void
    {
        $this->authorize('viewAny', User::class);
        $this->base = $currencies->base();
        $this->loadRows($currencies);
    }

    public function add(CurrencyService $currencies): void
    {
        $this->authorize('viewAny', User::class);

        $validated = $this->validate([
            'form.code' => ['required', 'string', 'max:8'],
            'form.name_ar' => ['nullable', 'string', 'max:255'],
            'form.name_en' => ['nullable', 'string', 'max:255'],
            'form.symbol' => ['nullable', 'string', 'max:16'],
            'form.decimals' => ['required', 'integer', 'min:0', 'max:6'],
            'form.is_active' => ['boolean'],
            'form.sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
        ])['form'];

        $code = $currencies->normalizeCode((string) $validated['code']);
        if ($code === '') {
            $this->addError('form.code', __('finance.errors.invalid_code'));

            return;
        }

        Currency::query()->updateOrCreate(['code' => $code], [
            'name_ar' => $validated['name_ar'] ?: null,
            'name_en' => $validated['name_en'] ?: $code,
            'symbol' => $validated['symbol'] ?: null,
            'decimals' => (int) $validated['decimals'],
            'is_active' => (bool) $validated['is_active'],
            'sort_order' => (int) $validated['sort_order'],
        ]);

        $this->reset('form');
        $this->form = ['code' => '', 'name_ar' => '', 'name_en' => '', 'symbol' => '', 'decimals' => 2, 'is_active' => true, 'sort_order' => 100];
        $this->loadRows($currencies);
        session()->flash('status', __('app.saved'));
    }

    public function saveRow(string $code, CurrencyService $currencies): void
    {
        $this->authorize('viewAny', User::class);

        $row = $this->rows[$code] ?? null;
        if ($row === null) {
            return;
        }

        $this->validate([
            "rows.{$code}.name_ar" => ['nullable', 'string', 'max:255'],
            "rows.{$code}.name_en" => ['nullable', 'string', 'max:255'],
            "rows.{$code}.symbol" => ['nullable', 'string', 'max:16'],
            "rows.{$code}.decimals" => ['required', 'integer', 'min:0', 'max:6'],
            "rows.{$code}.sort_order" => ['required', 'integer', 'min:0', 'max:9999'],
        ]);

        $currency = Currency::query()->find($code);
        if ($currency === null) {
            return;
        }

        $currency->fill([
            'name_ar' => $row['name_ar'] ?: null,
            'name_en' => $row['name_en'] ?: null,
            'symbol' => $row['symbol'] ?: null,
            'decimals' => (int) $row['decimals'],
            'sort_order' => (int) $row['sort_order'],
        ])->save();

        $this->loadRows($currencies);
        session()->flash('status', __('app.saved'));
    }

    public function toggle(string $code, CurrencyService $currencies): void
    {
        $this->authorize('viewAny', User::class);

        $currency = Currency::query()->find($code);
        if ($currency === null) {
            return;
        }

        if ($currencies->isBase($code) && $currency->is_active) {
            $this->addError('form.code', __('finance.errors.base_required'));

            return;
        }

        $currency->update(['is_active' => ! $currency->is_active]);
        $this->loadRows($currencies);
    }

    public function setBase(string $code, CurrencyService $currencies): void
    {
        $this->authorize('viewAny', User::class);

        try {
            $currencies->changeBase($code);
            $this->base = $currencies->base();
            session()->flash('status', __('app.saved'));
        } catch (\Throwable $e) {
            $this->addError('form.code', $e->getMessage());
        }
    }

    protected function loadRows(CurrencyService $currencies): void
    {
        $this->rows = [];

        foreach ($currencies->all() as $currency) {
            $this->rows[$currency->code] = [
                'code' => $currency->code,
                'name_ar' => (string) ($currency->name_ar ?? ''),
                'name_en' => (string) ($currency->name_en ?? ''),
                'symbol' => (string) ($currency->symbol ?? ''),
                'decimals' => (int) $currency->decimals,
                'is_active' => (bool) $currency->is_active,
                'sort_order' => (int) $currency->sort_order,
            ];
        }
    }

    public function render(CurrencyService $currencies)
    {
        $this->authorize('viewAny', User::class);

        return view('livewire.admin.currency-manager', [
            'canChangeBase' => $currencies->canChangeBase(),
        ]);
    }
}
