<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Periodos académicos') }}
            </h2>

            <a href="{{ route('admin.periodos.create') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                {{ __('Nuevo periodo') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card overflow-hidden">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Periodo') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Inicio') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Fin') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Cursos') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Estado') }}</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($periodos as $periodo)
                            <tr>
                                <td class="px-6 py-4 text-slate-900">{{ $periodo->nombre }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ $periodo->fecha_inicio->format('d/m/Y') }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ $periodo->fecha_fin->format('d/m/Y') }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ $periodo->cursos_count }}</td>
                                <td class="px-6 py-4">
                                    @if ($periodo->activo)
                                        <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-brand-100 text-brand-800">{{ __('Vigente') }}</span>
                                    @else
                                        <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-600">{{ __('Cerrado') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('admin.periodos.show', $periodo) }}" class="text-brand-600 hover:text-brand-900">
                                        {{ __('Ver') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-slate-500">
                                    {{ __('Todavía no hay periodos registrados.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $periodos->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
