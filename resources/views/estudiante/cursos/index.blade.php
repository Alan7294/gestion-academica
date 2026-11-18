<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Oferta académica') }}
                @if ($periodo)
                    <span class="ms-2 text-sm font-normal text-gray-500">{{ $periodo->nombre }}</span>
                @endif
            </h2>

            <a href="{{ route('estudiante.inscripciones.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900">
                {{ __('Mis cursos') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <form method="GET" action="{{ route('estudiante.cursos.index') }}"
                  class="bg-white shadow-sm sm:rounded-lg p-4 grid grid-cols-1 sm:grid-cols-4 gap-3">
                <div class="sm:col-span-2">
                    <x-input-label for="busqueda" :value="__('Buscar curso')" />
                    <x-text-input id="busqueda" name="busqueda" type="text" class="mt-1 block w-full text-sm"
                                  :value="$filtros['busqueda']" placeholder="{{ __('Nombre o código') }}" />
                </div>

                <div>
                    <x-input-label for="carrera_id" :value="__('Carrera')" />
                    <select id="carrera_id" name="carrera_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm">
                        <option value="">{{ __('Todas') }}</option>
                        @foreach ($carreras as $carrera)
                            <option value="{{ $carrera->id }}" @selected($filtros['carrera_id'] == $carrera->id)>
                                {{ $carrera->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <x-primary-button>{{ __('Buscar') }}</x-primary-button>
                    <a href="{{ route('estudiante.cursos.index') }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Limpiar') }}</a>
                </div>
            </form>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse ($cursos as $curso)
                    @php($inscrito = in_array($curso->id, $inscritos, true))
                    <div class="bg-white shadow-sm sm:rounded-lg p-5 flex flex-col">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="font-mono text-xs text-gray-500">{{ $curso->codigo }}</p>
                                <h3 class="mt-1 font-semibold text-gray-900">{{ $curso->nombre }}</h3>
                            </div>

                            @if ($inscrito)
                                <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                    {{ __('Inscrito') }}
                                </span>
                            @endif
                        </div>

                        <dl class="mt-3 space-y-1 text-sm text-gray-600">
                            <div>{{ $curso->carrera->nombre }}</div>
                            <div>{{ __('Docente') }}: {{ $curso->docente?->name ?? __('Por asignar') }}</div>
                            <div>{{ __('Créditos') }}: {{ $curso->creditos }}</div>
                            <div>
                                {{ __('Cupo') }}:
                                {{ $curso->cupo === 0 ? __('Sin límite') : ($curso->cupo - $curso->alumnos_inscritos_count).' / '.$curso->cupo }}
                            </div>
                        </dl>

                        @if ($curso->horarios->isNotEmpty())
                            <ul class="mt-3 text-xs text-gray-500 space-y-0.5">
                                @foreach ($curso->horarios as $horario)
                                    <li>{{ $horario->dia_semana->etiqueta() }} {{ $horario->rango_texto }}</li>
                                @endforeach
                            </ul>
                        @endif

                        <div class="mt-auto pt-4 flex items-center justify-between gap-2">
                            <a href="{{ route('estudiante.cursos.show', $curso) }}" class="text-sm text-gray-600 hover:text-gray-900">
                                {{ __('Ver detalle') }}
                            </a>

                            @unless ($inscrito)
                                <form method="POST" action="{{ route('estudiante.inscripciones.store', $curso) }}">
                                    @csrf
                                    <x-primary-button class="text-xs">{{ __('Inscribirme') }}</x-primary-button>
                                </form>
                            @endunless
                        </div>
                    </div>
                @empty
                    <div class="md:col-span-3 bg-white shadow-sm sm:rounded-lg p-8 text-center text-gray-500">
                        {{ __('No hay cursos disponibles con esos filtros.') }}
                    </div>
                @endforelse
            </div>

            <div>
                {{ $cursos->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
