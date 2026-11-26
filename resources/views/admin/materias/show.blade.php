<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-600">{{ __('Materia') }}</p>
                <h2 class="text-xl font-semibold text-slate-900">{{ $materia->nombre }}</h2>
                <p class="mt-0.5 text-sm text-slate-500">{{ $materia->codigo }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('admin.materias.edit', $materia) }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                    <x-icon name="pencil" class="h-4 w-4" />
                    {{ __('Editar') }}
                </a>
                <form method="POST" action="{{ route('admin.materias.destroy', $materia) }}" class="inline"
                      onsubmit="return confirm('{{ __('¿Eliminar esta materia?') }}')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="rounded-lg border border-red-200 px-4 py-2.5 text-sm font-semibold text-red-700 transition hover:bg-red-50">
                        {{ __('Eliminar') }}
                    </button>
                </form>
                <a href="{{ route('admin.materias.index') }}" class="text-sm text-slate-600 hover:text-slate-900">
                    {{ __('Volver') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card p-5">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Cursos') }}</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900">{{ $materia->cursos->count() }}</p>
                </div>

                <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card p-5">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Planes') }}</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900">{{ $materia->planesEstudio->count() }}</p>
                </div>

                <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card p-5">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Requisitos') }}</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900">{{ $materia->requisitos->unique('id')->count() }}</p>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card overflow-hidden">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h3 class="font-semibold text-slate-900">{{ __('Planes que incluyen la materia') }}</h3>
                </div>

                @if ($materia->planesEstudio->isEmpty())
                    <p class="px-6 py-5 text-sm text-slate-500">{{ __('La materia no está incluida en ningún plan de estudio.') }}</p>
                @else
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Carrera') }}</th>
                                <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Plan') }}</th>
                                <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Semestre') }}</th>
                                <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Créditos') }}</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($materia->planesEstudio as $plan)
                                <tr>
                                    <td class="px-6 py-3 text-slate-600">{{ $plan->carrera?->nombre ?? '—' }}</td>
                                    <td class="px-6 py-3 text-slate-900">{{ $plan->nombre }}</td>
                                    <td class="px-6 py-3 text-slate-600">{{ $plan->pivot->semestre }}</td>
                                    <td class="px-6 py-3 text-slate-600">{{ $plan->pivot->creditos }}</td>
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
                    <h3 class="font-semibold text-slate-900">{{ __('Cursos de la materia') }}</h3>
                </div>

                @if ($materia->cursos->isEmpty())
                    <p class="px-6 py-5 text-sm text-slate-500">{{ __('La materia todavía no tiene cursos registrados.') }}</p>
                @else
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Curso') }}</th>
                                <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Periodo') }}</th>
                                <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Docente') }}</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($materia->cursos as $curso)
                                <tr>
                                    <td class="px-6 py-3 text-slate-900">{{ $curso->nombre }}</td>
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

            <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card p-6">
                <h3 class="font-semibold text-slate-900">{{ __('Requisitos para cursarla') }}</h3>

                @if ($materia->requisitos->isEmpty())
                    <p class="mt-2 text-sm text-slate-500">{{ __('Esta materia no tiene requisitos declarados.') }}</p>
                @else
                    <ul class="mt-3 flex flex-wrap gap-2">
                        @foreach ($materia->requisitos->unique('id') as $requisito)
                            <li class="rounded-lg bg-slate-100 px-3 py-2 text-sm text-slate-700">
                                {{ $requisito->codigo }} - {{ $requisito->nombre }}
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
