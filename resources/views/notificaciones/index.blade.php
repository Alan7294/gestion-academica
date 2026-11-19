<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Notificaciones') }}
            </h2>

            @if ($notificaciones->contains(fn ($n) => $n->read_at === null))
                <form method="POST" action="{{ route('notificaciones.leer-todas') }}">
                    @csrf
                    <button type="submit" class="text-sm text-indigo-600 hover:text-indigo-900">
                        {{ __('Marcar todas como leídas') }}
                    </button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg divide-y divide-gray-100">
                @forelse ($notificaciones as $notificacion)
                    @php($leida = $notificacion->read_at !== null)

                    <div class="p-5 flex items-start justify-between gap-4 {{ $leida ? 'bg-white' : 'bg-indigo-50/40' }}">
                        <div>
                            <p class="font-medium text-gray-900">
                                {{ $notificacion->data['titulo'] ?? __('Aviso') }}
                                @unless ($leida)
                                    <span class="ms-2 inline-flex px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800">
                                        {{ __('Nueva') }}
                                    </span>
                                @endunless
                            </p>
                            <p class="mt-1 text-sm text-gray-600">{{ $notificacion->data['mensaje'] ?? '' }}</p>
                            <p class="mt-1 text-xs text-gray-400">{{ $notificacion->created_at->diffForHumans() }}</p>
                        </div>

                        <form method="POST" action="{{ route('notificaciones.leer', $notificacion) }}">
                            @csrf
                            <button type="submit" class="text-sm text-indigo-600 hover:text-indigo-900 whitespace-nowrap">
                                {{ $leida ? __('Ver') : __('Abrir') }}
                            </button>
                        </form>
                    </div>
                @empty
                    <div class="p-8 text-center text-gray-500">
                        {{ __('No tienes notificaciones.') }}
                    </div>
                @endforelse
            </div>

            <div class="mt-4">
                {{ $notificaciones->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
