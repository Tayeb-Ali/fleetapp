<x-layouts.app>
    <div class="my-3">
        <a href="{{ route('vehicles.create') }}" class="block text-center px-4 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-semibold">＋ {{ __('actions.register') }}</a>
    </div>
    <livewire:vehicles.vehicle-list />
</x-layouts.app>
