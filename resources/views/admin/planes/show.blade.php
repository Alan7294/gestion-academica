<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-600">{{ __('Plan de estudios') }}</p>
                <h2 class="text-xl font-semibold text-slate-900">{{ $plan->nombre }}</h2>
                <p class="mt-0.5 text-sm text-slate-500">{{ $plan->carrera->nombre }}</p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.planes.edit', $plan) }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                    <x-icon name="pencil" class="h-4 w-4" />
                    {{ __('Editar plan') }}
                </a>
                <a href="{{ route('admin.planes.index') }}" class="text-sm text-slate-600 hover:text-slate-900">
                    {{ __('Volver') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card p-5">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Modalidad') }}</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900">{{ $plan->modalidad->etiqueta() }}</p>
                </div>

                <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card p-5">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Duración') }}</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900">
                        {{ $plan->semestres }} {{ $plan->semestres === 1 ? __('semestre') : __('semestres') }}
                    </p>
                </div>

                <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card p-5">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Materias') }}</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900">{{ $plan->materias->count() }}</p>
                </div>

                <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card p-5">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Créditos totales') }}</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900">{{ $plan->totalCreditos() }}</p>
                </div>
            </div>

            @if (! $plan->activo)
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    {{ __('Este plan está inactivo y no debería usarse para nuevas inscripciones.') }}
                </div>
            @endif

            @php($materiasPorSemestre = $plan->materiasPorSemestre())

            <div class="space-y-5">
                @for ($semestre = 1; $semestre <= $plan->semestres; $semestre++)
                    @php($materias = $materiasPorSemestre->get($semestre, collect()))

                    <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card overflow-hidden">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-6 py-4">
                            <h3 class="font-semibold text-slate-900">{{ __('Semestre') }} {{ $semestre }}</h3>
                            <span class="text-xs text-slate-500">
                                {{ $materias->count() }} {{ $materias->count() === 1 ? __('materia') : __('materias') }}
                                · {{ $materias->sum('creditos') }} {{ __('créditos') }}
                            </span>
                        </div>

                        @if ($materias->isEmpty())
                            <p class="px-6 py-5 text-sm text-slate-500">
                                {{ __('Sin materias registradas en este semestre.') }}
                            </p>
                        @else
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Código') }}</th>
                                        <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Materia') }}</th>
                                        <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Créditos') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($materias as $materia)
                                        <tr>
                                            <td class="px-6 py-3 font-mono text-slate-700">{{ $materia->codigo }}</td>
                                            <td class="px-6 py-3 text-slate-900">{{ $materia->nombre }}</td>
                                            <td class="px-6 py-3 text-slate-600">{{ $materia->creditos }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                @endfor
            </div>
        </div>
    </div>
</x-app-layout>
