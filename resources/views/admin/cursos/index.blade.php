<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Cursos') }}
            </h2>

            <a href="{{ route('admin.cursos.create') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                {{ __('Nuevo curso') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <form method="GET" action="{{ route('admin.cursos.index') }}"
                  class="rounded-2xl border border-slate-200/70 bg-white shadow-card p-4 grid grid-cols-1 sm:grid-cols-4 gap-3">
                <div>
                    <x-input-label for="busqueda" :value="__('Buscar')" />
                    <x-text-input id="busqueda" name="busqueda" type="text" class="mt-1 block w-full text-sm"
                                  :value="$filtros['busqueda'] ?? null" placeholder="{{ __('Código o nombre') }}" />
                </div>

                <div>
                    <x-input-label for="carrera_id" :value="__('Carrera')" />
                    <select id="carrera_id" name="carrera_id" class="mt-1 block w-full border-slate-300 rounded-md shadow-sm text-sm">
                        <option value="">{{ __('Todas') }}</option>
                        @foreach ($carreras as $carrera)
                            <option value="{{ $carrera->id }}" @selected(($filtros['carrera_id'] ?? null) == $carrera->id)>
                                {{ $carrera->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="periodo_id" :value="__('Periodo')" />
                    <select id="periodo_id" name="periodo_id" class="mt-1 block w-full border-slate-300 rounded-md shadow-sm text-sm">
                        <option value="">{{ __('Todos') }}</option>
                        @foreach ($periodos as $periodo)
                            <option value="{{ $periodo->id }}" @selected(($filtros['periodo_id'] ?? null) == $periodo->id)>
                                {{ $periodo->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <x-primary-button>{{ __('Filtrar') }}</x-primary-button>
                    <a href="{{ route('admin.cursos.index') }}" class="text-sm text-slate-600 hover:text-slate-900">{{ __('Limpiar') }}</a>
                </div>
            </form>

            <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card overflow-hidden">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Código') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Curso') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500">{{ __('Días') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500">{{ __('Horario') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500">{{ __('Aula') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Carrera') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Periodo') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Docente') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Cupo') }}</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($cursos as $curso)
                            @php($resumen = $curso->resumenHorarios())
                            <tr>
                                <td class="px-6 py-4 font-mono text-slate-700">{{ $curso->materia?->codigo ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    <a href="{{ route('admin.cursos.show', $curso) }}" class="text-brand-600 hover:text-brand-900">
                                        {{ $curso->nombre }}
                                    </a>
                                </td>
                                <td class="px-4 py-4 text-slate-600">{{ $resumen['dias'] }}</td>
                                <td class="px-4 py-4 font-mono text-xs text-slate-600">{{ $resumen['horario'] }}</td>
                                <td class="px-4 py-4 text-slate-600">{{ $resumen['aula'] }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ $curso->carrera->nombre }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ $curso->periodo->nombre }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ $curso->docente?->name ?? __('Sin asignar') }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ $curso->cupo === 0 ? __('Sin límite') : $curso->cupo }}</td>
                                <td class="px-6 py-4 text-right space-x-3">
                                    <a href="{{ route('admin.cursos.show', $curso) }}" class="text-slate-700 hover:text-slate-900">{{ __('Ver') }}</a>
                                    <a href="{{ route('admin.cursos.edit', $curso) }}" class="text-brand-600 hover:text-brand-900">{{ __('Editar') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-6 py-8 text-center text-slate-500">
                                    {{ __('No hay cursos que coincidan con los filtros.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>
                {{ $cursos->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
