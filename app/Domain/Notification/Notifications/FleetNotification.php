<?php

declare(strict_types=1);

namespace App\Domain\Notification\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Generic database (+ optional mail) notification for any fleet system event.
 *
 * Only raw, locale-neutral values are stored; the notifications screen
 * translates them at render time so the list always follows the active locale.
 */
class FleetNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public string $category,
        public string $event,
        public array $context = [],
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (self::mailConfigured()) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $toStage = $this->context['to_stage'] ?? null;
        $fromStage = $this->context['from_stage'] ?? null;

        return array_filter([
            'category' => $this->category,
            'event' => $this->event,
            'actor' => $this->context['actor'] ?? null,
            'vin' => $this->context['vin'] ?? null,
            'vehicle' => $this->context['vehicle'] ?? null,
            'transition_key' => $this->context['transition_key'] ?? null,
            'from_stage' => $fromStage,
            'to_stage' => $toStage,
            'note' => $this->context['note'] ?? null,
            'subject_name' => $this->context['subject_name'] ?? null,
            'subject_role' => $this->context['subject_role'] ?? null,
            'url' => $this->context['url'] ?? null,
            // Legacy bell compatibility.
            'action' => $this->event,
            'stage' => $toStage ?? $fromStage,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = (string) ($this->context['vehicle'] ?? $this->context['subject_name'] ?? $this->context['vin'] ?? '');
        $url = $this->context['url'] ?? null;

        $message = (new MailMessage)
            ->subject('[Fleet Tracker] '.$this->category.'.'.$this->event.($subject !== '' ? ' — '.$subject : ''))
            ->line($this->summary());

        if (is_string($url) && $url !== '') {
            $message->action('Open in Fleet Tracker', url($url));
        }

        return $message;
    }

    protected function summary(): string
    {
        $actor = (string) ($this->context['actor'] ?? 'System');
        $vin = (string) ($this->context['vin'] ?? '');
        $vehicle = (string) ($this->context['vehicle'] ?? '');
        $target = $vehicle !== '' ? $vehicle : ($vin !== '' ? $vin : (string) ($this->context['subject_name'] ?? ''));

        return trim(sprintf('%s: %s', $actor, $target !== '' ? $target : $this->event));
    }

    public static function mailConfigured(): bool
    {
        try {
            $mailer = config('mail.default');

            if (! is_string($mailer) || in_array($mailer, ['log', 'array'], true)) {
                return false;
            }

            $host = config("mail.mailers.{$mailer}.host");

            return is_string($host) && $host !== '';
        } catch (\Throwable) {
            return false;
        }
    }
}
