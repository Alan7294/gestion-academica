<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Mis reportes') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <p class="text-sm text-gray-600">
                {{ __('Descarga tu información académica en formato PDF o Excel.') }}
            </p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white shadow-sm sm:rounded-lg p-6 flex flex-col">
                    <h3 class="font-semibold text-gray-900">{{ __('Historial académico') }}</h3>
                    <p class="mt-1 text-sm text-gray-600 flex-1">
                        {{ __('Todas las materias cursadas, con su nota final y resultado.') }}
                    </p>

                    <div class="mt-4 flex items-center gap-2">
                        <a href="{{ route('estudiante.reportes.historial', ['formato' => 'pdf']) }}"
                           class="inline-flex items-center px-3 py-2 bg-red-600 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">
                            {{ __('PDF') }}
                        </a>
                        <a href="{{ route('estudiante.reportes.historial', ['formato' => 'excel']) }}"
                           class="inline-flex items-center px-3 py-2 bg-green-700 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-800">
                            {{ __('Excel') }}
                        </a>
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-6 flex flex-col">
                    <h3 class="font-semibold text-gray-900">{{ __('Notas de todas las materias') }}</h3>
                    <p class="mt-1 text-sm text-gray-600 flex-1">
                        {{ __('Detalle de cada evaluación registrada en tus materias.') }}
                    </p>

                    <div class="mt-4 flex items-center gap-2">
                        <a href="{{ route('estudiante.reportes.notas', ['formato' => 'pdf']) }}"
                           class="inline-flex items-center px-3 py-2 bg-red-600 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">
                            {{ __('PDF') }}
                        </a>
                        <a href="{{ route('estudiante.reportes.notas', ['formato' => 'excel']) }}"
                           class="inline-flex items-center px-3 py-2 bg-green-700 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-800">
                            {{ __('Excel') }}
                        </a>
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-6 flex flex-col">
                    <h3 class="font-semibold text-gray-900">{{ __('Materias del alumno') }}</h3>
                    <p class="mt-1 text-sm text-gray-600 flex-1">
                        {{ __('Materias en las que mantienes una inscripción vigente.') }}
                    </p>

                    <div class="mt-4 flex items-center gap-2">
                        <a href="{{ route('estudiante.reportes.materias', ['formato' => 'pdf']) }}"
                           class="inline-flex items-center px-3 py-2 bg-red-600 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">
                            {{ __('PDF') }}
                        </a>
                        <a href="{{ route('estudiante.reportes.materias', ['formato' => 'excel']) }}"
                           class="inline-flex items-center px-3 py-2 bg-green-700 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-800">
                            {{ __('Excel') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
