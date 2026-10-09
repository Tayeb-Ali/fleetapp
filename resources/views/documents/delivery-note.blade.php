<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="auto">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Delivery Note - {{ $vehicle['ord_no'] ?? $vehicle['vin'] ?? '' }}</title>
<style>
  body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #111; margin: 40px; }
  .banner { background: #1f3a8a; color: #fff; padding: 18px 24px; margin: -40px -40px 20px -40px; }
  .banner h1 { margin: 0; font-size: 20px; }
  .banner .co { font-size: 16px; font-weight: bold; }
  .row { display: table; width: 100%; margin-bottom: 6px; }
  .row > div { display: table-cell; }
  .right { text-align: right; }
  h2 { font-size: 11px; color: #1f3a8a; text-transform: uppercase; border-bottom: 1px solid #ddd; padding-bottom: 4px; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
  td { padding: 4px 0; vertical-align: top; }
  td.k { color: #666; width: 170px; }
  td.v { font-weight: bold; }
  .note { margin: 10px 0 20px 0; }
  .sigs { margin-top: 60px; display: table; width: 100%; }
  .sig { display: table-cell; width: 50%; padding-right: 40px; }
  .line { border-top: 1px solid #000; margin-top: 60px; padding-top: 4px; color: #666; font-size: 10px; }
  .footer { margin-top: 40px; color: #888; font-size: 8px; }
</style>
</head>
<body>
<div class="banner">
  <div class="co">{{ $company ?? config('app.name', 'Fleet Tracker') }}</div>
  <h1>Delivery Note</h1>
</div>
<div class="row">
  <div><strong>Reference:</strong> {{ $vehicle['ord_no'] ?? $vehicle['vin'] ?? '-' }}</div>
  <div class="right"><strong>Date:</strong> {{ $vehicle['del_date'] ?? '-' }}</div>
</div>
<h2>Buyer</h2>
<table dir="auto">
  <tr><td class="k">Buyer / client</td><td class="v">{{ $vehicle['client'] ?? '-' }}</td></tr>
  <tr><td class="k">Phone</td><td class="v">{{ $vehicle['phone'] ?? '-' }}</td></tr>
</table>
<h2>Vehicle</h2>
<table dir="auto">
  <tr><td class="k">Brand / model</td><td class="v">{{ trim(($vehicle['brand'] ?? '').' '.($vehicle['model'] ?? '')) ?: '-' }}</td></tr>
  <tr><td class="k">Chassis No. (VIN)</td><td class="v">{{ $vehicle['vin'] ?? '-' }}</td></tr>
  <tr><td class="k">Fuel / engine</td><td class="v">{{ $vehicle['fuel'] ?? '-' }}</td></tr>
  <tr><td class="k">Bill of Lading (B/L)</td><td class="v">{{ $vehicle['bl'] ?? '-' }}</td></tr>
  <tr><td class="k">Warehouse location</td><td class="v">{{ $vehicle['loc'] ?? '-' }}</td></tr>
</table>
<h2>Delivery</h2>
<table dir="auto">
  <tr><td class="k">Delivery date</td><td class="v">{{ $vehicle['del_date'] ?? '-' }}</td></tr>
  <tr><td class="k">Received by</td><td class="v">{{ $vehicle['recv'] ?? '-' }}</td></tr>
  <tr><td class="k">Sale order No.</td><td class="v">{{ $vehicle['ord_no'] ?? '-' }}</td></tr>
</table>
<p class="note">The vehicle above was handed over to the buyer in the condition agreed.</p>
<div class="sigs">
  <div class="sig"><div class="line">Delivered by (Warehouse)<br>Name / date:</div></div>
  <div class="sig"><div class="line">Received by (Buyer)<br>Name / date:</div></div>
</div>
<div class="footer">{{ $company ?? config('app.name', 'Fleet Tracker') }} - generated {{ date('Y-m-d H:i') }} UTC</div>
</body>
</html>
