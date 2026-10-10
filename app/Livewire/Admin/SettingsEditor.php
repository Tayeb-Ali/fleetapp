<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Domain\Identity\Services\SettingsService;
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

    /** @var array<string, array{roles: string[], notify: string[]}> */
    public array $perm = [];

    public function mount(SettingsService $settings): void
    {
        $this->company = $settings->getCompany();
        $this->currency = $settings->getCurrency();

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
            'perm' => ['array'],
            'perm.*.roles' => ['array'],
            'perm.*.roles.*' => [Rule::in($roleKeys)],
            'perm.*.notify' => ['array'],
            'perm.*.notify.*' => [Rule::in($roleKeys)],
        ]);

        $settings->update([
            'company' => $this->company,
            'currency' => $this->currency,
            'perm' => $this->perm,
        ]);

        session()->flash('status', __('app.saved'));
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
        ]);
    }
}
