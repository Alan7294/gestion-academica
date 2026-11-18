<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Panel del estudiante') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-900">{{ __('Bienvenido, :nombre', ['nombre' => auth()->user()->name]) }}</h3>
                <p class="mt-1 text-sm text-gray-600">
                    {{ __('Aquí verás los cursos disponibles, tus inscripciones y tus calificaciones.') }}
                </p>
            </div>
        </div>
    </div>
</x-app-layout>
