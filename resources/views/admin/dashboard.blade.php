<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Panel de administración') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-900">{{ __('Próximos módulos') }}</h3>
                <p class="mt-1 text-sm text-gray-600">
                    {{ __('La gestión de usuarios, carreras, cursos y reportes se incorpora en las siguientes iteraciones.') }}
                </p>
            </div>
        </div>
    </div>
</x-app-layout>
