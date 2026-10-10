<div>
    <div class="mt-3 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-lg font-bold">{{ __('audit.title') }}</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('app.total') }}: {{ count($entries) }} {{ __('audit.entries') }}</p>
        </div>
    </div>

    @if ($entries->isEmpty())
        <div class="mt-3 rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center dark:border-slate-700 dark:bg-slate-900">
            <p class="text-sm font-semibold">{{ __('app.no_results') }}</p>
        </div>
    @else
        <div class="mt-3 overflow-x-auto rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
            <table class="w-full min-w-max border-collapse text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-800/60">
                        <th class="whitespace-nowrap px-3 py-2.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300">{{ __('audit.date') }}</th>
                        <th class="whitespace-nowrap px-3 py-2.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300">{{ __('audit.action') }}</th>
                        <th class="whitespace-nowrap px-3 py-2.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300">{{ __('audit.vehicle') }}</th>
                        <th class="whitespace-nowrap px-3 py-2.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300">{{ __('audit.from_stage') }}</th>
                        <th class="whitespace-nowrap px-3 py-2.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300">{{ __('audit.to_stage') }}</th>
                        <th class="whitespace-nowrap px-3 py-2.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300">{{ __('audit.user') }}</th>
                        <th class="whitespace-nowrap px-3 py-2.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300">{{ __('audit.note') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($entries as $entry)
                        @php
                            $badge = match ($entry['action']) {
                                'created' => 'bg-teal-100 text-teal-700 dark:bg-teal-950 dark:text-teal-300',
                                'updated' => 'bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300',
                                'transition' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300',
                                'deleted' => 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300',
                                'restored' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
                                default => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
                            };
                        @endphp
                        <tr class="odd:bg-white even:bg-slate-50/60 hover:bg-indigo-50/50 dark:odd:bg-slate-900 dark:even:bg-slate-800/40 dark:hover:bg-indigo-950/30">
                            <td class="whitespace-nowrap px-3 py-2 align-top text-xs text-slate-500 dark:text-slate-400">
                                <bdi>{{ $entry['date'] }}</bdi>
                            </td>
                            <td class="whitespace-nowrap px-3 py-2 align-top">
                                <span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $badge }}">{{ $entry['action_label'] }}</span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-2 align-top">
                                <a href="{{ url(route('vehicles.show', $entry['vin'], false)) }}" class="font-mono text-xs font-semibold text-indigo-600 hover:underline dark:text-indigo-400" dir="ltr">
                                    {{ $entry['vin'] }}
                                </a>
                            </td>
                            <td class="whitespace-nowrap px-3 py-2 align-top">
                                @if ($entry['from'])
                                    <x-stage-pill :stage="$entry['from']" />
                                @else
                                    <span class="text-slate-300 dark:text-slate-600">&mdash;</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-3 py-2 align-top">
                                @if ($entry['to'])
                                    <x-stage-pill :stage="$entry['to']" />
                                @else
                                    <span class="text-slate-300 dark:text-slate-600">&mdash;</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-3 py-2 align-top text-xs text-slate-600 dark:text-slate-300">{{ $entry['user'] }}</td>
                            <td class="whitespace-nowrap px-3 py-2 align-top text-xs text-slate-500 dark:text-slate-400">
                                {{ $entry['note'] ?: '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>