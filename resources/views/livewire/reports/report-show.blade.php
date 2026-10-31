<div>
  <div class="mt-3 flex flex-wrap items-start justify-between gap-3">
    <div>
      <a href="{{ route('reports.index') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:underline dark:text-indigo-400">
        <svg class="h-3.5 w-3.5 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>
        {{ __('reports.all_reports') }}
      </a>
      <h1 class="mt-1 text-lg font-bold">{{ $report->title() }}</h1>
      <p class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
        <span>{{ $total }} {{ __('reports.records') }}</span>
        @if($filters->isActive())
          <span class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2 py-0.5 font-semibold text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300">
            {{ __('reports.filters.filtered') }}
          </span>
          <span class="text-[11px]">{{ $filters->describe() }}</span>
        @endif
      </p>
    </div>

    <div class="flex flex-wrap items-center gap-2 print:hidden">
      <button type="button" wire:click="toggleFilters"
              aria-expanded="{{ $filtersOpen ? 'true' : 'false' }}"
              aria-controls="report-filters"
              class="inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-semibold transition {{ $filtersOpen ? 'bg-indigo-600 text-white shadow-sm hover:bg-indigo-500' : 'border border-slate-200 text-slate-600 hover:border-indigo-300 hover:text-indigo-600 dark:border-slate-700 dark:text-slate-300 dark:hover:border-indigo-500 dark:hover:text-indigo-300' }}">
        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/></svg>
        {{ $filtersOpen ? __('reports.filters.hide') : __('reports.filters.show') }}
        @if($filters->isActive())
          <span class="h-1.5 w-1.5 rounded-full bg-amber-400" aria-hidden="true"></span>
        @endif
      </button>
      <a href="{{ $exportXlsx }}"
         class="rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:border-slate-300 dark:border-slate-700 dark:text-slate-300">
        {{ __('reports.download_xlsx') }}
      </a>
      <a href="{{ $exportPdf }}"
         class="rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:border-slate-300 dark:border-slate-700 dark:text-slate-300">
        {{ __('reports.download_pdf') }}
      </a>
      <button type="button" onclick="window.print()"
              class="rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:border-slate-300 dark:border-slate-700 dark:text-slate-300">
        {{ __('reports.print') }}
      </button>
    </div>
  </div>

  @if($filtersOpen)
  <div id="report-filters" class="mt-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm dark:border-slate-800 dark:bg-slate-900 print:hidden">
    <div class="flex flex-wrap items-end gap-2 sm:gap-3">
      <label class="min-w-44 flex-1 sm:flex-none">
        <span class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('reports.filters.date_field') }}</span>
        <select wire:model.live="dateField"
                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 focus:border-indigo-400 focus:outline-none dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
          <option value="">{{ __('reports.filters.all_dates') }}</option>
          @foreach($dateFields as $field)
            <option value="{{ $field }}">{{ __('reports.date_fields.'.$field) }}</option>
          @endforeach
        </select>
      </label>

      <label class="min-w-36 flex-1 sm:flex-none">
        <span class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('reports.filters.from') }}</span>
        <input type="date" wire:model.live="from" @disabled($dateField === '')
               class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 focus:border-indigo-400 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
      </label>

      <label class="min-w-36 flex-1 sm:flex-none">
        <span class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('reports.filters.to') }}</span>
        <input type="date" wire:model.live="to" @disabled($dateField === '')
               class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 focus:border-indigo-400 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
      </label>
    </div>

    <div class="mt-3 flex flex-wrap items-center gap-1.5">
      @foreach(['this_month', 'last_month', 'last_30', 'this_year'] as $preset)
        <button type="button" wire:click="applyPreset('{{ $preset }}')"
                class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-semibold text-slate-600 transition hover:border-indigo-300 hover:text-indigo-600 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-300 dark:hover:border-indigo-500 dark:hover:text-indigo-300">
          {{ __('reports.filters.presets.'.$preset) }}
        </button>
      @endforeach
      <button type="button" wire:click="resetFilters"
              class="ms-auto rounded-full px-2.5 py-1 text-[11px] font-semibold text-slate-500 underline-offset-2 hover:text-indigo-600 hover:underline dark:text-slate-400 dark:hover:text-indigo-300">
        {{ __('reports.filters.reset') }}
      </button>
    </div>
  </div>
  @endif

  <div class="mt-3 overflow-x-auto rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
    <table class="w-full min-w-max border-collapse text-sm">
      <thead>
        <tr class="border-b border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-800/60">
          @foreach($columns as $col)
            <th class="whitespace-nowrap px-3 py-2.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300">{{ $col }}</th>
          @endforeach
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
        @forelse($rows as $row)
          <tr class="odd:bg-white even:bg-slate-50/60 hover:bg-indigo-50/50 dark:odd:bg-slate-900 dark:even:bg-slate-800/40 dark:hover:bg-indigo-950/30">
            @foreach((array) $row as $cell)
              <td class="whitespace-nowrap px-3 py-2 text-start align-top tabular-nums text-slate-700 dark:text-slate-200">
                @if ($cell === '' || $cell === null)
                  <span class="text-slate-300 dark:text-slate-600">&mdash;</span>
                @else
                  <bdi>{{ $cell }}</bdi>
                @endif
              </td>
            @endforeach
          </tr>
        @empty
          <tr>
            <td colspan="{{ count($columns) }}" class="px-3 py-10 text-center text-sm text-slate-500">
              {{ __('app.no_results') }}
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="mt-3 flex flex-wrap items-center justify-between gap-3 print:hidden">
    <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
      <span>{{ __('reports.filters.showing', ['from' => $first, 'to' => $last, 'total' => $total]) }}</span>
      <span class="rounded-full bg-slate-100 px-2 py-0.5 font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
        {{ __('reports.filters.page', ['page' => $page, 'pages' => $pages]) }}
      </span>
    </div>

    <div class="flex flex-wrap items-center gap-3">
      <label class="inline-flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
        <span>{{ __('reports.filters.per_page') }}</span>
        <select wire:model.live="perPage"
                class="rounded-lg border border-slate-200 bg-slate-50 px-2 py-1 text-xs font-semibold text-slate-700 focus:border-indigo-400 focus:outline-none dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200">
          @foreach([10, 25, 50, 100] as $size)
            <option value="{{ $size }}">{{ $size }}</option>
          @endforeach
          <option value="all">{{ __('reports.filters.all') }}</option>
        </select>
      </label>

      <div class="flex flex-wrap items-center gap-1">
        <button type="button" wire:click="goto({{ $page - 1 }})" @disabled($page <= 1)
                class="inline-flex items-center gap-1 rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:border-slate-300 disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700 dark:text-slate-300">
          <svg class="h-3.5 w-3.5 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
          {{ __('reports.filters.prev') }}
        </button>

        @php
          $start = max(1, min($page - 2, $pages - 4));
          $end = min($pages, $start + 4);
        @endphp
        @if($start > 1)
          <button type="button" wire:click="goto(1)" class="h-8 min-w-8 rounded-lg px-2 text-xs font-semibold text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800">1</button>
          @if($start > 2)
            <span class="px-1 text-xs text-slate-400">&hellip;</span>
          @endif
        @endif
        @for($p = $start; $p <= $end; $p++)
          <button type="button" wire:click="goto({{ $p }})"
                  class="h-8 min-w-8 rounded-lg px-2 text-xs font-semibold {{ $p === $page ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800' }}">
            {{ $p }}
          </button>
        @endfor
        @if($end < $pages)
          @if($end < $pages - 1)
            <span class="px-1 text-xs text-slate-400">&hellip;</span>
          @endif
          <button type="button" wire:click="goto({{ $pages }})" class="h-8 min-w-8 rounded-lg px-2 text-xs font-semibold text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800">{{ $pages }}</button>
        @endif

        <button type="button" wire:click="goto({{ $page + 1 }})" @disabled($page >= $pages)
                class="inline-flex items-center gap-1 rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:border-slate-300 disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700 dark:text-slate-300">
          {{ __('reports.filters.next') }}
          <svg class="h-3.5 w-3.5 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
        </button>
      </div>
    </div>
  </div>
</div>
