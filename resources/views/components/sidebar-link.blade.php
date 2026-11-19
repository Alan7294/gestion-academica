@props(['href', 'active' => false, 'icon' => 'home', 'badge' => null])

@php
    $classes = $active
        ? 'group flex items-center gap-3 rounded-xl bg-brand-500/15 px-3 py-2.5 text-sm font-semibold text-white ring-1 ring-inset ring-brand-400/30'
        : 'group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-400 transition hover:bg-white/5 hover:text-white';
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }} @if ($active) aria-current="page" @endif>
    <x-icon :name="$icon" class="h-5 w-5 shrink-0 {{ $active ? 'text-brand-300' : 'text-slate-500 transition group-hover:text-brand-300' }}" />

    <span class="truncate">{{ $slot }}</span>

    @if ($badge)
        <span class="ms-auto inline-flex min-w-[1.35rem] items-center justify-center rounded-full bg-brand-500/20 px-1.5 py-0.5 text-[11px] font-semibold text-brand-200">
            {{ $badge }}
        </span>
    @endif
</a>
