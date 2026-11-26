<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Materias') }}
            </h2>

            <a href="{{ route('admin.materias.create') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                <x-icon name="plus" class="h-4 w-4" />
                {{ __('Nueva materia') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <form method="GET" action="{{ route('admin.materias.index') }}"
                  class="rounded-2xl border border-slate-200/70 bg-white shadow-card p-4">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                    <div class="sm:col-span-2">
                        <x-input-label for="busqueda" :value="__('Buscar')" />
                        <x-text-input id="busqueda" name="busqueda" type="text" class="mt-1 block w-full"
                                      :value="$filtros['busqueda']"
                                      placeholder="{{ __('Código o nombre de la materia') }}" />
                    </div>

                    <div>
                        <x-input-label for="carrera_id" :value="__('Carrera')" />
                        <select id="carrera_id" name="carrera_id"
                                class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                            <option value="">{{ __('Todas') }}</option>
                            @foreach ($carreras as $carrera)
                                <option value="{{ $carrera->id }}"
                                        @selected($filtros['carrera_id'] === (string) $carrera->id)>
                                    {{ $carrera->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="semestre" :value="__('Semestre')" />
                        <select id="semestre" name="semestre"
                                class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                            <option value="">{{ __('Todos') }}</option>
                            @foreach ($semestres as $semestre)
                                <option value="{{ $semestre }}"
                                        @selected($filtros['semestre'] === (string) $semestre)>
                                    {{ $semestre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mt-4 flex items-center gap-3">
                    <x-primary-button>{{ __('Filtrar') }}</x-primary-button>
                    <a href="{{ route('admin.materias.index') }}" class="text-sm text-slate-600 hover:text-slate-900">
                        {{ __('Limpiar') }}
                    </a>
                </div>
            </form>

            <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card overflow-hidden">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Código') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Nombre') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Carreras') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Cursos') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Planes') }}</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($materias as $materia)
                            <tr>
                                <td class="px-6 py-4 font-mono text-slate-700">{{ $materia->codigo }}</td>
                                <td class="px-6 py-4 text-slate-900">{{ $materia->nombre }}</td>
                                <td class="px-6 py-4 text-slate-600">
                                    {{ $materia->planesEstudio->pluck('carrera.nombre')->filter()->unique()->join(', ') ?: '—' }}
                                </td>
                                <td class="px-6 py-4 text-slate-600">{{ $materia->cursos_count }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ $materia->planes_estudio_count }}</td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('admin.materias.show', $materia) }}" class="text-brand-600 hover:text-brand-900">
                                        {{ __('Ver') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-slate-500">
                                    {{ __('Todavía no hay materias registradas.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $materias->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
