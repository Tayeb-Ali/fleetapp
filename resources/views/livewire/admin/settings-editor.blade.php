<div>
    <h1 class="text-lg font-bold text-start">{{ __('nav.settings') }}</h1>

    @if (session('status'))
        <div class="border border-green-600/40 bg-green-50 text-green-700 dark:bg-green-950/40 dark:text-green-300 rounded-xl px-3 py-2 my-3 text-sm max-w-3xl">{{ session('status') }}</div>
    @endif

    <form wire:submit="save" class="grid gap-3 max-w-3xl mt-3">
        <label class="block text-xs text-slate-500 dark:text-slate-400">{{ __('admin.company') }}
            <input type="text" wire:model="company" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 dark:text-slate-100 text-sm mt-1">
        </label>
        @error('company')<span class="text-xs text-red-600">{{ $message }}</span>@enderror

        <label class="block text-xs text-slate-500 dark:text-slate-400">{{ __('admin.currency') }}
            <input type="text" wire:model="currency" dir="ltr" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 dark:text-slate-100 text-sm mt-1 text-start">
        </label>
        @error('currency')<span class="text-xs text-red-600">{{ $message }}</span>@enderror

        <h2 class="font-bold mt-2 text-start">{{ __('admin.permissions_title') }}</h2>
        @foreach ($transitions as $key => $transition)
            @php $tKey = 'vehicle.transitions.'.$key; $tLabel = __($tKey) === $tKey ? ($transition['label'] ?? $key) : __($tKey); @endphp
            <fieldset class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4">
                <legend class="px-1.5 text-sm"><strong>{{ $tLabel }}</strong> <span class="text-slate-500 dark:text-slate-400" dir="ltr">({{ $key }})</span></legend>
                <div class="text-xs text-slate-500 dark:text-slate-400 mb-1 text-start">{{ __('admin.who_may') }}</div>
                <div class="flex flex-wrap gap-x-4 gap-y-2 mb-3">
                    @foreach ($performerRoles as $roleKey => $roleLabel)
                        <label class="flex items-center gap-1.5 text-xs"><input type="checkbox" wire:model="perm.{{ $key }}.roles" value="{{ $roleKey }}" class="accent-indigo-600"> {{ __('roles.'.$roleKey) }}</label>
                    @endforeach
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 mb-1 text-start">{{ __('admin.notify') }}</div>
                <div class="flex flex-wrap gap-x-4 gap-y-2">
                    @foreach ($notifyRoles as $roleKey => $roleLabel)
                        <label class="flex items-center gap-1.5 text-xs"><input type="checkbox" wire:model="perm.{{ $key }}.notify" value="{{ $roleKey }}" class="accent-indigo-600"> {{ __('roles.'.$roleKey) }}</label>
                    @endforeach
                </div>
            </fieldset>
        @endforeach

        <div>
            <button type="submit" class="px-4 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-semibold">{{ __('actions.save') }}</button>
        </div>
    </form>
</div>
