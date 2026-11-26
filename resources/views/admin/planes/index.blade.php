<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Planes de estudio') }}
            </h2>

            <a href="{{ route('admin.planes.create') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                <x-icon name="plus" class="h-4 w-4" />
                {{ __('Nuevo plan') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <form method="GET" action="{{ route('admin.planes.index') }}"
                  class="rounded-2xl border border-slate-200/70 bg-white shadow-card p-4">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-5">
                    <div class="sm:col-span-2">
                        <x-input-label for="busqueda" :value="__('Buscar')" />
                        <x-text-input id="busqueda" name="busqueda" type="text" class="mt-1 block w-full"
                                      :value="$filtros['busqueda']"
                                      placeholder="{{ __('Nombre del plan o de la carrera') }}" />
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
                        <x-input-label for="modalidad" :value="__('Modalidad')" />
                        <select id="modalidad" name="modalidad"
                                class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                            <option value="">{{ __('Todas') }}</option>
                            @foreach ($modalidades as $modalidad)
                                <option value="{{ $modalidad->value }}"
                                        @selected($filtros['modalidad'] === $modalidad->value)>
                                    {{ $modalidad->etiqueta() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="activo" :value="__('Estado')" />
                        <select id="activo" name="activo"
                                class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                            <option value="">{{ __('Todos') }}</option>
                            <option value="1" @selected($filtros['activo'] === '1')>{{ __('Activos') }}</option>
                            <option value="0" @selected($filtros['activo'] === '0')>{{ __('Inactivos') }}</option>
                        </select>
                    </div>
                </div>

                <div class="mt-4 flex items-center gap-3">
                    <x-primary-button>{{ __('Filtrar') }}</x-primary-button>
                    <a href="{{ route('admin.planes.index') }}" class="text-sm text-slate-600 hover:text-slate-900">
                        {{ __('Limpiar') }}
                    </a>
                </div>
            </form>

            <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card overflow-hidden">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Plan') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Modalidad') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Duración') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Materias') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Estado') }}</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($planes as $plan)
                            <tr>
                                <td class="px-6 py-4">
                                    <p class="font-medium text-slate-900">{{ $plan->nombre }}</p>
                                    <p class="text-xs text-slate-500">{{ $plan->carrera->nombre }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    @if ($plan->modalidad === \App\Enums\Modalidad::Modular)
                                        <span class="inline-flex rounded bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">
                                            {{ $plan->modalidad->etiqueta() }}
                                        </span>
                                    @else
                                        <span class="inline-flex rounded bg-brand-100 px-2 py-0.5 text-xs font-medium text-brand-800">
                                            {{ $plan->modalidad->etiqueta() }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-slate-600">
                                    {{ $plan->semestres }} {{ $plan->semestres === 1 ? __('semestre') : __('semestres') }}
                                </td>
                                <td class="px-6 py-4 text-slate-600">{{ $plan->materias_count }}</td>
                                <td class="px-6 py-4">
                                    @if ($plan->activo)
                                        <span class="inline-flex rounded bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">{{ __('Activo') }}</span>
                                    @else
                                        <span class="inline-flex rounded bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ __('Inactivo') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('admin.planes.show', $plan) }}" class="text-brand-600 hover:text-brand-900">
                                        {{ __('Ver') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-slate-500">
                                    {{ __('Todavía no hay planes de estudio registrados.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $planes->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
