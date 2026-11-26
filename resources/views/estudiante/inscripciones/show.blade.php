<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-600">{{ __('Mis cursos') }}</p>
                <h2 class="text-xl font-semibold text-slate-900">{{ __('Calificaciones de') }} {{ $inscripcion->curso->nombre }}</h2>
            </div>

            <a href="{{ route('estudiante.inscripciones.index') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                <x-icon name="arrow-left" class="h-4 w-4" />
                {{ __('Volver a mis cursos') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="card p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="space-y-1 text-sm text-slate-600">
                        <p class="font-mono text-xs text-slate-500">{{ $inscripcion->curso->materia?->codigo ?? '—' }}</p>
                        <p>{{ $inscripcion->curso->carrera->nombre }}</p>
                        <p>{{ $inscripcion->curso->docente?->name ?? __('Docente por asignar') }}</p>
                        <p class="text-xs text-slate-500">
                            {{ __('Inscrito') }}: {{ $inscripcion->inscrito_en->format('d/m/Y') }}
                        </p>
                    </div>

                    <div class="text-right">
                        <p class="text-xs text-slate-500">{{ __('Promedio final') }}</p>
                        <p class="text-3xl font-semibold {{ $inscripcion->estaAprobada() ? 'text-green-700' : 'text-slate-900' }}">
                            {{ $inscripcion->nota_final ?? '—' }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card overflow-hidden">
                @if ($inscripcion->calificaciones->isEmpty())
                    <p class="px-6 py-8 text-center text-sm text-slate-500">
                        {{ __('Todavía no hay calificaciones publicadas.') }}
                    </p>
                @else
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Evaluación') }}</th>
                                <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Ponderación') }}</th>
                                <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Nota') }}</th>
                                <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Fecha') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($inscripcion->calificaciones as $calificacion)
                                <tr>
                                    <td class="px-6 py-4 text-slate-900">{{ $calificacion->evaluacion->nombre }}</td>
                                    <td class="px-6 py-4 text-slate-600">{{ number_format((float) $calificacion->evaluacion->ponderacion, 2) }}%</td>
                                    <td class="px-6 py-4 font-medium text-slate-900">{{ $calificacion->nota }}</td>
                                    <td class="px-6 py-4 text-slate-600">{{ $calificacion->fecha->format('d/m/Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
