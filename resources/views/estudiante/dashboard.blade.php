<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Panel del estudiante') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm font-medium text-gray-500">{{ __('Cursos inscritos') }}</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $inscripciones->count() }}</p>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm font-medium text-gray-500">{{ __('Créditos') }}</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $inscripciones->sum(fn ($i) => $i->curso->creditos) }}</p>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-6 flex items-center">
                    <a href="{{ route('estudiante.cursos.index') }}"
                       class="inline-flex items-center px-4 py-2 bg-gray-800 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                        {{ __('Ver oferta de cursos') }}
                    </a>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900">{{ __('Mis cursos') }}</h3>
                </div>

                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">{{ __('Curso') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">{{ __('Docente') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">{{ __('Carrera') }}</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($inscripciones as $inscripcion)
                            <tr>
                                <td class="px-6 py-4 text-gray-900">{{ $inscripcion->curso->nombre }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $inscripcion->curso->docente?->name ?? __('Sin asignar') }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $inscripcion->curso->carrera->nombre }}</td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('estudiante.cursos.show', $inscripcion->curso) }}" class="text-indigo-600 hover:text-indigo-900">
                                        {{ __('Ver detalle') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                    {{ __('Aún no estás inscrito en ningún curso.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @include('partials.actividad-reciente', ['actividad' => $actividad])
        </div>
    </div>
</x-app-layout>
