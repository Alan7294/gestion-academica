<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-600">{{ __('Docencia') }}</p>
            <h2 class="text-xl font-semibold text-slate-900">{{ __('Reportes del docente') }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            <p class="text-sm text-slate-600">
                {{ __('Descarga en PDF o Excel la información de tus materias, tus alumnos y sus calificaciones.') }}
            </p>

            <div class="card flex flex-col gap-5 p-6 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-4">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100">
                        <x-icon name="book" class="h-6 w-6" />
                    </span>
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">{{ __('Listado de materias del docente') }}</h3>
                        <p class="mt-1 text-sm text-slate-600">{{ __('Materias asignadas, carga académica y horario.') }}</p>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <a href="{{ route('docente.reportes.materias.preview') }}"
                       class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                        <x-icon name="eye" class="h-4 w-4" /> {{ __('Ver') }}
                    </a>
                    <a href="{{ route('docente.reportes.materias', ['formato' => 'pdf']) }}"
                       class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700">
                        <x-icon name="download" class="h-4 w-4" /> PDF
                    </a>
                    <a href="{{ route('docente.reportes.materias', ['formato' => 'excel']) }}"
                       class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                        <x-icon name="download" class="h-4 w-4" /> Excel
                    </a>
                </div>
            </div>

            @forelse ($cursos as $curso)
                <div class="card overflow-hidden">
                    <div class="flex flex-col gap-4 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-slate-900">
                                {{ $curso->nombre }}
                                <span class="ms-1 font-mono text-xs font-normal text-slate-500">{{ $curso->codigo }}</span>
                            </h3>
                            <p class="mt-0.5 text-xs text-slate-500">
                                {{ $curso->carrera->nombre }} · {{ $curso->periodo->nombre }} ·
                                {{ $curso->alumnos_inscritos_count }} {{ __('inscritos') }}
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-medium text-slate-500">{{ __('Notas por curso') }}</span>
                                <a href="{{ route('docente.reportes.notas-curso.preview', ['curso' => $curso]) }}"
                                   class="rounded-md bg-brand-50 px-2 py-1 text-xs font-semibold text-brand-700 transition hover:bg-brand-100">{{ __('Ver') }}</a>
                                <a href="{{ route('docente.reportes.notas-curso', ['curso' => $curso, 'formato' => 'pdf']) }}"
                                   class="rounded-md bg-rose-50 px-2 py-1 text-xs font-semibold text-rose-700 transition hover:bg-rose-100">PDF</a>
                                <a href="{{ route('docente.reportes.notas-curso', ['curso' => $curso, 'formato' => 'excel']) }}"
                                   class="rounded-md bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100">Excel</a>
                            </div>

                            <div class="flex items-center gap-2">
                                <span class="text-xs font-medium text-slate-500">{{ __('Alumnos') }}</span>
                                <a href="{{ route('docente.reportes.alumnos.preview', ['curso' => $curso]) }}"
                                   class="rounded-md bg-brand-50 px-2 py-1 text-xs font-semibold text-brand-700 transition hover:bg-brand-100">{{ __('Ver') }}</a>
                                <a href="{{ route('docente.reportes.alumnos', ['curso' => $curso, 'formato' => 'pdf']) }}"
                                   class="rounded-md bg-rose-50 px-2 py-1 text-xs font-semibold text-rose-700 transition hover:bg-rose-100">PDF</a>
                                <a href="{{ route('docente.reportes.alumnos', ['curso' => $curso, 'formato' => 'excel']) }}"
                                   class="rounded-md bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100">Excel</a>
                            </div>
                        </div>
                    </div>

                    @if ($curso->alumnosInscritos->isEmpty())
                        <p class="px-6 py-8 text-sm text-slate-500">{{ __('Este curso todavía no tiene alumnos inscritos.') }}</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-100 text-sm">
                                <thead class="bg-slate-50/80">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('Estudiante') }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('CI') }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('Nota final') }}</th>
                                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('Reporte por alumno') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($curso->alumnosInscritos as $inscripcion)
                                        <tr class="transition hover:bg-slate-50/60">
                                            <td class="px-6 py-3 font-medium text-slate-900">{{ $inscripcion->estudiante->name }}</td>
                                            <td class="px-6 py-3 text-slate-600">{{ $inscripcion->estudiante->ci ?? '—' }}</td>
                                            <td class="px-6 py-3 text-slate-600">{{ $inscripcion->nota_final ?? '—' }}</td>
                                            <td class="space-x-2 px-6 py-3 text-right">
                                                <a href="{{ route('docente.reportes.notas-alumno.preview', ['curso' => $curso, 'inscripcion' => $inscripcion]) }}"
                                                   class="rounded-md bg-brand-50 px-2 py-1 text-xs font-semibold text-brand-700 transition hover:bg-brand-100">{{ __('Ver') }}</a>
                                                <a href="{{ route('docente.reportes.notas-alumno', ['curso' => $curso, 'inscripcion' => $inscripcion, 'formato' => 'pdf']) }}"
                                                   class="rounded-md bg-rose-50 px-2 py-1 text-xs font-semibold text-rose-700 transition hover:bg-rose-100">PDF</a>
                                                <a href="{{ route('docente.reportes.notas-alumno', ['curso' => $curso, 'inscripcion' => $inscripcion, 'formato' => 'excel']) }}"
                                                   class="rounded-md bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100">Excel</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            @empty
                <div class="card px-6 py-14 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                        <x-icon name="book" class="h-6 w-6" />
                    </span>
                    <p class="mt-3 text-sm font-medium text-slate-700">{{ __('Todavía no tienes cursos asignados.') }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ __('Cuando tengas materias asignadas podrás descargar sus reportes.') }}</p>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
