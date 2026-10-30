<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Domain\Finance\Models\Currency;
use App\Domain\Finance\Models\ExchangeRate;
use App\Domain\Finance\Services\CurrencyService;
use App\Domain\Finance\Services\ExchangeRateService;
use App\Livewire\Admin\CurrencyManager;
use App\Livewire\Admin\ExchangeRateEditor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CurrencyAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $role): User
    {
        return User::factory()->create([
            'username' => strtolower('ca_'.$role.'_'.uniqid()),
            'role' => $role,
            'active' => true,
            'password' => 'secret123',
        ]);
    }

    public function test_admin_can_open_currency_and_rate_screens(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->get('/settings/currencies')->assertOk();
        $this->actingAs($admin)->get('/settings/exchange-rates')->assertOk();
    }

    public function test_viewer_is_forbidden_from_currency_screens(): void
    {
        $viewer = $this->makeUser('viewer');

        $this->actingAs($viewer)->get('/settings/currencies')->assertForbidden();
        $this->actingAs($viewer)->get('/settings/exchange-rates')->assertForbidden();
    }

    public function test_admin_can_add_a_currency(): void
    {
        $admin = $this->makeUser('admin');

        Livewire::actingAs($admin)
            ->test(CurrencyManager::class)
            ->set('form.code', 'qar')
            ->set('form.name_ar', 'ريال قطري')
            ->set('form.name_en', 'Qatari Riyal')
            ->set('form.decimals', 2)
            ->set('form.sort_order', 60)
            ->call('add')
            ->assertHasNoErrors();

        $currency = Currency::query()->find('QAR');

        $this->assertNotNull($currency);
        $this->assertSame('Qatari Riyal', $currency->name_en);
        $this->assertTrue($currency->is_active);
    }

    public function test_admin_can_save_and_derive_monthly_rates(): void
    {
        $admin = $this->makeUser('admin');

        Livewire::actingAs($admin)
            ->test(ExchangeRateEditor::class)
            ->set('period', '2026-10')
            ->set('rates.AED', '3.6725')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(3.6725, app(ExchangeRateService::class)->rate('AED', '2026-10'));

        Livewire::actingAs($admin)
            ->test(ExchangeRateEditor::class)
            ->set('period', '2026-11')
            ->set('localAmounts.SAR', '375')
            ->set('baseAmounts.SAR', '100')
            ->call('derive', 'SAR')
            ->assertSet('rates.SAR', '3.75');
    }

    public function test_saving_only_the_base_rate_does_not_lock_the_base_currency(): void
    {
        $admin = $this->makeUser('admin');

        Livewire::actingAs($admin)
            ->test(ExchangeRateEditor::class)
            ->set('period', '2026-10')
            ->set('rates.AED', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(0, ExchangeRate::query()->count());
        $this->assertTrue(app(CurrencyService::class)->canChangeBase());
    }
}
