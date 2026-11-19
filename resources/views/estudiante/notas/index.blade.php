<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Mis calificaciones') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @forelse ($inscripciones as $inscripcion)
                <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div>
                            <h3 class="font-semibold text-slate-900">{{ $inscripcion->curso->nombre }}</h3>
                            <p class="text-xs text-slate-500">
                                {{ $inscripcion->curso->carrera->nombre }}
                                · {{ $inscripcion->curso->docente?->name ?? __('Docente por asignar') }}
                            </p>
                        </div>

                        <div class="text-right">
                            <p class="text-xs text-slate-500">{{ __('Promedio') }}</p>
                            <p class="text-2xl font-semibold {{ ($inscripcion->nota_final ?? 0) >= 51 ? 'text-green-700' : 'text-slate-900' }}">
                                {{ $inscripcion->nota_final ?? '—' }}
                            </p>
                        </div>
                    </div>

                    @if ($inscripcion->calificaciones->isEmpty())
                        <p class="mt-4 text-sm text-slate-500">{{ __('Todavía no hay calificaciones publicadas.') }}</p>
                    @else
                        <table class="mt-4 min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-2 text-left font-medium text-slate-500">{{ __('Evaluación') }}</th>
                                    <th class="px-4 py-2 text-left font-medium text-slate-500">{{ __('Ponderación') }}</th>
                                    <th class="px-4 py-2 text-left font-medium text-slate-500">{{ __('Nota') }}</th>
                                    <th class="px-4 py-2 text-left font-medium text-slate-500">{{ __('Fecha') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($inscripcion->calificaciones as $calificacion)
                                    <tr>
                                        <td class="px-4 py-2 text-slate-900">{{ $calificacion->nombre }}</td>
                                        <td class="px-4 py-2 text-slate-600">{{ $calificacion->ponderacion }}%</td>
                                        <td class="px-4 py-2 font-medium text-slate-900">{{ $calificacion->nota }}</td>
                                        <td class="px-4 py-2 text-slate-600">{{ $calificacion->fecha->format('d/m/Y') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            @empty
                <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card p-8 text-center text-slate-500">
                    {{ __('No tienes cursos inscritos.') }}
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
