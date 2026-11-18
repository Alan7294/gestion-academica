<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Mis cursos asignados') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">{{ __('Curso') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">{{ __('Carrera') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">{{ __('Periodo') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">{{ __('Inscritos') }}</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($cursos as $curso)
                            <tr>
                                <td class="px-6 py-4">
                                    <p class="font-medium text-gray-900">{{ $curso->nombre }}</p>
                                    <p class="font-mono text-xs text-gray-500">{{ $curso->codigo }}</p>
                                </td>
                                <td class="px-6 py-4 text-gray-600">{{ $curso->carrera->nombre }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $curso->periodo->nombre }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $curso->alumnos_inscritos_count }}</td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('docente.cursos.show', $curso) }}" class="text-indigo-600 hover:text-indigo-900">
                                        {{ __('Ver detalle') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                    {{ __('No tienes cursos asignados en este momento.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $cursos->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
