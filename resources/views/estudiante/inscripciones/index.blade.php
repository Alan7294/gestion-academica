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
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card overflow-hidden">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Curso') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Docente') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Horario') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Inscrito') }}</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($inscripciones as $inscripcion)
                            <tr>
                                <td class="px-6 py-4">
                                    <p class="font-medium text-slate-900">{{ $inscripcion->curso->nombre }}</p>
                                    <p class="font-mono text-xs text-slate-500">{{ $inscripcion->curso->codigo }}</p>
                                </td>
                                <td class="px-6 py-4 text-slate-600">{{ $inscripcion->curso->docente?->name ?? __('Por asignar') }}</td>
                                <td class="px-6 py-4 text-slate-600">
                                    @forelse ($inscripcion->curso->horarios as $horario)
                                        <div>{{ $horario->descripcion }}</div>
                                    @empty
                                        {{ __('Sin horario') }}
                                    @endforelse
                                </td>
                                <td class="px-6 py-4 text-slate-600">{{ $inscripcion->inscrito_en->format('d/m/Y') }}</td>
                                <td class="px-6 py-4 text-right">
                                    <form method="POST" action="{{ route('estudiante.inscripciones.destroy', $inscripcion) }}" class="inline"
                                          onsubmit="return confirm('{{ __('¿Retirarte de este curso?') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900">{{ __('Retirarme') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-slate-500">
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
