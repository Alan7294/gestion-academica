<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-600">{{ __('Facultad') }}</p>
                <h2 class="text-xl font-semibold text-slate-900">{{ $facultad->nombre }}</h2>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('admin.facultades.edit', $facultad) }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                    <x-icon name="pencil" class="h-4 w-4" />
                    {{ __('Editar') }}
                </a>
                <form method="POST" action="{{ route('admin.facultades.destroy', $facultad) }}" class="inline"
                      onsubmit="return confirm('{{ __('¿Eliminar esta facultad?') }}')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="rounded-lg border border-red-200 px-4 py-2.5 text-sm font-semibold text-red-700 transition hover:bg-red-50">
                        {{ __('Eliminar') }}
                    </button>
                </form>
                <a href="{{ route('admin.facultades.index') }}" class="text-sm text-slate-600 hover:text-slate-900">
                    {{ __('Volver') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card p-5">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Estado') }}</p>
                    <p class="mt-1 text-lg font-semibold {{ $facultad->activo ? 'text-green-700' : 'text-slate-500' }}">
                        {{ $facultad->activo ? __('Activa') : __('Inactiva') }}
                    </p>
                </div>

                <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card p-5">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Carreras') }}</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900">{{ $facultad->carreras->count() }}</p>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card overflow-hidden">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h3 class="font-semibold text-slate-900">{{ __('Carreras de la facultad') }}</h3>
                </div>

                @if ($facultad->carreras->isEmpty())
                    <p class="px-6 py-5 text-sm text-slate-500">{{ __('Esta facultad todavía no tiene carreras registradas.') }}</p>
                @else
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Código') }}</th>
                                <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Nombre') }}</th>
                                <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Cursos') }}</th>
                                <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Planes') }}</th>
                                <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Estado') }}</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($facultad->carreras as $carrera)
                                <tr>
                                    <td class="px-6 py-3 font-mono text-slate-700">{{ $carrera->codigo }}</td>
                                    <td class="px-6 py-3 text-slate-900">{{ $carrera->nombre }}</td>
                                    <td class="px-6 py-3 text-slate-600">{{ $carrera->cursos_count }}</td>
                                    <td class="px-6 py-3 text-slate-600">{{ $carrera->planes_estudio_count }}</td>
                                    <td class="px-6 py-3 text-slate-600">{{ $carrera->activo ? __('Activa') : __('Inactiva') }}</td>
                                    <td class="px-6 py-3 text-right">
                                        <a href="{{ route('admin.carreras.show', $carrera) }}" class="text-brand-600 hover:text-brand-900">
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
