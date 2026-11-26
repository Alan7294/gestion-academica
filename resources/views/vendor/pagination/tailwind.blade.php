@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Navegación de paginación') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-slate-600">
            @if ($paginator->firstItem())
                {{ __('Mostrando') }} <span class="font-semibold text-slate-900">{{ $paginator->firstItem() }}</span>
                {{ __('a') }}
                <span class="font-semibold text-slate-900">{{ $paginator->lastItem() }}</span>
            @else
                <span class="font-semibold text-slate-900">{{ $paginator->count() }}</span>
            @endif
            {{ __('de') }} <span class="font-semibold text-slate-900">{{ $paginator->total() }}</span>
            {{ __('resultados') }}
        </p>

        <ul class="flex flex-wrap items-center gap-1">
            @if ($paginator->onFirstPage())
                <li>
                    <span aria-disabled="true" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-400">
                        <span aria-hidden="true">&laquo;</span> {{ __('Anterior') }}
                    </span>
                </li>
            @else
                <li>
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <span aria-hidden="true">&laquo;</span> {{ __('Anterior') }}
                    </a>
                </li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li aria-hidden="true" class="px-2 text-sm text-slate-400">{{ $element }}</li>
                @else
                    @foreach ($element as $page => $url)
                        @if ($page === $paginator->currentPage())
                            <li>
                                <span aria-current="page" class="inline-flex min-w-[2.25rem] items-center justify-center rounded-lg bg-brand-600 px-3 py-2 text-sm font-semibold text-white shadow-sm">{{ $page }}</span>
                            </li>
                        @else
                            <li>
                                <a href="{{ $url }}" aria-label="{{ __('Ir a la página :page', ['page' => $page]) }}" class="inline-flex min-w-[2.25rem] items-center justify-center rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li>
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500">
                        {{ __('Siguiente') }} <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            @else
                <li>
                    <span aria-disabled="true" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-400">
                        {{ __('Siguiente') }} <span aria-hidden="true">&raquo;</span>
                    </span>
                </li>
            @endif
        </ul>
    </nav>
@endif
