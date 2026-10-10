<div>
    @if (session('attachments-ok'))
        <div class="rounded bg-green-100 dark:bg-green-900 px-3 py-2 text-sm text-green-800 dark:text-green-100">{{ session('attachments-ok') }}</div>
    @endif

    <form wire:submit.prevent="upload" class="mt-3 flex items-center gap-2">
        <input type="file" wire:model="file" class="text-sm" />
        <button type="submit" class="rounded bg-indigo-600 px-3 py-1.5 text-sm text-white">{{ __('attachments.upload') }}</button>
        <div wire:loading wire:target="file,upload" class="text-xs text-slate-500 dark:text-slate-400">{{ __('attachments.uploading') }}</div>
    </form>
    @error('file') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror

    <ul class="mt-4 divide-y divide-slate-200 dark:divide-slate-800">
        @forelse ($attachments as $a)
            <li class="flex items-center justify-between py-2 text-sm">
                <span>
                    <span class="font-medium">{{ $a->original_name }}</span>
                    <span class="text-slate-500 dark:text-slate-400">({{ number_format((int) $a->size / 1024, 1) }} KB)</span>
                </span>
                <span class="flex gap-2">
                    <a href="{{ route('attachments.download', $a) }}" class="text-indigo-600 dark:text-indigo-400 underline">{{ __('attachments.download') }}</a>
                    <button wire:click="delete({{ $a->id }})" wire:confirm="{{ __('attachments.delete_confirm') }}"
                        class="text-red-600 dark:text-red-400 underline">{{ __('actions.delete') }}</button>
                </span>
            </li>
        @empty
            <li class="py-2 text-sm text-slate-500 dark:text-slate-400">{{ __('attachments.empty') }}</li>
        @endforelse
    </ul>
</div>
