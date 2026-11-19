<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Editar usuario') }} — {{ $usuario->name }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('admin.usuarios.update', $usuario) }}"
                  class="bg-white shadow-sm sm:rounded-lg p-6">
                @csrf
                @method('PUT')

                @include('admin.usuarios._form', ['usuario' => $usuario])

                <div class="mt-6 flex items-center gap-3">
                    <x-primary-button>{{ __('Guardar cambios') }}</x-primary-button>
                    <a href="{{ route('admin.usuarios.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                        {{ __('Cancelar') }}
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
