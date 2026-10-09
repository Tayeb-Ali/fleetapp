<div>
  <p><a href="{{ route('reports.index') }}">&larr; All reports</a></p>
  <h1>{{ $report->titleKey() }}</h1>
  <p style="color:#666;">{{ count($rows) }} records</p>
  <p>
    <a href="{{ route('reports.export', $report->key()) }}?format=xlsx"><button type="button">Download XLSX</button></a>
    <a href="{{ route('reports.export', $report->key()) }}?format=pdf"><button type="button">Download PDF</button></a>
    <button type="button" onclick="window.print()">Print</button>
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
          <tr><td colspan="{{ count($columns) }}">No records.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
