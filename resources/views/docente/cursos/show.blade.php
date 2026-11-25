<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ $curso->nombre }}
                <span class="ms-2 font-mono text-sm text-slate-500">{{ $curso->materia?->codigo ?? '—' }}</span>
            </h2>

            <div class="flex items-center gap-4">
                <a href="{{ route('docente.cursos.calificaciones', $curso) }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                    <x-icon name="document" class="h-4 w-4" />
                    {{ __('Calificaciones') }}
                </a>

                <a href="{{ route('docente.cursos.index') }}" class="text-sm text-slate-600 hover:text-slate-900">
                    {{ __('Volver') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card p-6 grid grid-cols-2 sm:grid-cols-4 gap-6 text-sm">
                <div>
                    <p class="text-slate-500">{{ __('Carrera') }}</p>
                    <p class="mt-1 font-medium text-slate-900">{{ $curso->carrera->nombre }}</p>
                </div>
                <div>
                    <p class="text-slate-500">{{ __('Periodo') }}</p>
                    <p class="mt-1 font-medium text-slate-900">{{ $curso->periodo->nombre }}</p>
                </div>
                <div>
                    <p class="text-slate-500">{{ __('Inscritos') }}</p>
                    <p class="mt-1 font-medium text-slate-900">
                        {{ $curso->alumnosInscritos->count() }}{{ $curso->cupo > 0 ? ' / '.$curso->cupo : '' }}
                    </p>
                </div>
                <div>
                    <p class="text-slate-500">{{ __('Horario') }}</p>
                    <div class="mt-1 font-medium text-slate-900">
                        @forelse ($curso->horarios as $horario)
                            <div class="text-sm">{{ $horario->descripcion }}</div>
                        @empty
                            <span class="text-sm">{{ __('Por definir') }}</span>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h3 class="font-semibold text-slate-900">{{ __('Alumnos inscritos') }}</h3>
                </div>

                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Estudiante') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('CI') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Correo') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Inscrito') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($curso->alumnosInscritos as $inscripcion)
                            <tr>
                                <td class="px-6 py-4 text-slate-900">{{ $inscripcion->estudiante->name }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ $inscripcion->estudiante->ci ?? '—' }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ $inscripcion->estudiante->email }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ $inscripcion->inscrito_en->format('d/m/Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-slate-500">
                                    {{ __('Todavía no hay alumnos inscritos en este curso.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
