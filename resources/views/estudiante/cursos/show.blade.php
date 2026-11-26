<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ $curso->nombre }}
            </h2>

            <a href="{{ route('estudiante.cursos.index') }}" class="text-sm text-slate-600 hover:text-slate-900">
                {{ __('Volver a la oferta') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card p-6 grid grid-cols-2 sm:grid-cols-3 gap-6 text-sm">
                <div>
                    <p class="text-slate-500">{{ __('Código de la materia') }}</p>
                    <p class="mt-1 font-mono font-medium text-slate-900">{{ $curso->materia?->codigo ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-slate-500">{{ __('Carreras participantes') }}</p>
                    <p class="mt-1 font-medium text-slate-900">
                        {{ $curso->planesEstudio->pluck('carrera.nombre')->filter()->unique()->implode(', ') ?: $curso->carrera->nombre }}
                    </p>
                </div>
                <div>
                    <p class="text-slate-500">{{ __('Periodo') }}</p>
                    <p class="mt-1 font-medium text-slate-900">{{ $curso->periodo->nombre }}</p>
                </div>
                <div>
                    <p class="text-slate-500">{{ __('Docente') }}</p>
                    <p class="mt-1 font-medium text-slate-900">{{ $curso->docente?->name ?? __('Por asignar') }}</p>
                </div>
                <div>
                    <p class="text-slate-500">{{ __('Inscritos') }}</p>
                    <p class="mt-1 font-medium text-slate-900">
                        {{ $curso->alumnos_inscritos_count }}{{ $curso->cupo > 0 ? ' / '.$curso->cupo : '' }}
                    </p>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card p-6">
                <h3 class="font-semibold text-slate-900">{{ __('Horario') }}</h3>

                @if ($curso->horarios->isEmpty())
                    <p class="mt-2 text-sm text-slate-600">{{ __('El horario todavía no fue definido.') }}</p>
                @else
                    <ul class="mt-3 divide-y divide-slate-100 text-sm">
                        @foreach ($curso->horarios as $horario)
                            <li class="py-2 flex justify-between">
                                <span class="text-slate-900">{{ $horario->dia_semana->etiqueta() }}</span>
                                <span class="text-slate-600">{{ $horario->rango_texto }} · {{ $horario->vigencia_texto }} · {{ $horario->aula ?? __('Aula por definir') }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            @if ($curso->materia_id && $convalidadas->contains($curso->materia_id))
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    {{ __('Ya tienes esta materia convalidada por equivalencia. Puedes inscribirte igual si deseas cursarla.') }}
                </div>
            @endif

            <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card p-6 flex items-center justify-between">
                <p class="text-sm text-slate-600">
                    {{ __('Al inscribirte aceptas cursar esta materia en el horario indicado.') }}
                </p>

                @if ($curso->estaInscrito(auth()->user()))
                    <span class="inline-flex px-3 py-1 rounded text-sm font-medium bg-green-100 text-green-800">
                        {{ __('Ya estás inscrito') }}
                    </span>
                @elseif ($curso->inscripcionesAbiertas())
                    <form method="POST" action="{{ route('estudiante.inscripciones.store', $curso) }}">
                        @csrf
                        <x-primary-button>{{ __('Inscribirme') }}</x-primary-button>
                    </form>
                @else
                    <span class="inline-flex px-3 py-1 rounded text-sm font-medium bg-slate-100 text-slate-600">
                        {{ __('Inscripción cerrada') }}
                    </span>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
