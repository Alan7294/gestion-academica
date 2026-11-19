<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-600">{{ __('Panel de administración') }}</p>
                <h2 class="text-xl font-semibold text-slate-900">{{ __('Resumen general') }}</h2>
            </div>

            <a href="{{ route('admin.reportes.index') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                <x-icon name="chart" class="h-4 w-4 text-brand-600" />
                {{ __('Ver reportes') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <x-stat-card :title="__('Usuarios registrados')" :value="$totalUsuarios" icon="users" tone="brand" :hint="__('Cuentas activas e inactivas')" />
                <x-stat-card :title="__('Estudiantes')" :value="$totalEstudiantes" icon="academic-cap" tone="ink" :hint="__('Con rol de estudiante')" />
                <x-stat-card :title="__('Docentes')" :value="$totalDocentes" icon="user" tone="emerald" :hint="__('Con rol de docente')" />
                <x-stat-card :title="__('Cursos')" :value="$totalCursos" icon="book" tone="amber" :hint="__('Registrados en el sistema')" />
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="card overflow-hidden lg:col-span-2">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">{{ __('Actividad reciente') }}</h3>
                            <p class="mt-0.5 text-xs text-slate-500">{{ __('Últimas inscripciones registradas en el sistema.') }}</p>
                        </div>

                        <a href="{{ route('admin.reportes.index') }}" class="link-brand text-sm">
                            {{ __('Reportes') }}
                        </a>
                    </div>

                    <ul class="divide-y divide-slate-100">
                        @forelse ($ultimasInscripciones as $inscripcion)
                            <li class="flex items-start gap-4 px-6 py-4">
                                <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-700 ring-1 ring-brand-100">
                                    <x-icon name="academic-cap" class="h-4 w-4" />
                                </span>

                                <div class="min-w-0 flex-1">
                                    <p class="text-sm text-slate-900">
                                        <span class="font-semibold">{{ $inscripcion->estudiante->name }}</span>
                                        {{ __('se inscribió a') }}
                                        <span class="font-semibold">{{ $inscripcion->curso->nombre }}</span>
                                    </p>
                                    <p class="mt-0.5 truncate text-xs text-slate-500">
                                        {{ $inscripcion->curso->carrera->nombre }}
                                        @if ($inscripcion->curso->docente)
                                            · {{ __('Docente') }}: {{ $inscripcion->curso->docente->name }}
                                        @endif
                                    </p>
                                </div>

                                <span class="whitespace-nowrap text-xs text-slate-400">
                                    {{ $inscripcion->inscrito_en?->diffForHumans() ?? $inscripcion->created_at->diffForHumans() }}
                                </span>
                            </li>
                        @empty
                            <li class="px-6 py-10 text-center text-sm text-slate-500">
                                {{ __('Todavía no hay inscripciones registradas.') }}
                            </li>
                        @endforelse
                    </ul>
                </div>

                <div class="card h-fit p-6">
                    <h3 class="card-title">{{ __('Accesos rápidos') }}</h3>
                    <p class="mt-0.5 text-xs text-slate-500">{{ __('Tareas frecuentes de administración.') }}</p>

                    <div class="mt-5 space-y-2">
                        <a href="{{ route('admin.usuarios.index') }}"
                           class="flex items-center gap-3 rounded-xl border border-slate-200 px-3 py-2.5 transition hover:border-brand-300 hover:bg-brand-50/50">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-700"><x-icon name="users" class="h-4 w-4" /></span>
                            <span class="text-sm font-medium text-slate-800">{{ __('Gestionar usuarios') }}</span>
                            <x-icon name="arrow-right" class="ms-auto h-4 w-4 text-slate-400" />
                        </a>

                        <a href="{{ route('admin.cursos.create') }}"
                           class="flex items-center gap-3 rounded-xl border border-slate-200 px-3 py-2.5 transition hover:border-brand-300 hover:bg-brand-50/50">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-700"><x-icon name="plus" class="h-4 w-4" /></span>
                            <span class="text-sm font-medium text-slate-800">{{ __('Crear un curso') }}</span>
                            <x-icon name="arrow-right" class="ms-auto h-4 w-4 text-slate-400" />
                        </a>

                        <a href="{{ route('admin.periodos.index') }}"
                           class="flex items-center gap-3 rounded-xl border border-slate-200 px-3 py-2.5 transition hover:border-brand-300 hover:bg-brand-50/50">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-700"><x-icon name="calendar" class="h-4 w-4" /></span>
                            <span class="text-sm font-medium text-slate-800">{{ __('Periodos académicos') }}</span>
                            <x-icon name="arrow-right" class="ms-auto h-4 w-4 text-slate-400" />
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
