<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Panel de administración') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm font-medium text-gray-500">{{ __('Usuarios registrados') }}</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $totalUsuarios }}</p>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm font-medium text-gray-500">{{ __('Estudiantes') }}</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $totalEstudiantes }}</p>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm font-medium text-gray-500">{{ __('Docentes') }}</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $totalDocentes }}</p>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm font-medium text-gray-500">{{ __('Cursos') }}</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $totalCursos }}</p>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900">{{ __('Actividad reciente') }}</h3>
                </div>

                <ul class="divide-y divide-gray-100">
                    @forelse ($ultimasInscripciones as $inscripcion)
                        <li class="px-6 py-4 text-sm">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-gray-900">
                                        <span class="font-medium">{{ $inscripcion->estudiante->name }}</span>
                                        {{ __('se inscribió a') }}
                                        <span class="font-medium">{{ $inscripcion->curso->nombre }}</span>
                                    </p>
                                    <p class="mt-1 text-xs text-gray-500">
                                        {{ $inscripcion->curso->carrera->nombre }}
                                        @if ($inscripcion->curso->docente)
                                            · {{ __('Docente') }}: {{ $inscripcion->curso->docente->name }}
                                        @endif
                                    </p>
                                </div>

                                <span class="text-xs text-gray-400 whitespace-nowrap">
                                    {{ $inscripcion->inscrito_en?->diffForHumans() ?? $inscripcion->created_at->diffForHumans() }}
                                </span>
                            </div>
                        </li>
                    @empty
                        <li class="px-6 py-8 text-center text-gray-500">
                            {{ __('Todavía no hay inscripciones registradas.') }}
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
