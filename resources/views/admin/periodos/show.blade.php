<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-600">{{ __('Periodo académico') }}</p>
                <h2 class="text-xl font-semibold text-slate-900">{{ $periodo->nombre }}</h2>
                <p class="mt-0.5 text-sm text-slate-500">
                    {{ $periodo->fecha_inicio->format('d/m/Y') }} - {{ $periodo->fecha_fin->format('d/m/Y') }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                @unless ($periodo->activo)
                    <form method="POST" action="{{ route('admin.periodos.activar', $periodo) }}" class="inline">
                        @csrf
                        <button type="submit"
                                class="rounded-lg border border-brand-200 px-4 py-2.5 text-sm font-semibold text-brand-700 transition hover:bg-brand-50">
                            {{ __('Marcar vigente') }}
                        </button>
                    </form>
                @endunless

                <a href="{{ route('admin.periodos.edit', $periodo) }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                    <x-icon name="pencil" class="h-4 w-4" />
                    {{ __('Editar') }}
                </a>
                <form method="POST" action="{{ route('admin.periodos.destroy', $periodo) }}" class="inline"
                      onsubmit="return confirm('{{ __('¿Eliminar este periodo?') }}')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="rounded-lg border border-red-200 px-4 py-2.5 text-sm font-semibold text-red-700 transition hover:bg-red-50">
                        {{ __('Eliminar') }}
                    </button>
                </form>
                <a href="{{ route('admin.periodos.index') }}" class="text-sm text-slate-600 hover:text-slate-900">
                    {{ __('Volver') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card p-5">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Inicio') }}</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900">{{ $periodo->fecha_inicio->format('d/m/Y') }}</p>
                </div>

                <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card p-5">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Fin') }}</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900">{{ $periodo->fecha_fin->format('d/m/Y') }}</p>
                </div>

                <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card p-5">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Estado') }}</p>
                    <p class="mt-1 text-lg font-semibold {{ $periodo->activo ? 'text-brand-700' : 'text-slate-500' }}">
                        {{ $periodo->activo ? __('Vigente') : __('Cerrado') }}
                    </p>
                </div>

                <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card p-5">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Cursos') }}</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900">{{ $periodo->cursos->count() }}</p>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card overflow-hidden">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h3 class="font-semibold text-slate-900">{{ __('Cursos del periodo') }}</h3>
                </div>

                @if ($periodo->cursos->isEmpty())
                    <p class="px-6 py-5 text-sm text-slate-500">{{ __('Este periodo todavía no tiene cursos registrados.') }}</p>
                @else
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Curso') }}</th>
                                <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Carrera') }}</th>
                                <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Materia') }}</th>
                                <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Docente') }}</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($periodo->cursos as $curso)
                                <tr>
                                    <td class="px-6 py-3 text-slate-900">{{ $curso->nombre }}</td>
                                    <td class="px-6 py-3 text-slate-600">{{ $curso->carrera->nombre }}</td>
                                    <td class="px-6 py-3 text-slate-600">{{ $curso->materia?->nombre ?? '—' }}</td>
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
