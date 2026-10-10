<div>
    <h1 class="text-lg font-bold text-start">{{ __('nav.audit') }}</h1>

    @if ($entries === [])
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-3">{{ __('app.no_results') }}</p>
    @else
        <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('app.total') }}: {{ count($entries) }}</p>
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-x-auto mt-2">
            <table class="w-full border-collapse text-sm">
                <thead>
                    <tr class="border-b-2 border-slate-200 dark:border-slate-800 text-start">
                        @foreach ($columns as $column)
                            <th class="p-2 text-start font-semibold" dir="ltr">{{ $column }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($entries as $entry)
                        <tr class="border-b border-slate-100 dark:border-slate-800 last:border-0">
                            @foreach ($columns as $column)
                                <td class="p-2 text-start align-top">{{ is_scalar($entry[$column] ?? null) ? $entry[$column] : json_encode($entry[$column] ?? null) }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
