<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $report->titleKey() }}</title>
@livewireStyles
</head>
<body>
<livewire:reports.report-show :report-key="$report->key()" />
@livewireScripts
</body>
</html>
