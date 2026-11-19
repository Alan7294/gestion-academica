<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Usuarios') }}
            </h2>

            <a href="{{ route('admin.usuarios.create') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                {{ __('Nuevo usuario') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-lg px-4 py-3">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-800 text-sm rounded-lg px-4 py-3">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="GET" action="{{ route('admin.usuarios.index') }}"
                  class="bg-white shadow-sm sm:rounded-lg p-4 grid grid-cols-1 sm:grid-cols-4 gap-3">
                <div class="sm:col-span-2">
                    <x-input-label for="busqueda" :value="__('Buscar')" />
                    <x-text-input id="busqueda" name="busqueda" type="text" class="mt-1 block w-full text-sm"
                                  :value="$filtros['busqueda']" placeholder="{{ __('Nombre, correo o CI') }}" />
                </div>

                <div>
                    <x-input-label for="rol" :value="__('Rol')" />
                    <select id="rol" name="rol" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm">
                        <option value="">{{ __('Todos') }}</option>
                        @foreach ($roles as $rol)
                            <option value="{{ $rol->value }}" @selected($filtros['rol'] === $rol->value)>
                                {{ $rol->etiqueta() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <x-primary-button>{{ __('Filtrar') }}</x-primary-button>
                    <a href="{{ route('admin.usuarios.index') }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Limpiar') }}</a>
                </div>
            </form>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">{{ __('Usuario') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">{{ __('CI') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">{{ __('Teléfono') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">{{ __('Rol') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">{{ __('Estado') }}</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($usuarios as $usuario)
                            <tr>
                                <td class="px-6 py-4">
                                    <p class="font-medium text-gray-900">{{ $usuario->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $usuario->email }}</p>
                                </td>
                                <td class="px-6 py-4 text-gray-600">{{ $usuario->ci ?? '—' }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $usuario->telefono ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800">
                                        {{ $usuario->rolPrincipal()?->etiqueta() ?? __('Sin rol') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    @if ($usuario->activo)
                                        <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">{{ __('Activo') }}</span>
                                    @else
                                        <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">{{ __('Inactivo') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right space-x-3 whitespace-nowrap">
                                    <a href="{{ route('admin.usuarios.edit', $usuario) }}" class="text-indigo-600 hover:text-indigo-900">
                                        {{ __('Editar') }}
                                    </a>

                                    @unless ($usuario->is(auth()->user()))
                                        <form method="POST" action="{{ route('admin.usuarios.destroy', $usuario) }}" class="inline"
                                              onsubmit="return confirm('{{ __('¿Eliminar este usuario?') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900">{{ __('Eliminar') }}</button>
                                        </form>
                                    @endunless
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                    {{ __('No hay usuarios que coincidan con los filtros.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>
                {{ $usuarios->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
