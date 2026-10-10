<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Illuminate\Notifications\DatabaseNotification;

/** Builds locale-aware view models from stored database notifications. */
trait PresentsNotifications
{
    /**
     * @return array<string, mixed>
     */
    protected function presentNotification(DatabaseNotification $notification): array
    {
        $data = $notification->data;
        $data = is_array($data) ? $data : [];

        $category = (string) ($data['category'] ?? 'system');
        $event = (string) ($data['event'] ?? ($data['action'] ?? 'updated'));
        $created = $notification->created_at;

        return [
            'id' => (string) $notification->id,
            'read' => $notification->read_at !== null,
            'category' => $category,
            'event' => $event,
            'headline' => $this->notificationHeadline($category, $event, $data),
            'details' => $this->notificationDetails($data),
            'vehicle' => $this->stringOrNull($data['vehicle'] ?? null),
            'vin' => $this->stringOrNull($data['vin'] ?? null),
            'from' => $this->stringOrNull($data['from_stage'] ?? null),
            'to' => $this->stringOrNull($data['to_stage'] ?? null),
            'note' => $this->stringOrNull($data['note'] ?? null),
            'url' => $this->stringOrNull($data['url'] ?? null),
            'created_human' => $created?->diffForHumans(),
            'created_full' => $created?->format('Y-m-d H:i'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function notificationHeadline(string $category, string $event, array $data): string
    {
        $key = "notifications.events.{$category}.{$event}";

        if ($category === 'vehicle' && $event === 'transitioned') {
            $params = [
                'actor' => $this->notificationActor($data),
                'transition' => $this->transitionLabel((string) ($data['transition_key'] ?? '')),
            ];
        } elseif ($category === 'user') {
            $params = [
                'actor' => $this->notificationActor($data),
                'name' => (string) ($data['subject_name'] ?? ''),
            ];
        } else {
            $params = ['actor' => $this->notificationActor($data)];
        }

        $translated = __($key, $params);

        return $translated === $key ? $event : $translated;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function notificationActor(array $data): string
    {
        return (string) ($data['actor'] ?? __('notifications.system_actor'));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function notificationDetails(array $data): ?string
    {
        $role = $this->stringOrNull($data['subject_role'] ?? null);

        if ($role === null) {
            return null;
        }

        $key = 'roles.'.$role;
        $label = __($key);

        return $label === $key ? $role : $label;
    }

    protected function transitionLabel(string $key): string
    {
        if ($key === '') {
            return '';
        }

        $labelKey = 'vehicle.transitions.'.$key;
        $label = __($labelKey);

        return $label === $labelKey ? $key : $label;
    }

    protected function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
