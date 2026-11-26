<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Facultades') }}
            </h2>

            <a href="{{ route('admin.facultades.create') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                {{ __('Nueva facultad') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card overflow-hidden">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Nombre') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Carreras') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Estado') }}</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($facultades as $facultad)
                            <tr>
                                <td class="px-6 py-4 text-slate-900">{{ $facultad->nombre }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ $facultad->carreras_count }}</td>
                                <td class="px-6 py-4">
                                    @if ($facultad->activo)
                                        <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">{{ __('Activa') }}</span>
                                    @else
                                        <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-600">{{ __('Inactiva') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('admin.facultades.show', $facultad) }}" class="text-brand-600 hover:text-brand-900">
                                        {{ __('Ver') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-slate-500">
                                    {{ __('Todavía no hay facultades registradas.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $facultades->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
