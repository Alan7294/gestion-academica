<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Mis calificaciones') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @forelse ($inscripciones as $inscripcion)
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div>
                            <h3 class="font-semibold text-gray-900">{{ $inscripcion->curso->nombre }}</h3>
                            <p class="text-xs text-gray-500">
                                {{ $inscripcion->curso->carrera->nombre }}
                                · {{ $inscripcion->curso->docente?->name ?? __('Docente por asignar') }}
                            </p>
                        </div>

                        <div class="text-right">
                            <p class="text-xs text-gray-500">{{ __('Promedio') }}</p>
                            <p class="text-2xl font-semibold {{ ($inscripcion->nota_final ?? 0) >= 51 ? 'text-green-700' : 'text-gray-900' }}">
                                {{ $inscripcion->nota_final ?? '—' }}
                            </p>
                        </div>
                    </div>

                    @if ($inscripcion->calificaciones->isEmpty())
                        <p class="mt-4 text-sm text-gray-500">{{ __('Todavía no hay calificaciones publicadas.') }}</p>
                    @else
                        <table class="mt-4 min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left font-medium text-gray-500">{{ __('Evaluación') }}</th>
                                    <th class="px-4 py-2 text-left font-medium text-gray-500">{{ __('Ponderación') }}</th>
                                    <th class="px-4 py-2 text-left font-medium text-gray-500">{{ __('Nota') }}</th>
                                    <th class="px-4 py-2 text-left font-medium text-gray-500">{{ __('Fecha') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($inscripcion->calificaciones as $calificacion)
                                    <tr>
                                        <td class="px-4 py-2 text-gray-900">{{ $calificacion->nombre }}</td>
                                        <td class="px-4 py-2 text-gray-600">{{ $calificacion->ponderacion }}%</td>
                                        <td class="px-4 py-2 font-medium text-gray-900">{{ $calificacion->nota }}</td>
                                        <td class="px-4 py-2 text-gray-600">{{ $calificacion->fecha->format('d/m/Y') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            @empty
                <div class="bg-white shadow-sm sm:rounded-lg p-8 text-center text-gray-500">
                    {{ __('No tienes cursos inscritos.') }}
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
