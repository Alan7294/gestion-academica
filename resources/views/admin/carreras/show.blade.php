<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-600">{{ __('Carrera') }}</p>
                <h2 class="text-xl font-semibold text-slate-900">{{ $carrera->nombre }}</h2>
                <p class="mt-0.5 text-sm text-slate-500">{{ $carrera->codigo }} · {{ $carrera->facultad?->nombre ?? '—' }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('admin.carreras.edit', $carrera) }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                    <x-icon name="pencil" class="h-4 w-4" />
                    {{ __('Editar') }}
                </a>
                <form method="POST" action="{{ route('admin.carreras.destroy', $carrera) }}" class="inline"
                      onsubmit="return confirm('{{ __('¿Eliminar esta carrera?') }}')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="rounded-lg border border-red-200 px-4 py-2.5 text-sm font-semibold text-red-700 transition hover:bg-red-50">
                        {{ __('Eliminar') }}
                    </button>
                </form>
                <a href="{{ route('admin.carreras.index') }}" class="text-sm text-slate-600 hover:text-slate-900">
                    {{ __('Volver') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card p-5">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Facultad') }}</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900">{{ $carrera->facultad?->nombre ?? '—' }}</p>
                </div>

                <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card p-5">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Cursos') }}</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900">{{ $carrera->cursos->count() }}</p>
                </div>

                <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card p-5">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Planes') }}</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900">{{ $carrera->planesEstudio->count() }}</p>
                </div>

                <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card p-5">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Estado') }}</p>
                    <p class="mt-1 text-lg font-semibold {{ $carrera->activo ? 'text-green-700' : 'text-slate-500' }}">
                        {{ $carrera->activo ? __('Activa') : __('Inactiva') }}
                    </p>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card overflow-hidden">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h3 class="font-semibold text-slate-900">{{ __('Planes de estudio') }}</h3>
                </div>

                @if ($carrera->planesEstudio->isEmpty())
                    <p class="px-6 py-5 text-sm text-slate-500">{{ __('Esta carrera todavía no tiene planes registrados.') }}</p>
                @else
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Plan') }}</th>
                                <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Modalidad') }}</th>
                                <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Duración') }}</th>
                                <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Estado') }}</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($carrera->planesEstudio as $plan)
                                <tr>
                                    <td class="px-6 py-3 text-slate-900">{{ $plan->nombre }}</td>
                                    <td class="px-6 py-3 text-slate-600">{{ $plan->modalidad->etiqueta() }}</td>
                                    <td class="px-6 py-3 text-slate-600">
                                        {{ $plan->semestres }} {{ $plan->semestres === 1 ? __('semestre') : __('semestres') }}
                                    </td>
                                    <td class="px-6 py-3 text-slate-600">{{ $plan->activo ? __('Activo') : __('Inactivo') }}</td>
                                    <td class="px-6 py-3 text-right">
                                        <a href="{{ route('admin.planes.show', $plan) }}" class="text-brand-600 hover:text-brand-900">
                                            {{ __('Ver') }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card overflow-hidden">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h3 class="font-semibold text-slate-900">{{ __('Cursos') }}</h3>
                </div>

                @if ($carrera->cursos->isEmpty())
                    <p class="px-6 py-5 text-sm text-slate-500">{{ __('Esta carrera todavía no tiene cursos registrados.') }}</p>
                @else
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Curso') }}</th>
                                <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Materia') }}</th>
                                <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Periodo') }}</th>
                                <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Docente') }}</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($carrera->cursos as $curso)
                                <tr>
                                    <td class="px-6 py-3 text-slate-900">{{ $curso->nombre }}</td>
                                    <td class="px-6 py-3 text-slate-600">{{ $curso->materia?->nombre ?? '—' }}</td>
                                    <td class="px-6 py-3 text-slate-600">{{ $curso->periodo->nombre }}</td>
                                    <td class="px-6 py-3 text-slate-600">{{ $curso->docente?->name ?? __('Sin asignar') }}</td>
                                    <td class="px-6 py-3 text-right">
                                        <a href="{{ route('admin.cursos.show', $curso) }}" class="text-brand-600 hover:text-brand-900">
                                            {{ __('Ver') }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
