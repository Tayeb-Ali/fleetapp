<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FooterTest extends TestCase
{
    use RefreshDatabase;

    protected function makeAdmin(): User
    {
        return User::factory()->create([
            'username' => 'footer_admin_'.uniqid(),
            'role' => 'admin',
            'active' => true,
        ]);
    }

    public function test_login_page_shows_developer_credit(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee(__('app.developed_by'))
            ->assertSee('www.smart.sd')
            ->assertSee('SmartTeam');
    }

    public function test_authenticated_page_shows_developer_credit(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get('/')
            ->assertOk()
            ->assertSee('www.smart.sd')
            ->assertSee('SmartTeam');
    }
}
