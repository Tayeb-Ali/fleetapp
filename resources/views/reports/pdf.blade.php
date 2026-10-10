@php
  // Per-language PDF design: Cairo (Arabic transliteration-safe, covers all
  // pre-shaped glyphs) for Arabic, Inter for English, DejaVu as fallback.
  $rtl = $rtl ?? app()->getLocale() === 'ar';
  $fontDir = str_replace('\\', '/', storage_path('fonts'));
  $font = $rtl ? 'Cairo' : 'Inter';
  $ta = $rtl ? 'right' : 'left'; // explicit sides: dompdf logical values are unreliable
  $compact = $compact ?? false;
  $bodySize = $compact ? '8.5px' : '10px';
  $headSize = $compact ? '9px' : '10.5px';
  $cellPad = $compact ? '4px 5px' : '6px 8px';

  // A column is numeric when every non-empty cell holds only digits/punctuation
  // (optionally followed by the currency code). Headers then match their cells.
  $cur = preg_quote((string) ($currency ?? ''), '/');
  $numRe = '/^[\d\s,.\-%+–—\/:]+(\s*'.$cur.')?$/u';
  $isNum = function ($v) use ($numRe) {
      $s = trim((string) $v);

      return $s !== '' && preg_match('/\d/u', $s) && preg_match($numRe, $s);
  };
  $colCount = count($columns ?? []);
  $colNumeric = [];
  for ($i = 0; $i < $colCount; $i++) {
      $vals = [];
      foreach (($rows ?? []) as $r) {
          $r = array_values((array) $r);
          if (isset($r[$i]) && trim((string) $r[$i]) !== '') {
              $vals[] = $r[$i];
          }
      }
      $numeric = $vals !== [];
      foreach ($vals as $v) {
          if (! $isNum($v)) {
              $numeric = false;
              break;
          }
      }
      $colNumeric[$i] = $numeric;
  }

  // Pre-shaped Arabic is stored in visual order: if dompdf wrapped such a
  // line, the lines would stack bottom-up (inverted). Short strings fit on
  // one line, so keep them unwrapped; only long strings may wrap. Wide
  // (compact) tables keep wrapping so all columns still fit the page.
  $noWrap = fn ($v) => ! ($compact ?? false) || mb_strlen((string) $v, 'UTF-8') <= 30;
  $headNoWrap = ! ($compact ?? false);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>{{ $title ?? __('reports.report_fallback') }}</title>
<style>
  @font-face { font-family: 'Cairo'; font-weight: 400; src: url('file://{{ $fontDir }}/Cairo-Regular.ttf') format('truetype'); }
  @font-face { font-family: 'Cairo'; font-weight: 700; src: url('file://{{ $fontDir }}/Cairo-Bold.ttf') format('truetype'); }
  @font-face { font-family: 'Inter'; font-weight: 400; src: url('file://{{ $fontDir }}/Inter-Regular.ttf') format('truetype'); }
  @font-face { font-family: 'Inter'; font-weight: 700; src: url('file://{{ $fontDir }}/Inter-Bold.ttf') format('truetype'); }
  body { font-family: '{{ $font }}', 'DejaVu Sans', sans-serif; font-size: {{ $bodySize }}; color: #0f172a; margin: 0; }
  h1 { font-size: {{ $compact ? '17px' : '20px' }}; font-weight: 700; margin: 0 0 2px 0; text-align: {{ $ta }}; }
  .meta { color: #64748b; font-size: {{ $compact ? '8px' : '9px' }}; margin-bottom: 8px; text-align: {{ $ta }}; }
  .rule { height: 3px; background: #1f3a8a; margin-bottom: 12px; }
  table { width: 100%; border-collapse: collapse; border: 1px solid #cbd5e1; }
  th { background: #1f3a8a; color: #fff; font-weight: 700; font-size: {{ $headSize }}; padding: {{ $cellPad }}; }
  td { padding: {{ $cellPad }}; border-bottom: 1px solid #e2e8f0; font-size: {{ $bodySize }}; }
  tr:nth-child(even) td { background: #f6f8fc; }
  .txt { text-align: {{ $ta }}; }
  .num { text-align: center; white-space: nowrap; }
  .nw { white-space: nowrap; }
  .footer { margin-top: 14px; padding-top: 6px; border-top: 1px solid #cbd5e1; color: #64748b; font-size: 8px; text-align: {{ $ta }}; }
  .empty { padding: 20px; color: #64748b; text-align: center; border: 1px solid #cbd5e1; }
</style>
</head>
<body>
<h1>{{ $title ?? __('reports.report_fallback') }}</h1>
<div class="meta">{{ $meta ?? '' }}</div>
<div class="rule"></div>
@if(empty($rows))
  <div class="empty">{{ $empty ?? __('app.no_results') }}</div>
@else
<table>
  <thead>
    <tr>
      @foreach($columns as $i => $col)
        <th class="{{ ($colNumeric[$i] ?? false) ? 'num' : 'txt' }}{{ $headNoWrap ? ' nw' : '' }}">@foreach(explode("\n", (string) $col) as $ln){{ $ln }}@if(!$loop->last)<br>@endif @endforeach</th>
      @endforeach
    </tr>
  </thead>
  <tbody>
    @foreach($rows as $row)
      <tr>
        @foreach(array_values((array) $row) as $i => $cell)
          <td class="{{ ($colNumeric[$i] ?? false) ? 'num' : 'txt' }}{{ $noWrap($cell) ? ' nw' : '' }}">{{ $cell === '' || $cell === null ? '—' : $cell }}</td>
        @endforeach
      </tr>
    @endforeach
  </tbody>
</table>
@endif
<div class="footer">{{ $footer ?? config('app.name') }}</div>
</body>
</html>
