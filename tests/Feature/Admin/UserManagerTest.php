<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Livewire\Admin\UserManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $role, array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'username' => strtolower('um_'.$role.'_'.uniqid()),
            'role' => $role,
            'active' => true,
            'password' => 'secret123',
        ], $overrides));
    }

    public function test_admin_can_view_users_page(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->get('/users')->assertOk();
    }

    public function test_viewer_save_and_toggle_active_are_forbidden(): void
    {
        $viewer = $this->makeUser('viewer');
        $target = $this->makeUser('sales');

        $this->actingAs($viewer)->get('/users')->assertForbidden();

        // The component refuses to render for non-admins, which makes both
        // save() and toggleActive() unreachable for viewers.
        Livewire::actingAs($viewer)->test(UserManager::class)->assertForbidden();

        // And the gates behind save() ('create') and toggleActive()
        // deny viewers as well, so no write could have happened.
        $this->assertFalse($viewer->can('create', User::class));
        $this->assertFalse($viewer->can('update', $target));
        $this->assertFalse($viewer->can('toggleActive', $target));

        $this->assertTrue($target->fresh()->active);
    }

    public function test_admin_cannot_disable_own_account(): void
    {
        $admin = $this->makeUser('admin');

        Livewire::actingAs($admin)->test(UserManager::class)
            ->call('toggleActive', $admin->id)
            ->assertSee(__('users.cannot_disable_self'));

        $this->assertTrue($admin->fresh()->active);
    }

    public function test_non_admin_sales_cannot_create_users(): void
    {
        $sales = $this->makeUser('sales');

        Livewire::actingAs($sales)->test(UserManager::class)->assertForbidden();

        $this->assertFalse($sales->can('create', User::class));

        $this->assertDatabaseMissing('users', ['username' => 'um_salescreated']);
    }
}
