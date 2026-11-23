<x-guest-layout>
    <div class="mb-4 text-sm text-slate-600">
        {{ __('Gracias por acceder. Antes de continuar, confirma tu correo electrónico haciendo clic en el enlace que te enviamos. Si no lo recibiste, podemos enviártelo de nuevo.') }}
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 text-sm font-medium text-green-600">
            {{ __('Se envió un nuevo enlace de verificación a tu dirección de correo electrónico.') }}
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>
                    {{ __('Reenviar correo de verificación') }}
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="rounded-md text-sm text-slate-600 underline hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                {{ __('Cerrar sesión') }}
            </button>
        </form>
    </div>
</x-guest-layout>
