<div>
  <h1>Reports</h1>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px;margin-top:12px;">
    @foreach($reports as $report)
      <div style="border:1px solid #ddd;border-radius:8px;padding:12px;">
        <strong>{{ $report->titleKey() }}</strong>
        <div style="color:#666;font-size:12px;margin:4px 0 8px 0;">{{ count($report->rows()) }} rows &middot; {{ count($report->columns()) }} columns</div>
        <a href="{{ route('reports.show', $report->key()) }}">View</a>
        &middot; <a href="{{ route('reports.export', $report->key()) }}?format=xlsx">XLSX</a>
        &middot; <a href="{{ route('reports.export', $report->key()) }}?format=pdf">PDF</a>
      </div>
    @endforeach
  </div>
</div>
