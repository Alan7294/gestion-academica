<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ $curso->nombre }}
                <span class="ms-2 font-mono text-sm text-slate-500">{{ $curso->codigo }}</span>
            </h2>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.cursos.edit', $curso) }}"
                   class="text-sm text-brand-600 hover:text-brand-900">{{ __('Editar curso') }}</a>
                @if ($puedeEliminarse)
                    <form method="POST" action="{{ route('admin.cursos.destroy', $curso) }}" class="inline"
                          onsubmit="return confirm('{{ __('¿Eliminar este curso? También se borrarán sus horarios y evaluaciones.') }}')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm text-red-600 hover:text-red-900">{{ __('Eliminar curso') }}</button>
                    </form>
                @else
                    <span class="text-xs text-slate-400">{{ __('No se puede eliminar: tiene inscripciones') }}</span>
                @endif
                <a href="{{ route('admin.cursos.index') }}"
                   class="text-sm text-slate-600 hover:text-slate-900">{{ __('Volver') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card p-6 grid grid-cols-2 sm:grid-cols-3 gap-6 text-sm">
                <div>
                    <p class="text-slate-500">{{ __('Carrera') }}</p>
                    <p class="mt-1 font-medium text-slate-900">{{ $curso->carrera->nombre }}</p>
                </div>
                <div>
                    <p class="text-slate-500">{{ __('Plan de estudios') }}</p>
                    <p class="mt-1 font-medium text-slate-900">{{ $curso->planEstudio?->nombre ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-slate-500">{{ __('Materia') }}</p>
                    @if ($curso->materia)
                        <p class="mt-1 font-medium text-slate-900">{{ $curso->materia->codigo }} - {{ $curso->materia->nombre }}</p>
                        <p class="mt-0.5 text-xs text-slate-500">{{ $curso->materia->carrera->nombre }}</p>
                    @else
                        <p class="mt-1 font-medium text-red-600">{{ __('Sin asignar: no admite inscripciones') }}</p>
                    @endif
                </div>
                <div>
                    <p class="text-slate-500">{{ __('Modalidad') }}</p>
                    <p class="mt-1 font-medium text-slate-900">{{ $curso->modalidad->etiqueta() }}</p>
                </div>
                <div>
                    <p class="text-slate-500">{{ __('Periodo') }}</p>
                    <p class="mt-1 font-medium text-slate-900">{{ $curso->periodo->nombre }}</p>
                </div>
                <div>
                    <p class="text-slate-500">{{ __('Docente') }}</p>
                    <p class="mt-1 font-medium text-slate-900">{{ $curso->docente?->name ?? __('Sin asignar') }}</p>
                </div>
                <div>
                    <p class="text-slate-500">{{ __('Semestre') }}</p>
                    <p class="mt-1 font-medium text-slate-900">{{ $curso->semestre }}</p>
                </div>
                <div>
                    <p class="text-slate-500">{{ __('Créditos') }}</p>
                    <p class="mt-1 font-medium text-slate-900">{{ $curso->creditos }}</p>
                </div>
                <div>
                    <p class="text-slate-500">{{ __('Cupo') }}</p>
                    <p class="mt-1 font-medium text-slate-900">{{ $curso->cupo === 0 ? __('Sin límite') : $curso->cupo }}</p>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card p-6">
                @php($esModular = $curso->modalidad === \App\Enums\Modalidad::Modular)

                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <h3 class="font-semibold text-slate-900">{{ __('Horario del curso') }}</h3>
                    <span class="text-xs text-slate-500">
                        {{ $esModular ? __('Modalidad modular · 20 días hábiles') : __('Modalidad semestral · vigencia de todo el periodo') }}
                    </span>
                </div>

                @if ($curso->horarios->isEmpty())
                    <p class="mt-2 text-sm text-slate-600">{{ __('Este curso todavía no tiene horarios asignados.') }}</p>
                @else
                    <table class="mt-4 min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-2 text-left font-medium text-slate-500">{{ __('Día') }}</th>
                                <th class="px-4 py-2 text-left font-medium text-slate-500">{{ __('Horario') }}</th>
                                <th class="px-4 py-2 text-left font-medium text-slate-500">{{ __('Vigencia') }}</th>
                                <th class="px-4 py-2 text-left font-medium text-slate-500">{{ __('Aula') }}</th>
                                <th class="px-4 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($curso->horarios as $horario)
                                <tr>
                                    <td class="px-4 py-2 text-slate-900">{{ $horario->dia_semana->etiqueta() }}</td>
                                    <td class="px-4 py-2 text-slate-600">{{ $horario->rango_texto }}</td>
                                    <td class="px-4 py-2 text-slate-600">{{ $horario->vigencia_texto }}</td>
                                    <td class="px-4 py-2 text-slate-600">{{ $horario->aula ?? '—' }}</td>
                                    <td class="px-4 py-2 text-right">
                                        <form method="POST" action="{{ route('admin.horarios.destroy', [$curso, $horario]) }}" class="inline"
                                              onsubmit="return confirm('{{ $esModular ? __('¿Eliminar este módulo? Se borrarán sus veinte sesiones.') : __('¿Eliminar este horario?') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900">
                                                {{ $esModular ? __('Eliminar módulo') : __('Eliminar') }}
                                            </button>
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

                    @if ($esModular)
                        <div>
                            <x-input-label for="fecha_inicio" :value="__('Inicio del módulo')" />
                            <x-text-input id="fecha_inicio" name="fecha_inicio" type="date"
                                          class="mt-1 block w-full text-sm" :value="old('fecha_inicio')"
                                          min="{{ $curso->periodo->fecha_inicio->toDateString() }}"
                                          max="{{ $curso->periodo->fecha_fin->toDateString() }}" required />
                            <x-input-error class="mt-2" :messages="$errors->get('fecha_inicio')" />
                        </div>
                    @else
                        <div>
                            <x-input-label for="dia_semana" :value="__('Día')" />
                            <select id="dia_semana" name="dia_semana" required
                                    class="mt-1 block w-full border-slate-300 rounded-md shadow-sm text-sm">
                                @foreach (\App\Enums\DiaSemana::opciones() as $valor => $etiqueta)
                                    <option value="{{ $valor }}" @selected(old('dia_semana') == $valor)>{{ $etiqueta }}</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('dia_semana')" />
                        </div>
                    @endif

                    <div>
                        <x-input-label for="hora_inicio" :value="__('Desde')" />
                        <x-text-input id="hora_inicio" name="hora_inicio" type="time" class="mt-1 block w-full text-sm"
                                      :value="old('hora_inicio')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('hora_inicio')" />
                    </div>

                    <div>
                        <x-input-label for="hora_fin" :value="__('Hasta')" />
                        <x-text-input id="hora_fin" name="hora_fin" type="time" class="mt-1 block w-full text-sm"
                                      :value="old('hora_fin')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('hora_fin')" />
                    </div>

                    <div>
                        <x-input-label for="aula" :value="__('Aula')" />
                        <x-text-input id="aula" name="aula" type="text" class="mt-1 block w-full text-sm"
                                      :value="old('aula')" />
                    </div>

                    <div>
                        <x-primary-button>{{ $esModular ? __('Generar módulo') : __('Agregar') }}</x-primary-button>
                    </div>
                </form>

                <p class="mt-2 text-xs text-slate-500">
                    @if ($esModular)
                        {{ __('Se crean sesiones de lunes a viernes durante 20 días hábiles. El módulo se rechaza si el docente ya tiene clase en esa franja y fechas.') }}
                    @else
                        {{ __('El sistema rechaza un horario si el docente ya tiene otra clase en ese día, franja y periodo.') }}
                    @endif
                </p>
            </div>
        </div>
    </div>
</x-app-layout>
