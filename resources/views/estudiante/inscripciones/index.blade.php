<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Mis cursos') }}
            </h2>

            <a href="{{ route('estudiante.cursos.index') }}" class="text-sm text-brand-600 hover:text-brand-900">
                {{ __('Ver oferta académica') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @forelse ($convalidaciones as $convalidacion)
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div>
                            <h3 class="font-semibold text-slate-900">
                                {{ $convalidacion->materia->codigo }} - {{ $convalidacion->materia->nombre }}
                            </h3>
                            <p class="mt-1 text-xs text-amber-800">
                                {{ __('Convalidada') }} · {{ $convalidacion->origen() }}
                            </p>
                            <p class="text-xs text-slate-500">{{ $convalidacion->carreraDestino?->nombre ?? '—' }}</p>
                        </div>

                        <div class="text-right">
                            <p class="text-xs text-slate-500">{{ __('Nota de origen') }}</p>
                            <p class="text-2xl font-semibold text-green-700">{{ number_format($convalidacion->nota, 2) }}</p>
                        </div>
                    </div>
                </div>
            @empty
            @endforelse

            <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card overflow-hidden">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Curso') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Docente') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Horario') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Inscrito') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Promedio') }}</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($inscripciones as $inscripcion)
                            <tr>
                                <td class="px-6 py-4">
                                    <p class="font-medium text-slate-900">{{ $inscripcion->curso->nombre }}</p>
                                    <p class="font-mono text-xs text-slate-500">{{ $inscripcion->curso->materia?->codigo ?? '—' }}</p>
                                </td>
                                <td class="px-6 py-4 text-slate-600">{{ $inscripcion->curso->docente?->name ?? __('Por asignar') }}</td>
                                <td class="px-6 py-4 text-slate-600">
                                    @php($horarios = $inscripcion->curso->horarios)
                                    @php($inicio = $horarios->pluck('fecha_inicio')->filter()->sort()->first())
                                    @php($fin = $horarios->pluck('fecha_fin')->filter()->sort()->last())
                                    @php($rangos = $horarios->pluck('rango_texto')->unique()->implode(', '))

                                    @if ($horarios->isNotEmpty())
                                        <div>{{ __('Fecha de Inicio') }}: {{ $inicio?->format('d/m/Y') ?? '—' }}</div>
                                        <div>{{ __('Fecha de Fin') }}: {{ $fin?->format('d/m/Y') ?? '—' }}</div>
                                        <div>{{ __('Horario') }}: {{ $rangos ?: '—' }}</div>
                                    @else
                                        {{ __('Sin horario') }}
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-slate-600">{{ $inscripcion->inscrito_en->format('d/m/Y') }}</td>
                                <td class="px-6 py-4 text-slate-600">
                                    <span class="font-medium {{ $inscripcion->estaAprobada() ? 'text-green-700' : 'text-slate-900' }}">
                                        {{ $inscripcion->nota_final ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="inline-flex items-center gap-3">
                                        <a href="{{ route('estudiante.inscripciones.show', $inscripcion) }}" class="text-brand-600 hover:text-brand-900">
                                            {{ __('Ver calificaciones') }}
                                        </a>
                                        <form method="POST" action="{{ route('estudiante.inscripciones.destroy', $inscripcion) }}" class="inline"
                                              onsubmit="return confirm('{{ __('¿Retirarte de este curso?') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900">{{ __('Retirarme') }}</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-slate-500">
                                    {{ __('No estás inscrito en ningún curso todavía.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $inscripciones->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
