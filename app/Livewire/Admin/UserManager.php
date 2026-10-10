<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Domain\Notification\Notifications\FleetNotification;
use App\Domain\Notification\Services\FleetNotifier;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

/** Admin user list with search, create/edit modal, active toggle, password reset. */
class UserManager extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $username = '';

    public string $name = '';

    public string $email = '';

    public string $role = 'viewer';

    public bool $active = true;

    public string $password = '';

    public bool $showPasswordModal = false;

    public ?int $passwordUserId = null;

    public string $newPassword = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /** @return array<string, mixed> */
    protected function formRules(?int $ignoreId = null): array
    {
        return [
            'username' => ['required', 'string', 'max:50', Rule::unique('users', 'username')->ignore($ignoreId)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'active' => ['sometimes', 'boolean'],
            'password' => $ignoreId
                ? ['nullable', 'string', 'min:6']
                : ['required', 'string', 'min:6'],
        ];
    }

    public function create(): void
    {
        $this->authorize('create', User::class);
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $target = User::findOrFail($id);
        $this->authorize('update', $target);

        $this->resetForm();
        $this->editingId = $target->id;
        $this->username = $target->username;
        $this->name = $target->name;
        $this->email = (string) ($target->email ?? '');
        $this->role = $target->role;
        $this->active = (bool) $target->active;
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate($this->formRules($this->editingId));

        if ($this->editingId) {
            $target = User::findOrFail($this->editingId);
            $this->authorize('update', $target);

            // Legacy server.js:182 — cannot disable self or change own role.
            if ($target->is(auth()->user()) && ($data['role'] !== 'admin' || ($data['active'] ?? true) !== true)) {
                $this->addError('role', __('users.cannot_change_self'));
                $this->addError('active', __('users.cannot_change_self'));

                return;
            }

            $target->fill([
                'username' => strtolower(trim($data['username'])),
                'name' => trim($data['name']),
                'email' => ($data['email'] ?? '') !== '' ? trim($data['email']) : null,
                'role' => $data['role'],
                'active' => (bool) ($data['active'] ?? $target->active),
            ]);
            if (($data['password'] ?? '') !== '') {
                $target->password = $data['password'];
            }
            $target->save();

            $this->notifyAdmins('updated', $target);

            session()->flash('status', __('users.updated'));
        } else {
            $this->authorize('create', User::class);

            $created = User::create([
                'username' => strtolower(trim($data['username'])),
                'name' => trim($data['name']),
                'email' => ($data['email'] ?? '') !== '' ? trim($data['email']) : null,
                'role' => $data['role'],
                'active' => true,
                'password' => $data['password'],
            ]);

            $this->notifyAdmins('created', $created, $created->role);

            session()->flash('status', __('users.created'));
        }

        $this->closeModal();
    }

    public function toggleActive(int $id): void
    {
        $target = User::findOrFail($id);

        if ($target->is(auth()->user())) {
            session()->flash('error', __('users.cannot_disable_self'));

            return;
        }

        $this->authorize('toggleActive', $target);
        $target->active = ! $target->active;
        $target->save();

        $this->notifyAdmins($target->active ? 'enabled' : 'disabled', $target);

        session()->flash('status', $target->active ? __('users.enabled') : __('users.disabled_flash'));
    }

    public function openPasswordReset(int $id): void
    {
        $target = User::findOrFail($id);
        $this->authorize('resetPassword', $target);

        $this->passwordUserId = $target->id;
        $this->newPassword = '';
        $this->showPasswordModal = true;
    }

    public function resetPassword(): void
    {
        $target = User::findOrFail($this->passwordUserId);
        $this->authorize('resetPassword', $target);

        $this->validate(['newPassword' => ['required', 'string', 'min:6']]);

        $target->password = $this->newPassword;
        $target->save();

        $this->notifyAdmins('password_reset', $target);

        $this->showPasswordModal = false;
        $this->passwordUserId = null;
        $this->newPassword = '';

        session()->flash('status', __('users.password_reset_done'));
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->showPasswordModal = false;
        $this->resetForm();
        $this->resetValidation();
    }

    /** Best-effort admin notification for account changes. */
    protected function notifyAdmins(string $event, User $subject, ?string $role = null): void
    {
        try {
            app(FleetNotifier::class)->toAdmins(new FleetNotification('user', $event, [
                'subject_name' => $subject->name,
                'subject_role' => $role ?? $subject->role,
                'actor' => auth()->user()->name ?? null,
                'url' => route('users.index', absolute: false),
            ]));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->username = '';
        $this->name = '';
        $this->email = '';
        $this->role = 'viewer';
        $this->active = true;
        $this->password = '';
        $this->passwordUserId = null;
        $this->newPassword = '';
    }

    public function render()
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->when($this->search !== '', function ($query) {
                $term = '%'.strtolower(trim($this->search)).'%';
                $query->whereRaw('LOWER(username) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(name) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(COALESCE(email, "")) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(role) LIKE ?', [$term]);
            })
            ->orderBy('id')
            ->paginate(12);

        return view('livewire.admin.user-manager', [
            'users' => $users,
            'roles' => User::ROLES,
        ]);
    }
}
