<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Periodos académicos') }}
            </h2>

            <a href="{{ route('admin.periodos.create') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                {{ __('Nuevo periodo') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">{{ __('Periodo') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">{{ __('Inicio') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">{{ __('Fin') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">{{ __('Cursos') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">{{ __('Estado') }}</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($periodos as $periodo)
                            <tr>
                                <td class="px-6 py-4 text-gray-900">{{ $periodo->nombre }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $periodo->fecha_inicio->format('d/m/Y') }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $periodo->fecha_fin->format('d/m/Y') }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $periodo->cursos_count }}</td>
                                <td class="px-6 py-4">
                                    @if ($periodo->activo)
                                        <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800">{{ __('Vigente') }}</span>
                                    @else
                                        <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">{{ __('Cerrado') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right space-x-3">
                                    @unless ($periodo->activo)
                                        <form method="POST" action="{{ route('admin.periodos.activar', $periodo) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-indigo-600 hover:text-indigo-900">{{ __('Marcar vigente') }}</button>
                                        </form>
                                    @endunless

                                    <a href="{{ route('admin.periodos.edit', $periodo) }}" class="text-gray-700 hover:text-gray-900">{{ __('Editar') }}</a>

                                    <form method="POST" action="{{ route('admin.periodos.destroy', $periodo) }}" class="inline"
                                          onsubmit="return confirm('{{ __('¿Eliminar este periodo?') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900">{{ __('Eliminar') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                    {{ __('Todavía no hay periodos registrados.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $periodos->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
