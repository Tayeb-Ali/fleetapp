<div>
  <div class="mt-3 flex flex-wrap items-start justify-between gap-3">
    <div>
      <a href="{{ route('reports.index') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:underline dark:text-indigo-400">
        <svg class="h-3.5 w-3.5 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>
        {{ __('reports.all_reports') }}
      </a>
      <h1 class="mt-1 text-lg font-bold">{{ $report->title() }}</h1>
      <p class="text-xs text-slate-500 dark:text-slate-400">{{ count($rows) }} {{ __('reports.records') }}</p>
    </div>

    <div class="flex flex-wrap items-center gap-2 print:hidden">
      <a href="{{ route('reports.export', $report->key()) }}?format=xlsx"
         class="rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:border-slate-300 dark:border-slate-700 dark:text-slate-300">
        {{ __('reports.download_xlsx') }}
      </a>
      <a href="{{ route('reports.export', $report->key()) }}?format=pdf"
         class="rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:border-slate-300 dark:border-slate-700 dark:text-slate-300">
        {{ __('reports.download_pdf') }}
      </a>
      <button type="button" onclick="window.print()"
              class="rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:border-slate-300 dark:border-slate-700 dark:text-slate-300">
        {{ __('reports.print') }}
      </button>
    </div>
  </div>

  <div class="mt-3 overflow-x-auto rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
    <table class="w-full min-w-max border-collapse text-sm">
      <thead>
        <tr class="border-b border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-800/60">
          @foreach($columns as $col)
            <th class="whitespace-nowrap px-3 py-2.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300">{{ $col }}</th>
          @endforeach
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
        @forelse($rows as $row)
          <tr class="odd:bg-white even:bg-slate-50/60 hover:bg-indigo-50/50 dark:odd:bg-slate-900 dark:even:bg-slate-800/40 dark:hover:bg-indigo-950/30">
            @foreach((array) $row as $cell)
              <td class="whitespace-nowrap px-3 py-2 text-start align-top tabular-nums text-slate-700 dark:text-slate-200">
                @if ($cell === '' || $cell === null)
                  <span class="text-slate-300 dark:text-slate-600">&mdash;</span>
                @else
                  <bdi>{{ $cell }}</bdi>
                @endif
              </td>
            @endforeach
          </tr>
        @empty
          <tr>
            <td colspan="{{ count($columns) }}" class="px-3 py-10 text-center text-sm text-slate-500">
              {{ __('app.no_results') }}
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>