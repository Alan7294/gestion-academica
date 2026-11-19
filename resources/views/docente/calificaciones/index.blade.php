<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Planilla de calificaciones') }} — {{ $curso->nombre }}
            </h2>

            <a href="{{ route('docente.cursos.show', $curso) }}" class="text-sm text-gray-600 hover:text-gray-900">
                {{ __('Volver al curso') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <p class="text-sm text-gray-600">
                {{ __('La suma de ponderaciones por estudiante no puede superar el 100%. El promedio se recalcula automáticamente.') }}
            </p>

            @forelse ($curso->alumnosInscritos as $inscripcion)
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-semibold text-gray-900">{{ $inscripcion->estudiante->name }}</h3>
                            <p class="text-xs text-gray-500">{{ $inscripcion->estudiante->email }}</p>
                        </div>

                        <div class="text-right">
                            <p class="text-xs text-gray-500">{{ __('Promedio ponderado') }}</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ $inscripcion->nota_final ?? '—' }}</p>
                        </div>
                    </div>

                    <div class="mt-4 space-y-3">
                        @foreach ($inscripcion->calificaciones as $calificacion)
                            <div class="flex flex-col lg:flex-row lg:items-end gap-3 border-b border-gray-100 pb-3">
                                <form method="POST" action="{{ route('docente.calificaciones.update', $calificacion) }}"
                                      class="grid grid-cols-2 sm:grid-cols-5 gap-2 flex-1 items-end">
                                    @csrf
                                    @method('PUT')

                                    <div class="sm:col-span-2">
                                        <label class="text-xs text-gray-500">{{ __('Evaluación') }}</label>
                                        <input type="text" name="nombre" value="{{ $calificacion->nombre }}"
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" required>
                                    </div>

                                    <div>
                                        <label class="text-xs text-gray-500">{{ __('Ponderación %') }}</label>
                                        <input type="number" name="ponderacion" min="0" max="100" value="{{ $calificacion->ponderacion }}"
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" required>
                                    </div>

                                    <div>
                                        <label class="text-xs text-gray-500">{{ __('Nota') }}</label>
                                        <input type="number" step="0.01" name="nota" min="0" max="100" value="{{ $calificacion->nota }}"
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" required>
                                    </div>

                                    <div>
                                        <label class="text-xs text-gray-500">{{ __('Fecha') }}</label>
                                        <input type="date" name="fecha" value="{{ $calificacion->fecha->format('Y-m-d') }}"
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" required>
                                    </div>

                                    <div>
                                        <x-secondary-button type="submit">{{ __('Guardar') }}</x-secondary-button>
                                    </div>
                                </form>

                                <form method="POST" action="{{ route('docente.calificaciones.destroy', $calificacion) }}"
                                      onsubmit="return confirm('{{ __('¿Eliminar esta calificación?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm text-red-600 hover:text-red-900">{{ __('Eliminar') }}</button>
                                </form>
                            </div>
                        @endforeach
                    </div>

                    <form method="POST" action="{{ route('docente.calificaciones.store', $inscripcion) }}"
                          class="mt-4 grid grid-cols-2 sm:grid-cols-5 gap-2 items-end">
                        @csrf

                        <div class="sm:col-span-2">
                            <label class="text-xs text-gray-500">{{ __('Nueva evaluación') }}</label>
                            <input type="text" name="nombre" placeholder="{{ __('Ej. Parcial 1') }}"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" required>
                        </div>

                        <div>
                            <label class="text-xs text-gray-500">{{ __('Ponderación %') }}</label>
                            <input type="number" name="ponderacion" min="0" max="100" value="0"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" required>
                        </div>

                        <div>
                            <label class="text-xs text-gray-500">{{ __('Nota') }}</label>
                            <input type="number" step="0.01" name="nota" min="0" max="100"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" required>
                        </div>

                        <div class="flex items-end gap-2">
                            <div class="flex-1">
                                <label class="text-xs text-gray-500">{{ __('Fecha') }}</label>
                                <input type="date" name="fecha" value="{{ now()->format('Y-m-d') }}"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" required>
                            </div>
                            <x-primary-button>{{ __('Agregar') }}</x-primary-button>
                        </div>
                    </form>
                </div>
            @empty
                <div class="bg-white shadow-sm sm:rounded-lg p-8 text-center text-gray-500">
                    {{ __('Este curso todavía no tiene alumnos inscritos.') }}
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
