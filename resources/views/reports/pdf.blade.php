<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="auto">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>{{ $title ?? __('reports.report_fallback') }}</title>
<style>
  /* Arabic-safe: DejaVu Sans ships with dompdf and covers Arabic glyphs. */
  body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #111; margin: 0; }
  h1 { font-size: 16px; margin: 0 0 4px 0; }
  .meta { color: #666; font-size: 8px; margin-bottom: 12px; }
  table { width: 100%; border-collapse: collapse; }
  th { background: #1f3a8a; color: #fff; padding: 5px 4px; text-align: start; font-size: 8px; }
  td { padding: 4px; border-bottom: 1px solid #ddd; font-size: 8px; word-wrap: break-word; }
  tr:nth-child(even) td { background: #f2f4f8; }
  .footer { margin-top: 12px; color: #888; font-size: 7px; }
  .empty { padding: 16px; color: #666; }
</style>
</head>
<body>
<h1>{{ $title ?? __('reports.report_fallback') }}</h1>
<div class="meta">{{ __('reports.generated') }} {{ $generated ?? now()->format('Y-m-d H:i').' UTC' }} | {{ count($rows ?? []) }} {{ __('reports.records') }}</div>
@if(empty($rows))
  <div class="empty">{{ __('app.no_results') }}</div>
@else
<table dir="auto">
  <thead>
    <tr>
      @foreach($columns as $col)
        <th>{{ $col }}</th>
      @endforeach
    </tr>
  </thead>
  <tbody>
    @foreach($rows as $row)
      <tr>
        @foreach((array) $row as $cell)
          <td>{{ $cell }}</td>
        @endforeach
      </tr>
    @endforeach
  </tbody>
</table>
@endif
<div class="footer">{{ config('app.name') }}</div>
</body>
</html>
