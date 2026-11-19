<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Reportes del docente') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <p class="text-sm text-gray-600">
                {{ __('Descarga en PDF o Excel la información de tus materias, tus alumnos y sus calificaciones.') }}
            </p>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h3 class="font-semibold text-gray-900">{{ __('Listado de materias del docente') }}</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            {{ __('Materias asignadas, carga académica y horario.') }}
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('docente.reportes.materias', ['formato' => 'pdf']) }}"
                           class="inline-flex items-center px-3 py-2 bg-red-600 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">
                            {{ __('PDF') }}
                        </a>
                        <a href="{{ route('docente.reportes.materias', ['formato' => 'excel']) }}"
                           class="inline-flex items-center px-3 py-2 bg-green-700 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-800">
                            {{ __('Excel') }}
                        </a>
                    </div>
                </div>
            </div>

            @forelse ($cursos as $curso)
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <h3 class="font-semibold text-gray-900">
                                {{ $curso->nombre }}
                                <span class="ms-2 font-mono text-xs text-gray-500">{{ $curso->codigo }}</span>
                            </h3>
                            <p class="text-xs text-gray-500">
                                {{ $curso->carrera->nombre }} · {{ $curso->periodo->nombre }} ·
                                {{ $curso->alumnos_inscritos_count }} {{ __('inscritos') }}
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-xs text-gray-500">{{ __('Notas por curso') }}:</span>
                            <a href="{{ route('docente.reportes.notas-curso', ['curso' => $curso, 'formato' => 'pdf']) }}"
                               class="text-xs font-semibold text-red-600 hover:text-red-800">PDF</a>
                            <a href="{{ route('docente.reportes.notas-curso', ['curso' => $curso, 'formato' => 'excel']) }}"
                               class="text-xs font-semibold text-green-700 hover:text-green-900">Excel</a>

                            <span class="ms-2 text-xs text-gray-500">{{ __('Alumnos inscritos') }}:</span>
                            <a href="{{ route('docente.reportes.alumnos', ['curso' => $curso, 'formato' => 'pdf']) }}"
                               class="text-xs font-semibold text-red-600 hover:text-red-800">PDF</a>
                            <a href="{{ route('docente.reportes.alumnos', ['curso' => $curso, 'formato' => 'excel']) }}"
                               class="text-xs font-semibold text-green-700 hover:text-green-900">Excel</a>
                        </div>
                    </div>

                    @if ($curso->alumnosInscritos->isEmpty())
                        <p class="px-6 py-6 text-sm text-gray-500">{{ __('Este curso todavía no tiene alumnos inscritos.') }}</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left font-medium text-gray-500">{{ __('Estudiante') }}</th>
                                        <th class="px-6 py-3 text-left font-medium text-gray-500">{{ __('CI') }}</th>
                                        <th class="px-6 py-3 text-left font-medium text-gray-500">{{ __('Nota final') }}</th>
                                        <th class="px-6 py-3 text-right font-medium text-gray-500">{{ __('Notas por alumno') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($curso->alumnosInscritos as $inscripcion)
                                        <tr>
                                            <td class="px-6 py-3 text-gray-900">{{ $inscripcion->estudiante->name }}</td>
                                            <td class="px-6 py-3 text-gray-600">{{ $inscripcion->estudiante->ci ?? '—' }}</td>
                                            <td class="px-6 py-3 text-gray-600">{{ $inscripcion->nota_final ?? '—' }}</td>
                                            <td class="px-6 py-3 text-right space-x-3">
                                                <a href="{{ route('docente.reportes.notas-alumno', ['curso' => $curso, 'inscripcion' => $inscripcion, 'formato' => 'pdf']) }}"
                                                   class="text-xs font-semibold text-red-600 hover:text-red-800">PDF</a>
                                                <a href="{{ route('docente.reportes.notas-alumno', ['curso' => $curso, 'inscripcion' => $inscripcion, 'formato' => 'excel']) }}"
                                                   class="text-xs font-semibold text-green-700 hover:text-green-900">Excel</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            @empty
                <div class="bg-white shadow-sm sm:rounded-lg p-8 text-center text-gray-500">
                    {{ __('Todavía no tienes cursos asignados, por lo que no hay reportes disponibles.') }}
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
