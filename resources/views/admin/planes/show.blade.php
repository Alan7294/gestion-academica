<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-600">{{ __('Plan de estudios') }}</p>
                <h2 class="text-xl font-semibold text-slate-900">{{ $plan->nombre }}</h2>
                <p class="mt-0.5 text-sm text-slate-500">{{ $plan->carrera->nombre }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('admin.planes.edit', $plan) }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                    <x-icon name="pencil" class="h-4 w-4" />
                    {{ __('Editar') }}
                </a>
                <form method="POST" action="{{ route('admin.planes.destroy', $plan) }}" class="inline"
                      onsubmit="return confirm('{{ __('¿Eliminar este plan de estudios?') }}')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="rounded-lg border border-red-200 px-4 py-2.5 text-sm font-semibold text-red-700 transition hover:bg-red-50">
                        {{ __('Eliminar') }}
                    </button>
                </form>
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
            @php($materiasFueraDeDuracion = $plan->materias
                ->filter(fn ($materia) => (int) $materia->pivot->semestre > $plan->semestres)
                ->sortBy(fn ($materia) => (int) $materia->pivot->semestre)
                ->values())

            <div class="space-y-5">
                @for ($semestre = 1; $semestre <= $plan->semestres; $semestre++)
                    @php($materias = $materiasPorSemestre->get($semestre, collect()))

                    <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card overflow-hidden">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-6 py-4">
                            <h3 class="font-semibold text-slate-900">{{ __('Semestre') }} {{ $semestre }}</h3>
                            <span class="text-xs text-slate-500">
                                {{ $materias->count() }} {{ $materias->count() === 1 ? __('materia') : __('materias') }}
                                · {{ $materias->sum(fn ($materia) => $materia->pivot->creditos) }} {{ __('créditos') }}
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
                                        <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Requisitos') }}</th>
                                        <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Créditos') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($materias as $materia)
                                        <tr>
                                            <td class="px-6 py-3 font-mono text-slate-700">{{ $materia->codigo }}</td>
                                            <td class="px-6 py-3 text-slate-900">{{ $materia->nombre }}</td>
                                            <td class="px-6 py-3 text-slate-600">
                                                @if ($materia->requisitos->isEmpty())
                                                    <span class="text-slate-400">—</span>
                                                @else
                                                    {{ $materia->requisitos->map(fn ($requisito) => "{$requisito->codigo} - {$requisito->nombre}")->join(', ') }}
                                                @endif
                                            </td>
                                            <td class="px-6 py-3 text-slate-600">{{ $materia->pivot->creditos }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                @endfor

                @if ($materiasFueraDeDuracion->isNotEmpty())
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 shadow-card overflow-hidden">
                        <div class="border-b border-amber-200 px-6 py-4">
                            <h3 class="font-semibold text-amber-900">{{ __('Materias fuera de la duración del plan') }}</h3>
                            <p class="mt-0.5 text-sm text-amber-800">
                                {{ __('Estas materias están en un semestre mayor que la duración del plan (:duracion semestres). Ajusta su semestre o la duración.', ['duracion' => $plan->semestres]) }}
                            </p>
                        </div>

                        <table class="min-w-full divide-y divide-amber-200 text-sm">
                            <thead class="bg-amber-100/60">
                                <tr>
                                    <th class="px-6 py-3 text-left font-medium text-amber-800">{{ __('Semestre') }}</th>
                                    <th class="px-6 py-3 text-left font-medium text-amber-800">{{ __('Código') }}</th>
                                    <th class="px-6 py-3 text-left font-medium text-amber-800">{{ __('Materia') }}</th>
                                    <th class="px-6 py-3 text-left font-medium text-amber-800">{{ __('Créditos') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-amber-100">
                                @foreach ($materiasFueraDeDuracion as $materia)
                                    <tr>
                                        <td class="px-6 py-3 text-amber-900">{{ $materia->pivot->semestre }}</td>
                                        <td class="px-6 py-3 font-mono text-amber-900">{{ $materia->codigo }}</td>
                                        <td class="px-6 py-3 text-amber-900">{{ $materia->nombre }}</td>
                                        <td class="px-6 py-3 text-amber-900">{{ $materia->pivot->creditos }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
