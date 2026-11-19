<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-600">{{ __('Bandeja de entrada') }}</p>
                <h2 class="text-xl font-semibold text-slate-900">{{ __('Notificaciones') }}</h2>
            </div>

            @if ($notificaciones->contains(fn ($n) => $n->read_at === null))
                <form method="POST" action="{{ route('notificaciones.leer-todas') }}">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-2 text-sm font-semibold text-brand-700 transition hover:text-brand-900">
                        <x-icon name="check" class="h-4 w-4" />
                        {{ __('Marcar todas como leídas') }}
                    </button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <div class="card divide-y divide-slate-100 overflow-hidden">
                @forelse ($notificaciones as $notificacion)
                    @php($leida = $notificacion->read_at !== null)

                    <div class="flex items-start justify-between gap-4 p-5 {{ $leida ? 'bg-white' : 'bg-brand-50/40' }}">
                        <div class="flex min-w-0 items-start gap-4">
                            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $leida ? 'bg-slate-100 text-slate-500' : 'bg-brand-100 text-brand-700' }}">
                                <x-icon name="bell" class="h-4 w-4" />
                            </span>

                            <div class="min-w-0">
                                <p class="font-medium text-slate-900">
                                    {{ $notificacion->data['titulo'] ?? __('Aviso') }}
                                    @unless ($leida)
                                        <span class="ms-2 inline-flex rounded-full bg-brand-100 px-2 py-0.5 text-[11px] font-semibold text-brand-800">
                                            {{ __('Nueva') }}
                                        </span>
                                    @endunless
                                </p>
                                <p class="mt-1 text-sm text-slate-600">{{ $notificacion->data['mensaje'] ?? '' }}</p>
                                <p class="mt-1 text-xs text-slate-400">{{ $notificacion->created_at->diffForHumans() }}</p>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('notificaciones.leer', $notificacion) }}">
                            @csrf
                            <button type="submit" class="whitespace-nowrap text-sm font-semibold text-brand-700 transition hover:text-brand-900">
                                {{ $leida ? __('Ver') : __('Abrir') }}
                            </button>
                        </form>
                    </div>
                @empty
                    <div class="px-6 py-14 text-center">
                        <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                            <x-icon name="bell" class="h-6 w-6" />
                        </span>
                        <p class="mt-3 text-sm font-medium text-slate-700">{{ __('No tienes notificaciones.') }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ __('Cuando haya novedades en tus cursos aparecerán aquí.') }}</p>
                    </div>
                @endforelse
            </div>

            <div class="mt-4">
                {{ $notificaciones->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
