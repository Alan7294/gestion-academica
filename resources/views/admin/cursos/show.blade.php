<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $curso->nombre }}
                <span class="ms-2 font-mono text-sm text-gray-500">{{ $curso->codigo }}</span>
            </h2>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.cursos.edit', $curso) }}"
                   class="text-sm text-indigo-600 hover:text-indigo-900">{{ __('Editar curso') }}</a>
                <a href="{{ route('admin.cursos.index') }}"
                   class="text-sm text-gray-600 hover:text-gray-900">{{ __('Volver') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg p-6 grid grid-cols-2 sm:grid-cols-3 gap-6 text-sm">
                <div>
                    <p class="text-gray-500">{{ __('Carrera') }}</p>
                    <p class="mt-1 font-medium text-gray-900">{{ $curso->carrera->nombre }}</p>
                </div>
                <div>
                    <p class="text-gray-500">{{ __('Periodo') }}</p>
                    <p class="mt-1 font-medium text-gray-900">{{ $curso->periodo->nombre }}</p>
                </div>
                <div>
                    <p class="text-gray-500">{{ __('Docente') }}</p>
                    <p class="mt-1 font-medium text-gray-900">{{ $curso->docente?->name ?? __('Sin asignar') }}</p>
                </div>
                <div>
                    <p class="text-gray-500">{{ __('Semestre') }}</p>
                    <p class="mt-1 font-medium text-gray-900">{{ $curso->semestre }}</p>
                </div>
                <div>
                    <p class="text-gray-500">{{ __('Créditos') }}</p>
                    <p class="mt-1 font-medium text-gray-900">{{ $curso->creditos }}</p>
                </div>
                <div>
                    <p class="text-gray-500">{{ __('Cupo') }}</p>
                    <p class="mt-1 font-medium text-gray-900">{{ $curso->cupo === 0 ? __('Sin límite') : $curso->cupo }}</p>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-900">{{ __('Horario del curso') }}</h3>

                @if ($curso->horarios->isEmpty())
                    <p class="mt-2 text-sm text-gray-600">{{ __('Este curso todavía no tiene horarios asignados.') }}</p>
                @else
                    <table class="mt-4 min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left font-medium text-gray-500">{{ __('Día') }}</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-500">{{ __('Horario') }}</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-500">{{ __('Aula') }}</th>
                                <th class="px-4 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($curso->horarios as $horario)
                                <tr>
                                    <td class="px-4 py-2 text-gray-900">{{ $horario->dia_semana->etiqueta() }}</td>
                                    <td class="px-4 py-2 text-gray-600">{{ $horario->rango_texto }}</td>
                                    <td class="px-4 py-2 text-gray-600">{{ $horario->aula ?? '—' }}</td>
                                    <td class="px-4 py-2 text-right">
                                        <form method="POST" action="{{ route('admin.horarios.destroy', [$curso, $horario]) }}" class="inline"
                                              onsubmit="return confirm('{{ __('¿Eliminar este horario?') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900">{{ __('Eliminar') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                <form method="POST" action="{{ route('admin.horarios.store', $curso) }}"
                      class="mt-6 grid grid-cols-1 sm:grid-cols-5 gap-3 items-end">
                    @csrf

                    <div>
                        <x-input-label for="dia_semana" :value="__('Día')" />
                        <select id="dia_semana" name="dia_semana" required
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm">
                            @foreach (\App\Enums\DiaSemana::opciones() as $valor => $etiqueta)
                                <option value="{{ $valor }}" @selected(old('dia_semana') == $valor)>{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="hora_inicio" :value="__('Desde')" />
                        <x-text-input id="hora_inicio" name="hora_inicio" type="time" class="mt-1 block w-full text-sm"
                                      :value="old('hora_inicio')" required />
                    </div>

                    <div>
                        <x-input-label for="hora_fin" :value="__('Hasta')" />
                        <x-text-input id="hora_fin" name="hora_fin" type="time" class="mt-1 block w-full text-sm"
                                      :value="old('hora_fin')" required />
                    </div>

                    <div>
                        <x-input-label for="aula" :value="__('Aula')" />
                        <x-text-input id="aula" name="aula" type="text" class="mt-1 block w-full text-sm"
                                      :value="old('aula')" />
                    </div>

                    <div>
                        <x-primary-button>{{ __('Agregar') }}</x-primary-button>
                    </div>
                </form>

                <p class="mt-2 text-xs text-gray-500">
                    {{ __('El sistema rechaza un horario si el docente ya tiene otra clase en ese día y franja horaria.') }}
                </p>
            </div>
        </div>
    </div>
</x-app-layout>
