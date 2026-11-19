<div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-semibold text-gray-900">{{ __('Actividad reciente') }}</h3>

        <a href="{{ route('notificaciones.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900">
            {{ __('Ver todas') }}
        </a>
    </div>

    <ul class="divide-y divide-gray-100">
        @forelse ($actividad as $aviso)
            <li class="px-6 py-4 text-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="font-medium text-gray-900">{{ $aviso->data['titulo'] ?? __('Aviso') }}</p>
                        <p class="mt-1 text-gray-600">{{ $aviso->data['mensaje'] ?? '' }}</p>
                    </div>

                    <span class="text-xs text-gray-400 whitespace-nowrap">
                        {{ $aviso->created_at->diffForHumans() }}
                    </span>
                </div>
            </li>
        @empty
            <li class="px-6 py-8 text-center text-gray-500">
                {{ __('Todavía no hay actividad registrada.') }}
            </li>
        @endforelse
    </ul>
</div>
