<div>
  <div class="mt-3 flex flex-wrap items-start justify-between gap-3">
    <div>
      <h1 class="text-lg font-bold">{{ __('nav.reports') }}</h1>
      <p class="text-xs text-slate-500 dark:text-slate-400">{{ count($reports) }} {{ __('reports.records') }}</p>
    </div>
  </div>

  <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
    @foreach($reports as $report)
      @php $cols = count($report->columns()); @endphp
      <div class="flex flex-col rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
        <h2 class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ $report->title() }}</h2>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $cols }} {{ __('reports.columns') }}</p>
        <div class="mt-3 flex flex-wrap items-center gap-2">
          <a href="{{ route('reports.show', $report->key()) }}"
             class="inline-flex items-center gap-1 rounded-xl bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">
            {{ __('actions.view') }}
          </a>
          <a href="{{ route('reports.export', $report->key()) }}?format=xlsx"
             class="rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:border-slate-300 dark:border-slate-700 dark:text-slate-300">
            XLSX
          </a>
          <a href="{{ route('reports.export', $report->key()) }}?format=pdf"
             class="rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:border-slate-300 dark:border-slate-700 dark:text-slate-300">
            PDF
          </a>
        </div>
      </div>
    @endforeach
  </div>
</div>