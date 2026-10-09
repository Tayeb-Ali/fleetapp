<div>
  <h1>Audit log</h1>

  @if($entries === [])
    <p style="color:#666;margin-top:12px;">No audit entries yet.</p>
  @else
    <p style="color:#666;font-size:12px;">Latest {{ count($entries) }} entries.</p>
    <div style="overflow-x:auto;margin-top:8px;">
      <table style="width:100%;border-collapse:collapse;">
        <thead>
          <tr style="text-align:left;border-bottom:2px solid #ddd;">
            @foreach($columns as $column)
              <th style="padding:8px;">{{ $column }}</th>
            @endforeach
          </tr>
        </thead>
        <tbody>
          @foreach($entries as $entry)
            <tr style="border-bottom:1px solid #eee;">
              @foreach($columns as $column)
                <td style="padding:8px;">{{ is_scalar($entry[$column] ?? null) ? $entry[$column] : json_encode($entry[$column] ?? null) }}</td>
              @endforeach
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>
