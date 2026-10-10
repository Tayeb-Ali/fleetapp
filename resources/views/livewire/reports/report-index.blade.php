<div>
  <h1 class="text-xl font-semibold text-slate-900 dark:text-slate-100">{{ __('nav.reports') }}</h1>
  <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
    @foreach($reports as $report)
      <div class="rounded-lg border border-slate-200 bg-white p-3 dark:border-slate-800 dark:bg-slate-900">
        <strong class="text-slate-900 dark:text-slate-100">{{ $report->titleKey() }}</strong>
        <div class="my-1 text-xs text-slate-500 dark:text-slate-400">{{ count($report->rows()) }} {{ __('reports.rows') }} &middot; {{ count($report->columns()) }} {{ __('reports.columns') }}</div>
        <a href="{{ route('reports.show', $report->key()) }}">{{ __('actions.view') }}</a>
        &middot; <a href="{{ route('reports.export', $report->key()) }}?format=xlsx">XLSX</a>
        &middot; <a href="{{ route('reports.export', $report->key()) }}?format=pdf">PDF</a>
      </div>
    @endforeach
  </div>
</div>
