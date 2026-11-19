<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Reportes') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <p class="text-sm text-gray-600">
                {{ __('Descarga los reportes institucionales en formato PDF o Excel.') }}
            </p>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-900">{{ __('Materias por carrera') }}</h3>
                <p class="mt-1 text-sm text-gray-600">
                    {{ __('Listado de materias registradas, ordenadas por carrera y semestre, con docente, periodo e inscritos.') }}
                </p>

                <div class="mt-4 flex items-center gap-2">
                    <a href="{{ route('admin.reportes.materias', ['formato' => 'pdf']) }}"
                       class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">
                        {{ __('Descargar PDF') }}
                    </a>
                    <a href="{{ route('admin.reportes.materias', ['formato' => 'excel']) }}"
                       class="inline-flex items-center px-4 py-2 bg-green-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-800">
                        {{ __('Descargar Excel') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
