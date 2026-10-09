<?php

declare(strict_types=1);

namespace App\Livewire;

use Livewire\Component;

/** Bell with unread count, list, mark-read. Polls every 60s. */
class NotificationsBell extends Component
{
    public function markRead(string $id): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $user->notifications()->where('id', $id)->update(['read_at' => now()]);
    }

    public function markAllRead(): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $user->unreadNotifications->markAsRead();
    }

    public function render()
    {
        $user = auth()->user();
        $notifications = collect();
        $unread = 0;

        try {
            if ($user) {
                $notifications = $user->notifications()->latest()->limit(20)->get();
                $unread = $user->unreadNotifications()->count();
            }
        } catch (\Throwable) {
            $notifications = collect();
            $unread = 0;
        }

        return view('livewire.notifications-bell', [
            'notifications' => $notifications,
            'unread' => $unread,
        ]);
    }
}
