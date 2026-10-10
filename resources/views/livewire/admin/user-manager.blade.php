<div>
    <h1 class="text-lg font-bold text-start">{{ __('nav.users') }}</h1>

    @if (session('status'))
        <div class="border border-green-600/40 bg-green-50 text-green-700 dark:bg-green-950/40 dark:text-green-300 rounded-xl px-3 py-2 my-3 text-sm">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="border border-red-600/40 bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-300 rounded-xl px-3 py-2 my-3 text-sm">{{ session('error') }}</div>
    @endif

    <div class="flex gap-2 my-3">
        <input type="text" wire:model.live="search" placeholder="{{ __('users.search_placeholder') }}" class="flex-1 px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 dark:text-slate-100 text-sm">
        <button wire:click="create" class="px-4 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-semibold whitespace-nowrap">{{ __('users.new_user') }}</button>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-x-auto">
        <table class="w-full border-collapse text-sm">
            <thead>
                <tr class="border-b-2 border-slate-200 dark:border-slate-800 text-start">
                    <th class="p-2 text-start font-semibold">{{ __('users.username') }}</th>
                    <th class="p-2 text-start font-semibold">{{ __('users.name') }}</th>
                    <th class="p-2 text-start font-semibold">{{ __('users.email') }}</th>
                    <th class="p-2 text-start font-semibold">{{ __('users.role') }}</th>
                    <th class="p-2 text-start font-semibold">{{ __('users.active') }}</th>
                    <th class="p-2 text-start font-semibold">{{ __('users.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr class="border-b border-slate-100 dark:border-slate-800 last:border-0">
                        <td class="p-2" dir="ltr"><bdi>{{ $user->username }}</bdi></td>
                        <td class="p-2"><bdi>{{ $user->name }}</bdi></td>
                        <td class="p-2" dir="ltr"><bdi>{{ $user->email ?? '-' }}</bdi></td>
                        <td class="p-2">{{ $roles[$user->role] ?? $user->role }}</td>
                        <td class="p-2">{{ $user->active ? __('users.yes') : __('users.no') }}</td>
                        <td class="p-2 whitespace-nowrap">
                            <span class="inline-flex flex-wrap gap-1.5">
                                <button wire:click="edit({{ $user->id }})" class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 text-xs">{{ __('users.edit') }}</button>
                                <button wire:click="toggleActive({{ $user->id }})" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-white {{ $user->active ? 'bg-red-600' : 'bg-indigo-600' }}">{{ $user->active ? __('users.disable') : __('users.enable') }}</button>
                                <button wire:click="openPasswordReset({{ $user->id }})" class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 text-xs">{{ __('users.reset_password') }}</button>
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-4 text-sm text-slate-500 dark:text-slate-400">{{ __('users.no_users') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $users->links() }}</div>

    @if ($showModal)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 w-full max-w-md">
                <h2 class="font-bold text-start">{{ $editingId ? __('users.edit_user') : __('users.new_user') }}</h2>
                <div class="grid gap-2 mt-3">
                    <label class="block text-xs text-slate-500 dark:text-slate-400">{{ __('users.username') }}
                        <input type="text" wire:model="username" dir="ltr" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 dark:text-slate-100 text-sm mt-1">
                    </label>
                    @error('username')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                    <label class="block text-xs text-slate-500 dark:text-slate-400">{{ __('users.name') }}
                        <input type="text" wire:model="name" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 dark:text-slate-100 text-sm mt-1">
                    </label>
                    @error('name')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                    <label class="block text-xs text-slate-500 dark:text-slate-400">{{ __('users.email_optional') }}
                        <input type="email" wire:model="email" dir="ltr" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 dark:text-slate-100 text-sm mt-1">
                    </label>
                    @error('email')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                    <label class="block text-xs text-slate-500 dark:text-slate-400">{{ __('users.role') }}
                        <select wire:model="role" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 dark:text-slate-100 text-sm mt-1">
                            @foreach ($roles as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    @error('role')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                    @if ($editingId)
                        <label class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400"><input type="checkbox" wire:model="active" class="accent-indigo-600"> {{ __('users.active') }}</label>
                        @error('active')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                    @endif
                    <label class="block text-xs text-slate-500 dark:text-slate-400">{{ $editingId ? __('users.new_password_blank') : __('users.password_min6') }}
                        <input type="password" wire:model="password" dir="ltr" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 dark:text-slate-100 text-sm mt-1">
                    </label>
                    @error('password')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                </div>
                <div class="flex gap-2 justify-end mt-4">
                    <button wire:click="closeModal" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-sm">{{ __('actions.cancel') }}</button>
                    <button wire:click="save" class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold">{{ __('actions.save') }}</button>
                </div>
            </div>
        </div>
    @endif

    @if ($showPasswordModal)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 w-full max-w-sm">
                <h2 class="font-bold text-start">{{ __('users.reset_password') }}</h2>
                <div class="grid gap-2 mt-3">
                    <label class="block text-xs text-slate-500 dark:text-slate-400">{{ __('users.new_password') }}
                        <input type="password" wire:model="newPassword" dir="ltr" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 dark:text-slate-100 text-sm mt-1">
                    </label>
                    @error('newPassword')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                </div>
                <div class="flex gap-2 justify-end mt-4">
                    <button wire:click="closeModal" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-sm">{{ __('actions.cancel') }}</button>
                    <button wire:click="resetPassword" class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold">{{ __('users.reset') }}</button>
                </div>
            </div>
        </div>
    @endif
</div>
