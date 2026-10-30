<div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <b class="text-sm">{{ __('attachments.proofs') }} — {{ $categoryLabel }}</b>
        <span class="text-xs text-slate-500 dark:text-slate-400">{{ __('attachments.count', ['count' => $attachments->count()]) }}</span>
    </div>

    @if (session('attachments-ok-'.$category))
        <div class="mt-2 rounded-lg bg-green-100 dark:bg-green-900 px-3 py-1.5 text-xs text-green-800 dark:text-green-100">{{ session('attachments-ok-'.$category) }}</div>
    @endif

    @if ($canUpload)
    <form wire:submit="upload" class="mt-2 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
        <label class="block text-xs text-slate-500 dark:text-slate-400 sm:col-span-2">
            {{ __('attachments.files') }}
            <input type="file" wire:model="files" multiple accept=".pdf,.png,.jpg,.jpeg,.webp" class="mt-1 w-full text-xs">
        </label>
        <label class="block text-xs text-slate-500 dark:text-slate-400">
            {{ __('attachments.note') }}
            <input type="text" wire:model="note" maxlength="255" class="mt-1 w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-2 py-1.5 text-sm">
        </label>
        <div class="grid grid-cols-2 gap-1">
            <label class="block text-xs text-slate-500 dark:text-slate-400">
                {{ __('attachments.amount') }}
                <input type="number" step="0.01" min="0" wire:model="amount" dir="ltr" class="mt-1 w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-2 py-1.5 text-sm">
            </label>
            <label class="block text-xs text-slate-500 dark:text-slate-400">
                {{ __('attachments.currency') }}
                <select wire:model="currency" class="mt-1 w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-2 py-1.5 text-sm">
                    <option value="">—</option>
                    @foreach ($currencyOptions as $code => $label)
                        <option value="{{ $code }}">{{ $code }}</option>
                    @endforeach
                </select>
            </label>
        </div>
        <div class="flex items-end sm:col-span-2 lg:col-span-4">
            <button type="submit" class="rounded-xl bg-indigo-600 px-3 py-2 text-xs font-semibold text-white" wire:loading.attr="disabled" wire:target="files,upload">
                <span wire:loading.remove wire:target="files,upload">{{ __('attachments.upload') }}</span>
                <span wire:loading wire:target="files,upload">{{ __('attachments.uploading') }}</span>
            </button>
            <span class="ms-2 text-[11px] text-slate-500 dark:text-slate-400">{{ __('attachments.proof_hint') }}</span>
        </div>
    </form>

    @error('files')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
    @error('files.*')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
    @endif

    <ul class="mt-3 divide-y divide-slate-100 dark:divide-slate-800">
        @forelse ($attachments as $a)
            <li class="flex flex-wrap items-center justify-between gap-2 py-2 text-sm" wire:key="proof-{{ $a->id }}">
                <span class="min-w-0">
                    <span class="font-medium"><bdi>{{ $a->original_name }}</bdi></span>
                    <span class="text-xs text-slate-500 dark:text-slate-400">
                        ({{ number_format((int) $a->size / 1024, 1) }} KB)
                        @if ($a->amount !== null)
                            · <bdi>{{ number_format((float) $a->amount, 2) }} {{ $a->currency }}</bdi>
                        @endif
                        @if ($a->note)
                            · <bdi>{{ $a->note }}</bdi>
                        @endif
                        @if ($a->user)
                            · {{ $a->user->name }}
                        @endif
                    </span>
                </span>
                <span class="flex gap-2 text-xs">
                    <a href="{{ route('attachments.download', $a) }}" class="text-indigo-600 dark:text-indigo-400 underline">{{ __('attachments.download') }}</a>
                    @if ((auth()->user()->role ?? '') === 'admin')
                        <button wire:click="delete({{ $a->id }})" wire:confirm="{{ __('attachments.delete_confirm') }}" class="text-red-600 dark:text-red-400 underline">{{ __('actions.delete') }}</button>
                    @endif
                </span>
            </li>
        @empty
            <li class="py-2 text-xs text-slate-500 dark:text-slate-400">{{ __('attachments.empty') }}</li>
        @endforelse
    </ul>
</div>
