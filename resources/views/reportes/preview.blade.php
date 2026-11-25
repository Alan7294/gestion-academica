<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-600">{{ __('Reportes') }}</p>
                <h2 class="text-xl font-semibold text-slate-900">{{ $titulo }}</h2>
            </div>

            <a href="{{ $volver }}"
               class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                <x-icon name="arrow-left" class="h-4 w-4" />
                {{ __('Volver a reportes') }}
            </a>
        </div>
    </x-slot>

    @php
        $filas = $reporte->filas();
        $contexto = $reporte->contexto();
        $valoresSeleccionados = [
            'periodo_id' => $filtros->periodoId,
            'carrera_id' => $filtros->carreraId,
            'curso_id' => $filtros->cursoId,
            'docente_id' => $filtros->docenteId,
        ];
        $selects = [
            ['etiqueta' => __('Periodo'), 'nombre' => 'periodo_id', 'opciones' => $opciones['periodos'], 'texto' => 'nombre'],
            ['etiqueta' => __('Carrera'), 'nombre' => 'carrera_id', 'opciones' => $opciones['carreras'], 'texto' => 'nombre'],
            ['etiqueta' => __('Materia'), 'nombre' => 'curso_id', 'opciones' => $opciones['cursos'], 'texto' => 'codigo_nombre'],
            ['etiqueta' => __('Docente'), 'nombre' => 'docente_id', 'opciones' => $opciones['docentes'], 'texto' => 'name'],
        ];
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <p class="text-sm text-slate-600">{{ $reporte->descripcion() }}</p>

            <form method="GET" action="{{ url()->current() }}" class="card p-5 sm:p-6">
                <div class="flex items-center gap-2">
                    <x-icon name="search" class="h-5 w-5 text-brand-600" />
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Parámetros del reporte') }}</h3>
                </div>

                @if ($errors->any())
                    <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        <ul class="list-inside list-disc space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach ($selects as $select)
                        <div>
                            <x-input-label :for="$select['nombre']" :value="$select['etiqueta']" />
                            <select id="{{ $select['nombre'] }}"
                                    name="{{ $select['nombre'] }}"
                                    class="mt-1 block w-full rounded-lg border-slate-300 text-slate-900 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                                <option value="">{{ __('Todos') }}</option>
                                @foreach ($select['opciones'] as $opcion)
                                    @php
                                        $valor = (string) $opcion->id;
                                        $seleccionado = (string) $valoresSeleccionados[$select['nombre']] === $valor;
                                        $texto = $select['texto'] === 'codigo_nombre'
                                            ? ($opcion->materia?->codigo ?? '—').' - '.$opcion->nombre
                                            : $opcion->{$select['texto']};
                                    @endphp
                                    <option value="{{ $valor }}" @selected($seleccionado)>{{ $texto }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach

                    <div>
                        <x-input-label for="estado" :value="__('Estado de inscripción')" />
                        <select id="estado"
                                name="estado"
                                class="mt-1 block w-full rounded-lg border-slate-300 text-slate-900 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                            <option value="">{{ __('Todos') }}</option>
                            @foreach ($opciones['estados'] as $estado)
                                <option value="{{ $estado->value }}" @selected($filtros->estado === $estado->value)>
                                    {{ $estado->etiqueta() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="nota_desde" :value="__('Nota mínima')" />
                        <input id="nota_desde"
                               type="number"
                               min="0"
                               max="100"
                               step="0.01"
                               name="nota_desde"
                               value="{{ $filtros->notaDesde }}"
                               placeholder="0"
                               class="mt-1 block w-full rounded-lg border-slate-300 text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                    </div>

                    <div>
                        <x-input-label for="nota_hasta" :value="__('Nota máxima')" />
                        <input id="nota_hasta"
                               type="number"
                               min="0"
                               max="100"
                               step="0.01"
                               name="nota_hasta"
                               value="{{ $filtros->notaHasta }}"
                               placeholder="100"
                               class="mt-1 block w-full rounded-lg border-slate-300 text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap items-center gap-3">
                    <x-primary-button>{{ __('Aplicar filtros') }}</x-primary-button>
                    <a href="{{ url()->current() }}"
                       class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                        {{ __('Limpiar filtros') }}
                    </a>
                    @if ($filtros->activos())
                        <span class="text-xs font-medium text-brand-700">{{ __('Filtros activos') }}</span>
                    @endif
                </div>
            </form>

            @if ($contexto !== [])
                <div class="card p-5 sm:p-6">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Contexto del reporte') }}</h3>
                    <dl class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($contexto as $etiqueta => $valor)
                            <div class="rounded-lg bg-slate-50 px-3 py-2">
                                <dt class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">{{ $etiqueta }}</dt>
                                <dd class="mt-0.5 text-sm text-slate-800">{{ $valor }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            @endif

            <div class="card overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-6 py-5">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">{{ __('Vista previa') }}</h3>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ trans_choice(':count registro|:count registros', count($filas)) }}
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ $descargas['pdf'] }}"
                           class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700">
                            <x-icon name="download" class="h-4 w-4" />
                            {{ __('Descargar PDF') }}
                        </a>
                        <a href="{{ $descargas['excel'] }}"
                           class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                            <x-icon name="download" class="h-4 w-4" />
                            {{ __('Descargar Excel') }}
                        </a>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50/80">
                            <tr>
                                @foreach ($reporte->encabezados() as $encabezado)
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        {{ $encabezado }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($filas as $fila)
                                <tr class="transition hover:bg-slate-50/60">
                                    @foreach ($fila as $celda)
                                        <td class="px-6 py-3 text-slate-700">{{ $celda }}</td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($reporte->encabezados()) }}"
                                        class="px-6 py-12 text-center text-sm text-slate-500">
                                        {{ __('No hay datos para los filtros seleccionados.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
