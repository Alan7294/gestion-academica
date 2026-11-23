<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-600">{{ __('Estudiante') }}</p>
            <h2 class="text-xl font-semibold text-slate-900">{{ __('Mis reportes') }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <p class="text-sm text-slate-600">
                {{ __('Descarga tu información académica en formato PDF o Excel.') }}
            </p>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                <div class="card flex flex-col p-6">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100">
                        <x-icon name="academic-cap" class="h-5 w-5" />
                    </span>

                    <h3 class="mt-4 text-base font-semibold text-slate-900">{{ __('Historial académico') }}</h3>
                    <p class="mt-1 flex-1 text-sm text-slate-600">
                        {{ __('Todas las materias cursadas, con su nota final y resultado.') }}
                    </p>

                    <div class="mt-5 flex items-center gap-2">
                        <a href="{{ route('estudiante.reportes.historial.preview') }}"
                           class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg bg-brand-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-brand-700">
                            <x-icon name="eye" class="h-3.5 w-3.5" /> {{ __('Ver') }}
                        </a>
                        <a href="{{ route('estudiante.reportes.historial', ['formato' => 'pdf']) }}"
                           class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg bg-rose-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-rose-700">
                            <x-icon name="download" class="h-3.5 w-3.5" /> PDF
                        </a>
                        <a href="{{ route('estudiante.reportes.historial', ['formato' => 'excel']) }}"
                           class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                            <x-icon name="download" class="h-3.5 w-3.5" /> Excel
                        </a>
                    </div>
                </div>

                <div class="card flex flex-col p-6">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100">
                        <x-icon name="document" class="h-5 w-5" />
                    </span>

                    <h3 class="mt-4 text-base font-semibold text-slate-900">{{ __('Notas de todas las materias') }}</h3>
                    <p class="mt-1 flex-1 text-sm text-slate-600">
                        {{ __('Detalle de cada evaluación registrada en tus materias.') }}
                    </p>

                    <div class="mt-5 flex items-center gap-2">
                        <a href="{{ route('estudiante.reportes.notas.preview') }}"
                           class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg bg-brand-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-brand-700">
                            <x-icon name="eye" class="h-3.5 w-3.5" /> {{ __('Ver') }}
                        </a>
                        <a href="{{ route('estudiante.reportes.notas', ['formato' => 'pdf']) }}"
                           class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg bg-rose-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-rose-700">
                            <x-icon name="download" class="h-3.5 w-3.5" /> PDF
                        </a>
                        <a href="{{ route('estudiante.reportes.notas', ['formato' => 'excel']) }}"
                           class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                            <x-icon name="download" class="h-3.5 w-3.5" /> Excel
                        </a>
                    </div>
                </div>

                <div class="card flex flex-col p-6">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100">
                        <x-icon name="book" class="h-5 w-5" />
                    </span>

                    <h3 class="mt-4 text-base font-semibold text-slate-900">{{ __('Materias del alumno') }}</h3>
                    <p class="mt-1 flex-1 text-sm text-slate-600">
                        {{ __('Materias en las que mantienes una inscripción vigente.') }}
                    </p>

                    <div class="mt-5 flex items-center gap-2">
                        <a href="{{ route('estudiante.reportes.materias.preview') }}"
                           class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg bg-brand-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-brand-700">
                            <x-icon name="eye" class="h-3.5 w-3.5" /> {{ __('Ver') }}
                        </a>
                        <a href="{{ route('estudiante.reportes.materias', ['formato' => 'pdf']) }}"
                           class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg bg-rose-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-rose-700">
                            <x-icon name="download" class="h-3.5 w-3.5" /> PDF
                        </a>
                        <a href="{{ route('estudiante.reportes.materias', ['formato' => 'excel']) }}"
                           class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                            <x-icon name="download" class="h-3.5 w-3.5" /> Excel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
