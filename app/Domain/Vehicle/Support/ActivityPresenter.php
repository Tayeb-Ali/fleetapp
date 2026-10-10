<?php

declare(strict_types=1);

namespace App\Domain\Vehicle\Support;

use Carbon\Carbon;

/**
 * Localizes vehicle_activities rows for the audit log and the per-vehicle
 * timeline (localized action/note/datetime). Storage stays English-neutral;
 * presentation translates via lang/audit.php + lang/stages.php.
 */
final class ActivityPresenter
{
    /** Known action values (audit.actions.*). */
    private const ACTIONS = ['created', 'updated', 'transition', 'deleted', 'restored'];

    /** Known note literals => audit.notes.* key. */
    private const NOTES = [
        'Vehicle purchase registered' => 'vehicle_created',
        'Stage changed' => 'stage_changed',
        'Data updated' => 'data_updated',
        'Vehicle deleted' => 'vehicle_deleted',
        'Vehicle restored' => 'vehicle_restored',
    ];

    public static function action(?string $action): string
    {
        if ($action === null || $action === '') {
            return '';
        }

        if (in_array($action, self::ACTIONS, true)) {
            $key = 'audit.actions.'.$action;
            $label = __($key);

            return $label === $key ? $action : (string) $label;
        }

        return $action;
    }

    public static function note(?string $note): ?string
    {
        if ($note === null || trim($note) === '') {
            return null;
        }

        $key = self::NOTES[$note] ?? null;
        if ($key === null) {
            return $note;
        }

        return (string) __('audit.notes.'.$key);
    }

    public static function stage(?string $stage): string
    {
        if ($stage === null || $stage === '') {
            return '';
        }

        $key = 'stages.'.$stage;
        $label = __($key);

        return $label === $key ? $stage : (string) $label;
    }

    public static function datetime(mixed $value, string $format = 'd M Y H:i'): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return Carbon::parse($value)->translatedFormat($format);
        } catch (\Throwable) {
            return (string) $value;
        }
    }
}
