<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Mis cursos asignados') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card overflow-hidden">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Curso') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Carrera') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Periodo') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Horario') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Inscritos') }}</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($cursos as $curso)
                            <tr>
                                <td class="px-6 py-4">
                                    <p class="font-medium text-slate-900">{{ $curso->nombre }}</p>
                                    <p class="font-mono text-xs text-slate-500">{{ $curso->materia?->codigo ?? '—' }}</p>
                                </td>
                                <td class="px-6 py-4 text-slate-600">{{ $curso->carrera->nombre }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ $curso->periodo->nombre }}</td>
                                <td class="px-6 py-4 text-slate-600">
                                    @php($inicio = $curso->horarios->pluck('fecha_inicio')->filter()->sort()->first())
                                    @php($fin = $curso->horarios->pluck('fecha_fin')->filter()->sort()->last())
                                    @php($rangos = $curso->horarios->pluck('rango_texto')->unique()->implode(', '))

                                    @if ($curso->horarios->isNotEmpty())
                                        <div>{{ __('Fecha de Inicio') }}: {{ $inicio?->format('d/m/Y') ?? '—' }}</div>
                                        <div>{{ __('Fecha de Fin') }}: {{ $fin?->format('d/m/Y') ?? '—' }}</div>
                                        <div>{{ __('Horario') }}: {{ $rangos ?: '—' }}</div>
                                    @else
                                        {{ __('Sin horario') }}
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-slate-600">{{ $curso->alumnos_inscritos_count }}</td>
                                <td class="px-6 py-4 text-right space-x-3">
                                    <a href="{{ route('docente.cursos.calificaciones', $curso) }}" class="text-brand-600 hover:text-brand-900">
                                        {{ __('Calificaciones') }}
                                    </a>

                                    <a href="{{ route('docente.cursos.show', $curso) }}" class="text-brand-600 hover:text-brand-900">
                                        {{ __('Ver detalle') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-slate-500">
                                    {{ __('No tienes cursos asignados en este momento.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $cursos->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
