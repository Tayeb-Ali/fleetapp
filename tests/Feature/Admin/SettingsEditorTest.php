<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Identity\Services\SettingsService;
use App\Livewire\Admin\SettingsEditor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsEditorTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $role): User
    {
        return User::factory()->create([
            'username' => strtolower('se_'.$role.'_'.uniqid()),
            'role' => $role,
            'active' => true,
            'password' => 'secret123',
        ]);
    }

    public function test_admin_can_view_settings_page(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->get('/settings')->assertOk();
    }

    public function test_settings_page_shows_translated_transition_and_role_labels(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->get('/settings')
            ->assertOk()
            ->assertSee(__('vehicle.transitions.ship'))
            ->assertSee(__('roles.purchaser'))
            ->assertDontSee('Register shipment');
    }

    public function test_admin_save_updates_company_currency_and_permissions(): void
    {
        $admin = $this->makeUser('admin');

        Livewire::actingAs($admin)->test(SettingsEditor::class)
            ->set('company', 'Acme Motors')
            ->set('currency', 'AED')
            ->set('perm.ship.roles', ['purchaser'])
            ->set('perm.ship.notify', ['tracker'])
            ->call('save')
            ->assertHasNoErrors();

        $settings = app(SettingsService::class);

        $this->assertSame('Acme Motors', $settings->getCompany());
        $this->assertSame('AED', $settings->getCurrency());
        $this->assertSame(['purchaser'], $settings->getTransitionPermissions()['ship']['roles']);
        $this->assertSame(['tracker'], $settings->getTransitionPermissions()['ship']['notify']);
    }

    public function test_viewer_is_forbidden_from_settings(): void
    {
        $viewer = $this->makeUser('viewer');

        $this->actingAs($viewer)->get('/settings')->assertForbidden();

        // The component refuses to render for non-admins, which makes save()
        // unreachable for viewers.
        Livewire::actingAs($viewer)->test(SettingsEditor::class)->assertForbidden();

        $this->assertFalse($viewer->can('viewAny', User::class));

        $this->assertNotSame('Hacked Co', app(SettingsService::class)->getCompany());
    }
}
