<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Domain\Identity\Services\SettingsService;
use App\Domain\Identity\Support\DateDisplay;
use App\Domain\Notification\Notifications\FleetNotification;
use App\Domain\Notification\Services\FleetNotifier;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;

/** Admin editor for company, currency and per-transition role permissions. */
class SettingsEditor extends Component
{
    use AuthorizesRequests;

    public string $company = '';

    public string $currency = '';

    public string $dateFormatShort = '';

    public string $dateFormatFull = '';

    /** @var array<string, array{roles: string[], notify: string[]}> */
    public array $perm = [];

    public function mount(SettingsService $settings): void
    {
        $this->company = $settings->getCompany();
        $this->currency = $settings->getCurrency();
        $this->dateFormatShort = $settings->getDateFormatShort();
        $this->dateFormatFull = $settings->getDateFormatFull();

        $stored = $settings->getTransitionPermissions();
        foreach ($this->transitions() as $key => $transition) {
            $this->perm[$key] = [
                'roles' => array_values($stored[$key]['roles'] ?? $transition['roles']),
                'notify' => array_values($stored[$key]['notify'] ?? $stored[$key]['n'] ?? $transition['notify']),
            ];
        }
    }

    public function save(): void
    {
        $this->authorize('viewAny', User::class);

        $settings = app(SettingsService::class);

        $roleKeys = array_keys(User::ROLES);

        $this->validate([
            'company' => ['required', 'string', 'max:80'],
            'currency' => ['required', 'string', 'max:8'],
            'dateFormatShort' => ['required', Rule::in(array_keys(DateDisplay::SHORT_FORMATS))],
            'dateFormatFull' => ['required', Rule::in(array_keys(DateDisplay::FULL_FORMATS))],
            'perm' => ['array'],
            'perm.*.roles' => ['array'],
            'perm.*.roles.*' => [Rule::in($roleKeys)],
            'perm.*.notify' => ['array'],
            'perm.*.notify.*' => [Rule::in($roleKeys)],
        ]);

        $settings->update([
            'company' => $this->company,
            'currency' => $this->currency,
            'date_format_short' => $this->dateFormatShort,
            'date_format_full' => $this->dateFormatFull,
            'perm' => $this->perm,
        ]);

        $this->notifyAdmins();

        session()->flash('status', __('app.saved'));
    }

    /** Best-effort admin notification for a settings change. */
    protected function notifyAdmins(): void
    {
        try {
            app(FleetNotifier::class)->toAdmins(new FleetNotification('system', 'settings_updated', [
                'actor' => auth()->user()->name ?? null,
                'url' => route('settings.index', absolute: false),
            ]));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Transition catalogue: prefer a TransitionRegistry when one exists,
     * otherwise fall back to the hardcoded legacy list (7 transitions).
     *
     * @return array<string, array{label: string, roles: string[], notify: string[]}>
     */
    protected function transitions(): array
    {
        foreach ([
            'App\\Domain\\Fleet\\Transitions\\TransitionRegistry',
            'App\\Domain\\Fleet\\TransitionRegistry',
            'App\\Domain\\Transitions\\TransitionRegistry',
        ] as $candidate) {
            if (class_exists($candidate) && method_exists($candidate, 'all')) {
                return $this->normalizeTransitions($candidate::all());
            }
        }

        return SettingsService::FALLBACK_TRANSITIONS;
    }

    /** @return array<string, array{label: string, roles: string[], notify: string[]}> */
    protected function normalizeTransitions(mixed $raw): array
    {
        $out = [];

        if (! is_iterable($raw)) {
            return SettingsService::FALLBACK_TRANSITIONS;
        }

        foreach ($raw as $key => $transition) {
            $transition = is_object($transition) ? (array) $transition : $transition;
            if (! is_array($transition)) {
                continue;
            }
            $out[(string) $key] = [
                'label' => (string) ($transition['label'] ?? $transition['l'] ?? $key),
                'roles' => array_values((array) ($transition['roles'] ?? [])),
                'notify' => array_values((array) ($transition['notify'] ?? $transition['n'] ?? [])),
            ];
        }

        return $out !== [] ? $out : SettingsService::FALLBACK_TRANSITIONS;
    }

    public function render()
    {
        $this->authorize('viewAny', User::class);

        // Legacy applyPerm filtering: admin/viewer/accountant can never perform a
        // transition; viewer is never notified.
        $performerRoles = array_diff_key(User::ROLES, ['admin' => true, 'viewer' => true, 'accountant' => true]);
        $notifyRoles = array_diff_key(User::ROLES, ['viewer' => true]);

        return view('livewire.admin.settings-editor', [
            'transitions' => $this->transitions(),
            'performerRoles' => $performerRoles,
            'notifyRoles' => $notifyRoles,
            'shortFormats' => $this->formatOptions(DateDisplay::SHORT_FORMATS),
            'fullFormats' => $this->formatOptions(DateDisplay::FULL_FORMATS),
            'shortPreview' => DateDisplay::sample($this->dateFormatShort),
            'fullPreview' => DateDisplay::sample($this->dateFormatFull),
        ]);
    }

    /**
     * @param  array<string, string>  $formats
     * @return array<int, array{value: string, label: string}>
     */
    protected function formatOptions(array $formats): array
    {
        $options = [];

        foreach ($formats as $format => $key) {
            $options[] = [
                'value' => $format,
                'label' => __('admin.date_formats.'.$key).' — '.DateDisplay::sample($format),
            ];
        }

        return $options;
    }
}
