@props(['title' => null, 'value' => null, 'icon' => 'chart', 'tone' => 'brand', 'hint' => null])

@php
    $tonos = [
        'brand' => 'bg-brand-50 text-brand-700 ring-brand-100',
        'ink' => 'bg-ink-100 text-ink-700 ring-ink-200',
        'amber' => 'bg-amber-50 text-amber-700 ring-amber-100',
        'rose' => 'bg-rose-50 text-rose-700 ring-rose-100',
        'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
    ];
@endphp

<div class="card p-5">
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
            <p class="min-h-[2.5rem] text-sm font-medium leading-snug text-slate-500">{{ $title }}</p>
            <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">{{ $value }}</p>

            @if ($hint)
                <p class="mt-1 text-xs text-slate-400">{{ $hint }}</p>
            @endif
        </div>

        <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl ring-1 {{ $tonos[$tone] ?? $tonos['brand'] }}">
            <x-icon :name="$icon" class="h-5 w-5" />
        </span>
    </div>
</div>
