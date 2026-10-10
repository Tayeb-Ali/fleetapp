<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="auto">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>{{ __('documents.sale_order') }} - {{ $vehicle['ord_no'] ?? $vehicle['vin'] ?? '' }}</title>
<style>
  body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #111; margin: 40px; }
  .banner { background: #1f3a8a; color: #fff; padding: 18px 24px; margin: -40px -40px 20px -40px; }
  .banner h1 { margin: 0; font-size: 20px; }
  .banner .co { font-size: 16px; font-weight: bold; }
  .row { display: table; width: 100%; margin-bottom: 6px; }
  .row > div { display: table-cell; }
  .end { text-align: end; }
  h2 { font-size: 11px; color: #1f3a8a; text-transform: uppercase; border-bottom: 1px solid #ddd; padding-bottom: 4px; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
  td { padding: 4px 0; vertical-align: top; }
  td.k { color: #666; width: 170px; }
  td.v { font-weight: bold; }
  .sigs { margin-top: 60px; display: table; width: 100%; }
  .sig { display: table-cell; width: 50%; padding-inline-end: 40px; }
  .line { border-top: 1px solid #000; margin-top: 60px; padding-top: 4px; color: #666; font-size: 10px; }
  .footer { margin-top: 40px; color: #888; font-size: 8px; }
</style>
</head>
<body>
@php
  $fmt = fn($n) => number_format((float) ($n ?? 0), 2, '.', ',').' '.($currency ?? 'USD');
  $balance = ((float) ($vehicle['price'] ?? 0)) - ((float) ($vehicle['paid'] ?? 0));
@endphp
<div class="banner">
  <div class="co">{{ $company ?? config('app.name', 'Fleet Tracker') }}</div>
  <h1>{{ __('documents.sale_order') }}</h1>
</div>
<div class="row">
  <div><strong>{{ __('documents.order_no') }}:</strong> <span dir="ltr">{{ $vehicle['ord_no'] ?? '-' }}</span></div>
  <div class="end"><strong>{{ __('documents.date') }}:</strong> <span dir="ltr">{{ $vehicle['sale_date'] ?? substr((string) ($vehicle['updated'] ?? date('Y-m-d')), 0, 10) }}</span></div>
</div>
<h2>{{ __('documents.buyer') }}</h2>
<table dir="auto">
  <tr><td class="k">{{ __('vehicles.client') }}</td><td class="v">{{ $vehicle['client'] ?? '-' }}</td></tr>
  <tr><td class="k">{{ __('vehicles.phone') }}</td><td class="v"><span dir="ltr">{{ $vehicle['phone'] ?? '-' }}</span></td></tr>
</table>
<h2>{{ __('documents.vehicle') }}</h2>
<table dir="auto">
  <tr><td class="k">{{ __('vehicles.brand') }} / {{ __('vehicles.model') }}</td><td class="v">{{ trim(($vehicle['brand'] ?? '').' '.($vehicle['model'] ?? '')) ?: '-' }}</td></tr>
  <tr><td class="k">{{ __('vehicles.vin') }}</td><td class="v"><span dir="ltr">{{ $vehicle['vin'] ?? '-' }}</span></td></tr>
  <tr><td class="k">{{ __('vehicles.fuel') }}</td><td class="v">{{ $vehicle['fuel'] ?? '-' }}</td></tr>
  <tr><td class="k">{{ __('vehicles.bl') }}</td><td class="v"><span dir="ltr">{{ $vehicle['bl'] ?? '-' }}</span></td></tr>
  <tr><td class="k">{{ __('vehicles.loc') }}</td><td class="v">{{ $vehicle['loc'] ?? '-' }}</td></tr>
</table>
<h2>{{ __('documents.payment') }}</h2>
<table>
  <tr><td class="k">{{ __('vehicles.price') }}</td><td class="v"><span dir="ltr">{{ $fmt($vehicle['price'] ?? 0) }}</span></td></tr>
  <tr><td class="k">{{ __('vehicles.paid') }}</td><td class="v"><span dir="ltr">{{ $fmt($vehicle['paid'] ?? 0) }}</span></td></tr>
  <tr><td class="k">{{ __('documents.balance_due') }}</td><td class="v"><span dir="ltr">{{ $fmt($balance) }}</span></td></tr>
</table>
<div class="sigs">
  <div class="sig"><div class="line">{{ __('documents.prepared_by') }}<br>{{ __('documents.name_date') }}</div></div>
  <div class="sig"><div class="line">{{ __('documents.buyer_acceptance') }}<br>{{ __('documents.name_date') }}</div></div>
</div>
<div class="footer">{{ $company ?? config('app.name', 'Fleet Tracker') }} - {{ __('documents.generated') }} {{ date('Y-m-d H:i') }} UTC</div>
</body>
</html>
