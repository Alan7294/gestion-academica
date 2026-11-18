<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Carreras') }}
            </h2>

            <a href="{{ route('admin.carreras.create') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                {{ __('Nueva carrera') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">{{ __('Código') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">{{ __('Nombre') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">{{ __('Facultad') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">{{ __('Cursos') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">{{ __('Estado') }}</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($carreras as $carrera)
                            <tr>
                                <td class="px-6 py-4 font-mono text-gray-700">{{ $carrera->codigo }}</td>
                                <td class="px-6 py-4 text-gray-900">{{ $carrera->nombre }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $carrera->facultad ?? '—' }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $carrera->cursos_count }}</td>
                                <td class="px-6 py-4">
                                    @if ($carrera->activo)
                                        <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">{{ __('Activa') }}</span>
                                    @else
                                        <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">{{ __('Inactiva') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right space-x-3">
                                    <a href="{{ route('admin.carreras.edit', $carrera) }}" class="text-indigo-600 hover:text-indigo-900">{{ __('Editar') }}</a>

                                    <form method="POST" action="{{ route('admin.carreras.destroy', $carrera) }}" class="inline"
                                          onsubmit="return confirm('{{ __('¿Eliminar esta carrera?') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900">{{ __('Eliminar') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                    {{ __('Todavía no hay carreras registradas.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $carreras->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
