<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-600">{{ __('Usuario') }}</p>
                <h2 class="text-xl font-semibold text-slate-900">{{ $usuario->name }}</h2>
                <p class="mt-0.5 text-sm text-slate-500">{{ $usuario->email }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('admin.usuarios.edit', $usuario) }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                    <x-icon name="pencil" class="h-4 w-4" />
                    {{ __('Editar') }}
                </a>

                @unless ($usuario->is(auth()->user()))
                    <form method="POST" action="{{ route('admin.usuarios.destroy', $usuario) }}" class="inline"
                          onsubmit="return confirm('{{ __('¿Eliminar este usuario?') }}')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="rounded-lg border border-red-200 px-4 py-2.5 text-sm font-semibold text-red-700 transition hover:bg-red-50">
                            {{ __('Eliminar') }}
                        </button>
                    </form>
                @endunless

                <a href="{{ route('admin.usuarios.index') }}" class="text-sm text-slate-600 hover:text-slate-900">
                    {{ __('Volver') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card p-5">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Carnet de identidad') }}</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900">{{ $usuario->ci ?? '—' }}</p>
                </div>

                <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card p-5">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Teléfono') }}</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900">{{ $usuario->telefono ?? '—' }}</p>
                </div>

                <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card p-5">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Rol') }}</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900">
                        {{ $usuario->rolPrincipal()?->etiqueta() ?? __('Sin rol') }}
                    </p>
                </div>

                <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card p-5">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Estado') }}</p>
                    <p class="mt-1 text-lg font-semibold {{ $usuario->activo ? 'text-green-700' : 'text-slate-500' }}">
                        {{ $usuario->activo ? __('Activo') : __('Inactivo') }}
                    </p>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card overflow-hidden">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h3 class="font-semibold text-slate-900">{{ __('Matrículas académicas') }}</h3>
                </div>

                @if ($usuario->matriculas->isEmpty())
                    <p class="px-6 py-5 text-sm text-slate-500">{{ __('El usuario no tiene matrículas registradas.') }}</p>
                @else
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Carrera') }}</th>
                                <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Modalidad') }}</th>
                                <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Estado') }}</th>
                                <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Principal') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($usuario->matriculas as $matricula)
                                <tr>
                                    <td class="px-6 py-3 text-slate-900">{{ $matricula->carrera->nombre }}</td>
                                    <td class="px-6 py-3 text-slate-600">{{ $matricula->modalidad?->etiqueta() ?? '—' }}</td>
                                    <td class="px-6 py-3 text-slate-600">{{ $matricula->estado->etiqueta() }}</td>
                                    <td class="px-6 py-3 text-slate-600">{{ $matricula->es_principal ? __('Sí') : __('No') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            @if ($usuario->esDocente())
                <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card overflow-hidden">
                    <div class="border-b border-slate-100 px-6 py-4">
                        <h3 class="font-semibold text-slate-900">{{ __('Certificados del docente') }}</h3>
                    </div>

                    @if ($usuario->certificadosDocentes->isEmpty())
                        <p class="px-6 py-5 text-sm text-slate-500">{{ __('El docente no tiene certificados registrados.') }}</p>
                    @else
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Título') }}</th>
                                    <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Institution') }}</th>
                                    <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Codigo') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($usuario->certificadosDocentes as $certificado)
                                    <tr>
                                        <td class="px-6 py-3 text-slate-900">{{ $certificado->titulo }}</td>
                                        <td class="px-6 py-3 text-slate-600">{{ $certificado->institucion }}</td>
                                        <td class="px-6 py-3 text-slate-600">{{ $certificado->codigo }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
