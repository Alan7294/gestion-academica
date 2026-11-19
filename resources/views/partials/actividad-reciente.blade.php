<div class="card overflow-hidden">
    <div class="card-header">
        <h3 class="card-title">{{ __('Actividad reciente') }}</h3>

        <a href="{{ route('notificaciones.index') }}" class="link-brand text-sm">
            {{ __('Ver todas') }}
        </a>
    </div>

    <ul class="divide-y divide-slate-100">
        @forelse ($actividad as $aviso)
            <li class="flex items-start gap-4 px-6 py-4">
                <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-700 ring-1 ring-brand-100">
                    <x-icon name="bell" class="h-4 w-4" />
                </span>

                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-slate-900">{{ $aviso->data['titulo'] ?? __('Aviso') }}</p>
                    <p class="mt-0.5 text-sm text-slate-600">{{ $aviso->data['mensaje'] ?? '' }}</p>
                </div>

                <span class="whitespace-nowrap text-xs text-slate-400">
                    {{ $aviso->created_at->diffForHumans() }}
                </span>
            </li>
        @empty
            <li class="px-6 py-10 text-center text-sm text-slate-500">
                {{ __('Todavía no hay actividad registrada.') }}
            </li>
        @endforelse
    </ul>
</div>
