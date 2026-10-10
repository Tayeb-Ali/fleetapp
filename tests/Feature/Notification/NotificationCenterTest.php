<?php

declare(strict_types=1);

namespace Tests\Feature\Notification;

use App\Domain\Notification\Notifications\FleetNotification;
use App\Domain\Vehicle\Events\VehicleTransitioned;
use App\Domain\Vehicle\Models\Vehicle;
use App\Livewire\Notifications\NotificationCenter;
use App\Livewire\Vehicles\TransitionForm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $role): User
    {
        return User::factory()->create([
            'username' => strtolower('nc_'.$role.'_'.uniqid()),
            'role' => $role,
            'active' => true,
            'password' => 'secret123',
        ]);
    }

    protected function notify(User $user, string $category, string $event, array $context = []): void
    {
        $user->notify(new FleetNotification($category, $event, $context));
    }

    public function test_page_requires_authentication(): void
    {
        $this->get('/notifications')->assertRedirect(route('login'));
    }

    public function test_user_only_sees_their_own_notifications(): void
    {
        $mine = $this->makeUser('viewer');
        $other = $this->makeUser('viewer');

        $this->notify($mine, 'vehicle', 'created', ['vin' => 'VIN-MINE', 'actor' => 'Tester']);
        $this->notify($other, 'vehicle', 'created', ['vin' => 'VIN-OTHER', 'actor' => 'Tester']);

        Livewire::actingAs($mine)->test(NotificationCenter::class)
            ->assertSee('VIN-MINE')
            ->assertDontSee('VIN-OTHER');
    }

    public function test_mark_all_read_clears_unread_count(): void
    {
        $user = $this->makeUser('admin');

        $this->notify($user, 'vehicle', 'created', ['vin' => 'VIN-1']);
        $this->notify($user, 'user', 'created', ['subject_name' => 'Ali']);

        $this->assertSame(2, $user->fresh()->unreadNotifications()->count());

        Livewire::actingAs($user)->test(NotificationCenter::class)->call('markAllRead');

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }

    public function test_open_marks_notification_read(): void
    {
        $user = $this->makeUser('admin');

        $this->notify($user, 'vehicle', 'created', ['vin' => 'VIN-X', 'url' => '/vehicles/VIN-X']);
        $id = (string) $user->fresh()->notifications()->first()->id;

        Livewire::actingAs($user)->test(NotificationCenter::class)->call('open', $id);

        $this->assertNotNull($user->fresh()->notifications()->first()->read_at);
    }

    public function test_transition_notifies_configured_roles_and_admins(): void
    {
        $admin = $this->makeUser('admin');
        $tracker = $this->makeUser('tracker');
        $purchaser = $this->makeUser('purchaser');
        $viewer = $this->makeUser('viewer');

        $vehicle = Vehicle::factory()->create(['stage' => 'purchased']);

        VehicleTransitioned::dispatch($vehicle, 'ship', 'purchased', 'shipped', $purchaser);

        $this->assertSame(1, $tracker->fresh()->notifications()->count());
        $this->assertSame(1, $admin->fresh()->notifications()->count());
        $this->assertSame(0, $purchaser->fresh()->notifications()->count());
        $this->assertSame(0, $viewer->fresh()->notifications()->count());

        $data = $tracker->fresh()->notifications()->first()->data;

        $this->assertSame('vehicle', $data['category']);
        $this->assertSame('transitioned', $data['event']);
        $this->assertSame('ship', $data['transition_key']);
        $this->assertSame('purchased', $data['from_stage']);
        $this->assertSame('shipped', $data['to_stage']);
        $this->assertSame(route('vehicles.show', $vehicle->vin, false), $data['url']);
    }

    public function test_transition_form_creates_notification_for_trackers(): void
    {
        $purchaser = $this->makeUser('purchaser');
        $tracker = $this->makeUser('tracker');

        $vehicle = Vehicle::factory()->create(['stage' => 'purchased']);

        Livewire::actingAs($purchaser)->test(TransitionForm::class, [
            'vin' => $vehicle->vin,
            'transitionKey' => 'ship',
        ])->set('data.bl', 'BL-123456')->set('data.ship', '2026-01-01')->call('save');

        $this->assertSame(1, $tracker->fresh()->notifications()->count());
    }

    public function test_notifications_screen_is_bilingual(): void
    {
        $tracker = $this->makeUser('tracker');
        $vehicle = Vehicle::factory()->create(['stage' => 'purchased']);

        VehicleTransitioned::dispatch($vehicle, 'ship', 'purchased', 'shipped', $this->makeUser('purchaser'));

        app()->setLocale('ar');

        Livewire::actingAs($tracker)->test(NotificationCenter::class)
            ->assertSee($vehicle->vin)
            ->assertSee(__('vehicle.transitions.ship'))
            ->assertDontSee('Register shipment');
    }
}
