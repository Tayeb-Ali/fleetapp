<?php

declare(strict_types=1);

namespace Tests\Feature\Notification;

use App\Domain\Notification\Notifications\FleetNotification;
use App\Livewire\NotificationsBell;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationsBellTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $role = 'viewer'): User
    {
        return User::factory()->create([
            'username' => strtolower('bell_'.$role.'_'.uniqid()),
            'role' => $role,
            'active' => true,
            'password' => 'secret123',
        ]);
    }

    public function test_bell_renders_unread_count_and_headline(): void
    {
        $user = $this->makeUser();

        $user->notify(new FleetNotification('vehicle', 'created', ['vin' => 'VIN-BELL', 'actor' => 'Tester']));

        Livewire::actingAs($user)->test(NotificationsBell::class)
            ->assertSee('VIN-BELL')
            ->assertViewHas('unread', 1);
    }

    public function test_bell_is_safe_for_guests(): void
    {
        Livewire::test(NotificationsBell::class)
            ->assertViewHas('unread', 0)
            ->assertSee(__('notifications.empty'));
    }

    public function test_bell_mark_all_read_clears_unread_count(): void
    {
        $user = $this->makeUser('admin');

        $user->notify(new FleetNotification('system', 'settings_updated', ['actor' => 'Admin']));
        $user->notify(new FleetNotification('vehicle', 'created', ['vin' => 'VIN-1']));

        $this->assertSame(2, $user->fresh()->unreadNotifications()->count());

        Livewire::actingAs($user)->test(NotificationsBell::class)->call('markAllRead');

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }

    public function test_bell_open_marks_notification_read_and_redirects(): void
    {
        $user = $this->makeUser('admin');

        $user->notify(new FleetNotification('vehicle', 'created', ['vin' => 'VIN-BELL', 'url' => '/vehicles/VIN-BELL']));
        $id = (string) $user->fresh()->notifications()->first()->id;

        Livewire::actingAs($user)->test(NotificationsBell::class)
            ->call('open', $id)
            ->assertRedirect('/vehicles/VIN-BELL');

        $this->assertNotNull($user->fresh()->notifications()->first()->read_at);
    }
}
