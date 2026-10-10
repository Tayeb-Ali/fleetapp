<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Vehicle\Models\Vehicle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $role): User
    {
        return User::factory()->create([
            'username' => strtolower('al_'.$role.'_'.uniqid()),
            'role' => $role,
            'active' => true,
            'password' => 'secret123',
        ]);
    }

    public function test_viewer_cannot_view_audit_log(): void
    {
        $viewer = $this->makeUser('viewer');

        $this->actingAs($viewer)->get('/audit')->assertForbidden();
    }

    public function test_audit_log_localizes_actions_and_notes_in_arabic(): void
    {
        $admin = $this->makeUser('admin');
        Vehicle::factory()->create(['vin' => 'VIN-AUD-AR']);

        $response = $this->actingAs($admin)->get('/audit');

        $response->assertOk();
        $response->assertSee('سجل التدقيق');
        $response->assertSee('تسجيل');
        $response->assertSee('تم تسجيل شراء المركبة');
        $response->assertSee('VIN-AUD-AR');
    }

    public function test_audit_log_localizes_in_english(): void
    {
        $admin = $this->makeUser('admin');
        Vehicle::factory()->create(['vin' => 'VIN-AUD-EN']);

        $response = $this->actingAs($admin)->withSession(['locale' => 'en'])->get('/audit');

        $response->assertOk();
        $response->assertSee('Audit log');
        $response->assertSee('Registered');
        $response->assertSee('Vehicle purchase registered');
        $response->assertSee('VIN-AUD-EN');
    }
}
