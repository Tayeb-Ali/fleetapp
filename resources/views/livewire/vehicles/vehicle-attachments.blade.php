<div>
    @if (session('attachments-ok'))
        <div class="rounded bg-green-100 px-3 py-2 text-sm text-green-800">{{ session('attachments-ok') }}</div>
    @endif

    <form wire:submit.prevent="upload" class="mt-3 flex items-center gap-2">
        <input type="file" wire:model="file" class="text-sm" />
        <button type="submit" class="rounded bg-blue-600 px-3 py-1.5 text-sm text-white">Upload</button>
        <div wire:loading wire:target="file,upload" class="text-xs text-gray-500">Uploading…</div>
    </form>
    @error('file') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

    <ul class="mt-4 divide-y divide-gray-200">
        @forelse ($attachments as $a)
            <li class="flex items-center justify-between py-2 text-sm">
                <span>
                    <span class="font-medium">{{ $a->original_name }}</span>
                    <span class="text-gray-500">({{ number_format((int) $a->size / 1024, 1) }} KB)</span>
                </span>
                <span class="flex gap-2">
                    <a href="{{ route('attachments.download', $a) }}" class="text-blue-600 underline">Download</a>
                    <button wire:click="delete({{ $a->id }})" wire:confirm="Delete this file?"
                        class="text-red-600 underline">Delete</button>
                </span>
            </li>
        @empty
            <li class="py-2 text-sm text-gray-500">No attachments yet.</li>
        @endforelse
    </ul>
</div>
