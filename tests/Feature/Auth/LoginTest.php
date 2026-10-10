<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $role = 'viewer', array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'username' => strtolower('login_'.$role.'_'.uniqid()),
            'role' => $role,
            'active' => true,
            'password' => 'secret123',
        ], $overrides));
    }

    public function test_guest_can_view_login_page(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_authenticated_user_is_redirected_away_from_login(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->get('/login')->assertRedirect('/');
    }

    public function test_wrong_password_shows_auth_failed_error(): void
    {
        $user = $this->makeUser();

        Livewire::test(Login::class)
            ->set('username', $user->username)
            ->set('password', 'wrongpass')
            ->call('login')
            ->assertHasErrors(['username'])
            ->assertSee(__('auth.failed'));

        $this->assertGuest();
    }

    public function test_disabled_account_is_rejected(): void
    {
        $user = $this->makeUser('viewer', ['active' => false]);

        Livewire::test(Login::class)
            ->set('username', $user->username)
            ->set('password', 'secret123')
            ->call('login')
            ->assertHasErrors(['username'])
            ->assertSee(__('auth.disabled'));

        $this->assertGuest();
    }

    public function test_validation_requires_username_and_password(): void
    {
        Livewire::test(Login::class)
            ->set('username', '')
            ->set('password', '')
            ->call('login')
            ->assertHasErrors(['username', 'password']);

        $this->assertGuest();
    }

    public function test_success_redirects_to_home(): void
    {
        $user = $this->makeUser();

        // Livewire test requests skip the StartSession middleware, so attach
        // the session store to every request handled during this test
        // (test-only setup; production requests get it from the middleware).
        Event::listen(RouteMatched::class, function (RouteMatched $event): void {
            $event->request->setLaravelSession(app('session')->driver());
        });

        Livewire::test(Login::class)
            ->set('username', $user->username)
            ->set('password', 'secret123')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertAuthenticated();
    }
}
