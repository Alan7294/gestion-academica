<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-600">{{ __('Panel del docente') }}</p>
                <h2 class="text-xl font-semibold text-slate-900">{{ __('Mis cursos') }}</h2>
            </div>

            <a href="{{ route('docente.reportes.index') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                <x-icon name="chart" class="h-4 w-4 text-brand-600" />
                {{ __('Reportes') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-stat-card :title="__('Cursos asignados')" :value="$cursos->count()" icon="book" tone="brand" :hint="__('Periodo vigente y anteriores')" />
                <x-stat-card :title="__('Alumnos inscritos')" :value="$totalAlumnos" icon="users" tone="ink" :hint="__('Suma de todos tus cursos')" />
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="card overflow-hidden lg:col-span-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('Cursos a cargo') }}</h3>
                        <a href="{{ route('docente.cursos.index') }}" class="link-brand text-sm">{{ __('Ver todos') }}</a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-sm">
                            <thead class="bg-slate-50/80">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('Curso') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('Carrera') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('Inscritos') }}</th>
                                    <th class="px-6 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($cursos as $curso)
                                    <tr class="transition hover:bg-slate-50/60">
                                        <td class="px-6 py-4">
                                            <p class="font-medium text-slate-900">{{ $curso->nombre }}</p>
                                            <p class="font-mono text-xs text-slate-500">{{ $curso->materia?->codigo ?? '—' }}</p>
                                        </td>
                                        <td class="px-6 py-4 text-slate-600">{{ $curso->carrera->nombre }}</td>
                                        <td class="px-6 py-4 text-slate-600">{{ $curso->alumnos_inscritos_count }}</td>
                                        <td class="px-6 py-4 text-right">
                                            <a href="{{ route('docente.cursos.show', $curso) }}" class="link-brand text-sm">
                                                {{ __('Ver alumnos') }}
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-10 text-center text-sm text-slate-500">
                                            {{ __('Todavía no tienes cursos asignados.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card h-fit p-6">
                    <h3 class="card-title">{{ __('Atajos') }}</h3>
                    <p class="mt-0.5 text-xs text-slate-500">{{ __('Lo que más usas en el día a día.') }}</p>

                    <div class="mt-5 space-y-2">
                        <a href="{{ route('docente.reportes.index') }}"
                           class="flex items-center gap-3 rounded-xl border border-slate-200 px-3 py-2.5 transition hover:border-brand-300 hover:bg-brand-50/50">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-700"><x-icon name="document" class="h-4 w-4" /></span>
                            <span class="text-sm font-medium text-slate-800">{{ __('Notas y reportes') }}</span>
                            <x-icon name="arrow-right" class="ms-auto h-4 w-4 text-slate-400" />
                        </a>

                        <a href="{{ route('notificaciones.index') }}"
                           class="flex items-center gap-3 rounded-xl border border-slate-200 px-3 py-2.5 transition hover:border-brand-300 hover:bg-brand-50/50">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-700"><x-icon name="bell" class="h-4 w-4" /></span>
                            <span class="text-sm font-medium text-slate-800">{{ __('Avisos') }}</span>
                            <x-icon name="arrow-right" class="ms-auto h-4 w-4 text-slate-400" />
                        </a>
                    </div>
                </div>
            </div>

            @include('partials.actividad-reciente', ['actividad' => $actividad])
        </div>
    </div>
</x-app-layout>
