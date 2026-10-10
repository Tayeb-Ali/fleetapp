<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Livewire\Concerns\PresentsNotifications;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Livewire\Component;

/** Header bell with unread count, dropdown list and mark-read. Polls every 60s. */
class NotificationsBell extends Component
{
    use PresentsNotifications;

    public function markRead(string $id): void
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        $user->notifications()->whereKey($id)->whereNull('read_at')->update(['read_at' => now()]);
    }

    public function markAllRead(): void
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        $user->unreadNotifications()->update(['read_at' => now()]);

        $this->dispatch('toast', __('notifications.mark_all_done'));
    }

    public function open(string $id): mixed
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return null;
        }

        $notification = $user->notifications()->whereKey($id)->first();

        if (! $notification instanceof DatabaseNotification) {
            return null;
        }

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        $url = $notification->data['url'] ?? null;

        return is_string($url) && $url !== '' ? redirect()->to($url) : null;
    }

    public function render()
    {
        $user = auth()->user();
        $items = [];
        $unread = 0;

        if ($user instanceof User) {
            try {
                $items = array_map(
                    fn (DatabaseNotification $n): array => $this->presentNotification($n),
                    $user->notifications()->latest()->limit(8)->get()->all()
                );
                $unread = $user->unreadNotifications()->count();
            } catch (\Throwable $e) {
                report($e);
                $items = [];
                $unread = 0;
            }
        }

        return view('livewire.notifications-bell', [
            'items' => $items,
            'unread' => $unread,
        ]);
    }
}
