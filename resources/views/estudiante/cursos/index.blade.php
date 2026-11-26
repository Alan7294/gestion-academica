<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Oferta académica') }}
                @if ($periodo)
                    <span class="ms-2 text-sm font-normal text-slate-500">{{ $periodo->nombre }}</span>
                @endif
            </h2>

            <a href="{{ route('estudiante.inscripciones.index') }}" class="text-sm text-brand-600 hover:text-brand-900">
                {{ __('Mis cursos') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <form method="GET" action="{{ route('estudiante.cursos.index') }}"
                  class="rounded-2xl border border-slate-200/70 bg-white shadow-card p-4 grid grid-cols-1 sm:grid-cols-4 gap-3">
                <div class="sm:col-span-2">
                    <x-input-label for="busqueda" :value="__('Buscar curso')" />
                    <x-text-input id="busqueda" name="busqueda" type="text" class="mt-1 block w-full text-sm"
                                  :value="$filtros['busqueda']" placeholder="{{ __('Nombre o código') }}" />
                </div>

                <div>
                    <x-input-label for="carrera_id" :value="__('Carrera')" />
                    <select id="carrera_id" name="carrera_id" class="mt-1 block w-full border-slate-300 rounded-md shadow-sm text-sm">
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
                    <a href="{{ route('estudiante.cursos.index') }}" class="text-sm text-slate-600 hover:text-slate-900">{{ __('Limpiar') }}</a>
                </div>
            </form>

            @if ($carreras->isEmpty())
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-800">
                    {{ __('No tienes matrículas activas. Solicita al administrador que te matricule en una carrera para ver la oferta académica.') }}
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse ($cursos as $curso)
                    @php($inscrito = in_array($curso->id, $inscritos, true))
                    <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card p-5 flex flex-col">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="font-mono text-xs text-slate-500">{{ $curso->materia?->codigo ?? '—' }}</p>
                                <h3 class="mt-1 font-semibold text-slate-900">{{ $curso->nombre }}</h3>
                            </div>

                            @if ($inscrito)
                                <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                    {{ __('Inscrito') }}
                                </span>
                            @endif
                        </div>

                        <dl class="mt-3 space-y-1 text-sm text-slate-600">
                            <div>
                                {{ __('Carreras') }}:
                                {{ $curso->planesEstudio->pluck('carrera.nombre')->filter()->unique()->implode(', ') ?: $curso->carrera->nombre }}
                            </div>
                            <div>{{ __('Docente') }}: {{ $curso->docente?->name ?? __('Por asignar') }}</div>
                            <div>
                                {{ __('Cupo') }}:
                                {{ $curso->cupo === 0 ? __('Sin límite') : ($curso->cupo - $curso->alumnos_inscritos_count).' / '.$curso->cupo }}
                            </div>
                        </dl>

                        @if ($curso->horarios->isNotEmpty())
                            <ul class="mt-3 text-xs text-slate-500 space-y-0.5">
                                @foreach ($curso->horarios as $horario)
                                    <li>{{ $horario->descripcion }}</li>
                                @endforeach
                            </ul>
                        @endif

                        @if ($curso->materia_id && $convalidadas->contains($curso->materia_id))
                            <p class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800">
                                {{ __('Ya tienes esta materia convalidada por equivalencia. Puedes inscribirte igual si deseas cursarla.') }}
                            </p>
                        @endif

                        <div class="mt-auto pt-4 flex items-center justify-between gap-2">
                            <a href="{{ route('estudiante.cursos.show', $curso) }}" class="text-sm text-slate-600 hover:text-slate-900">
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
                    <div class="md:col-span-3 rounded-2xl border border-slate-200/70 bg-white shadow-card p-8 text-center text-slate-500">
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
