<?php

declare(strict_types=1);

namespace App\Domain\Notification\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Database + mail notification for vehicle transitions.
 *
 * Legacy reference: server.js notify() lines 41-43 (role notifs + email),
 * mail() 88-100, mailtest 186.
 */
class VehicleTransitionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public const STAGES_EN = [
        'purchased' => 'Purchased',
        'shipped' => 'In transit',
        'arrived' => 'Arrived at port',
        'clearing' => 'Clearing customs',
        'cleared' => 'Customs cleared',
        'instock' => 'In warehouse - available for sale',
        'sold' => 'Sold - awaiting delivery',
        'delivered' => 'Delivered',
    ];

    public const STAGES_AR = [
        'purchased' => 'تم الشراء',
        'shipped' => 'في الشحن',
        'arrived' => 'وصل إلى الميناء',
        'clearing' => 'التخليص الجمركي',
        'cleared' => 'تم التخليص الجمركي',
        'instock' => 'في المخزن - متاح للبيع',
        'sold' => 'تم البيع - بانتظار التسليم',
        'delivered' => 'تم التسليم',
    ];

    public function __construct(
        public string $vin,
        public string $action,
        public string $actorName,
        public ?string $fromStage = null,
        public ?string $toStage = null,
        public ?string $note = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        $stage = $this->toStage ?? $this->fromStage;

        return [
            'vin' => $this->vin,
            'action' => $this->action,
            'actor' => $this->actorName,
            'from_stage' => $this->fromStage,
            'to_stage' => $this->toStage,
            'note' => $this->note,
            'stage' => $stage,
            'stage_labels' => [
                'en' => $stage !== null ? (self::STAGES_EN[$stage] ?? $stage) : null,
                'ar' => $stage !== null ? (self::STAGES_AR[$stage] ?? $stage) : null,
            ],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $stage = $this->toStage ?? $this->fromStage;
        $label = $stage !== null ? (self::STAGES_EN[$stage] ?? $stage) : $this->action;

        return (new MailMessage)
            ->subject("[Fleet Tracker] {$this->action}: {$this->vin} ({$label})")
            ->line("Vehicle {$this->vin} — {$this->action} by {$this->actorName}.")
            ->line($this->fromStage || $this->toStage
                ? 'Stage: '.($this->fromStage ?? '-').' → '.($this->toStage ?? '-')." ({$label})"
                : "Stage: {$label}")
            ->when((bool) $this->note, fn (MailMessage $m) => $m->line("Note: {$this->note}"))
            ->line('Open the system to view details.');
    }
}
