<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-600">{{ __('Administración') }}</p>
            <h2 class="text-xl font-semibold text-slate-900">{{ __('Reportes') }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <p class="text-sm text-slate-600">
                {{ __('Descarga los reportes institucionales en formato PDF o Excel.') }}
            </p>

            <div class="card p-6">
                <div class="flex flex-col gap-6 sm:flex-row sm:items-start">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100">
                        <x-icon name="academic-cap" class="h-6 w-6" />
                    </span>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-base font-semibold text-slate-900">{{ __('Materias por carrera') }}</h3>
                            <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">{{ __('Institucional') }}</span>
                        </div>

                        <p class="mt-1.5 text-sm text-slate-600">
                            {{ __('Listado de materias registradas, ordenadas por carrera y semestre, con docente, periodo e inscritos.') }}
                        </p>

                        <div class="mt-5 flex flex-wrap items-center gap-2">
                            <a href="{{ route('admin.reportes.materias', ['formato' => 'pdf']) }}"
                               class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700">
                                <x-icon name="download" class="h-4 w-4" />
                                {{ __('Descargar PDF') }}
                            </a>
                            <a href="{{ route('admin.reportes.materias', ['formato' => 'excel']) }}"
                               class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                                <x-icon name="download" class="h-4 w-4" />
                                {{ __('Descargar Excel') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50/60 px-5 py-4 text-sm text-slate-500">
                {{ __('Los reportes de docentes y estudiantes se generan desde sus propios paneles, con la información de sus cursos.') }}
            </div>
        </div>
    </div>
</x-app-layout>
