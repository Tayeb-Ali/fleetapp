<?php

declare(strict_types=1);

namespace App\Livewire\Notifications;

use App\Livewire\Concerns\PresentsNotifications;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Livewire\Component;
use Livewire\WithPagination;

/** Full notifications screen: list, filter, mark read, deep-link to details. */
class NotificationCenter extends Component
{
    use PresentsNotifications;
    use WithPagination;

    public string $filter = 'all';

    /** @var array<int, string> */
    protected array $allowedFilters = ['all', 'unread', 'vehicle', 'user', 'system'];

    public function setFilter(string $filter): void
    {
        $this->filter = in_array($filter, $this->allowedFilters, true) ? $filter : 'all';
        $this->resetPage();
    }

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
        $notifications = null;
        $unread = 0;
        $items = [];

        if ($user instanceof User) {
            try {
                $query = $user->notifications();

                if ($this->filter === 'unread') {
                    $query->whereNull('read_at');
                } elseif (in_array($this->filter, ['vehicle', 'user', 'system'], true)) {
                    $query->where('data->category', $this->filter);
                }

                $notifications = $query->latest()->paginate(15);
                $unread = $user->unreadNotifications()->count();
                $items = array_map(
                    fn (DatabaseNotification $n): array => $this->presentNotification($n),
                    array_values($notifications->items())
                );
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return view('livewire.notifications.notification-center', [
            'notifications' => $notifications,
            'items' => $items,
            'unread' => $unread,
        ]);
    }
}
