<div>
  <p><a href="{{ route('reports.index') }}"><span aria-hidden="true" class="inline-block rtl:rotate-180">&larr;</span> {{ __('reports.all_reports') }}</a></p>
  <h1 class="text-xl font-semibold text-slate-900 dark:text-slate-100">{{ $report->titleKey() }}</h1>
  <p class="text-slate-500 dark:text-slate-400">{{ count($rows) }} {{ __('reports.records') }}</p>
  <p>
    <a href="{{ route('reports.export', $report->key()) }}?format=xlsx"><button type="button">{{ __('reports.download_xlsx') }}</button></a>
    <a href="{{ route('reports.export', $report->key()) }}?format=pdf"><button type="button">{{ __('reports.download_pdf') }}</button></a>
    <button type="button" onclick="window.print()">{{ __('reports.print') }}</button>
  </p>
  <div style="overflow-x:auto;">
    <table border="1" cellpadding="6" cellspacing="0" dir="auto" style="border-collapse:collapse;min-width:600px;">
      <thead>
        <tr>
          @foreach($columns as $col)
            <th>{{ $col }}</th>
          @endforeach
        </tr>
      </thead>
      <tbody>
        @forelse($rows as $row)
          <tr>
            @foreach((array) $row as $cell)
              <td>{{ $cell }}</td>
            @endforeach
          </tr>
        @empty
          <tr><td colspan="{{ count($columns) }}">{{ __('app.no_results') }}</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
