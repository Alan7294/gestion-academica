@php
    $notas = $inscripciones->pluck('nota_final')->filter(fn ($nota) => $nota !== null);
    $promedio = $notas->isEmpty() ? null : number_format($notas->avg(), 2);
    $creditos = $inscripciones->sum(fn ($inscripcion) => $inscripcion->curso->creditos);
    $matriculaPrincipal = $matriculasActivas->first(fn ($matricula) => $matricula->es_principal)
        ?? $matriculasActivas->first();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-600">{{ __('Panel del estudiante') }}</p>
                <h2 class="text-xl font-semibold text-slate-900">{{ __('Hola, ') . auth()->user()->name }}</h2>
                @if ($matriculaPrincipal)
                    <p class="mt-0.5 text-xs text-slate-500">
                        {{ $matriculaPrincipal->carrera->nombre }}
                        @if ($matriculaPrincipal->modalidad)
                            · {{ __('Modalidad') }} {{ $matriculaPrincipal->modalidad->etiqueta() }}
                        @endif
                    </p>
                    @if ($matriculasActivas->count() > 1)
                        <p class="mt-0.5 text-xs text-slate-400">
                            {{ __('También matriculado en') }}:
                            {{ $matriculasActivas->reject(fn ($matricula) => $matricula->is($matriculaPrincipal))->pluck('carrera.nombre')->join(', ') }}
                        </p>
                    @endif
                @endif
            </div>

            <a href="{{ route('estudiante.cursos.index') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                <x-icon name="plus" class="h-4 w-4" />
                {{ __('Inscribirme a un curso') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <x-stat-card :title="__('Cursos inscritos')" :value="$inscripciones->count()" icon="book" tone="brand" />
                <x-stat-card :title="__('Créditos')" :value="$creditos" icon="academic-cap" tone="ink" />
                <x-stat-card :title="__('Promedio general')" :value="$promedio ?? '—'" icon="chart" tone="emerald" :hint="__('Sobre las materias con nota final')" />
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="card overflow-hidden lg:col-span-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('Mis cursos') }}</h3>
                        <a href="{{ route('estudiante.inscripciones.index') }}" class="link-brand text-sm">{{ __('Ver todos') }}</a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-sm">
                            <thead class="bg-slate-50/80">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('Curso') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('Docente') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('Nota final') }}</th>
                                    <th class="px-6 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($inscripciones as $inscripcion)
                                    <tr class="transition hover:bg-slate-50/60">
                                        <td class="px-6 py-4">
                                            <p class="font-medium text-slate-900">{{ $inscripcion->curso->nombre }}</p>
                                            <p class="text-xs text-slate-500">{{ $inscripcion->curso->carrera->nombre }}</p>
                                        </td>
                                        <td class="px-6 py-4 text-slate-600">{{ $inscripcion->curso->docente?->name ?? __('Sin asignar') }}</td>
                                        <td class="px-6 py-4">
                                            @if ($inscripcion->nota_final === null)
                                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">{{ __('Pendiente') }}</span>
                                            @else
                                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $inscripcion->nota_final >= 51 ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-rose-50 text-rose-700 ring-1 ring-rose-200' }}">
                                                    {{ number_format((float) $inscripcion->nota_final, 2) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <a href="{{ route('estudiante.cursos.show', $inscripcion->curso) }}" class="link-brand text-sm">
                                                {{ __('Ver detalle') }}
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-10 text-center text-sm text-slate-500">
                                            {{ __('Aún no estás inscrito en ningún curso.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card h-fit p-6">
                    <h3 class="card-title">{{ __('Atajos') }}</h3>
                    <p class="mt-0.5 text-xs text-slate-500">{{ __('Accesos directos a tu información.') }}</p>

                    <div class="mt-5 space-y-2">
                        <a href="{{ route('estudiante.notas.index') }}"
                           class="flex items-center gap-3 rounded-xl border border-slate-200 px-3 py-2.5 transition hover:border-brand-300 hover:bg-brand-50/50">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-700"><x-icon name="document" class="h-4 w-4" /></span>
                            <span class="text-sm font-medium text-slate-800">{{ __('Mis calificaciones') }}</span>
                            <x-icon name="arrow-right" class="ms-auto h-4 w-4 text-slate-400" />
                        </a>

                        <a href="{{ route('estudiante.reportes.index') }}"
                           class="flex items-center gap-3 rounded-xl border border-slate-200 px-3 py-2.5 transition hover:border-brand-300 hover:bg-brand-50/50">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-700"><x-icon name="chart" class="h-4 w-4" /></span>
                            <span class="text-sm font-medium text-slate-800">{{ __('Descargar reportes') }}</span>
                            <x-icon name="arrow-right" class="ms-auto h-4 w-4 text-slate-400" />
                        </a>
                    </div>
                </div>
            </div>

            @include('partials.actividad-reciente', ['actividad' => $actividad])
        </div>
    </div>
</x-app-layout>
