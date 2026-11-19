<x-guest-layout>
    <div class="mb-8">
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">{{ __('Iniciar sesión') }}</h1>
        <p class="mt-1.5 text-sm text-slate-500">
            {{ __('Ingresa con el correo y la contraseña de tu cuenta institucional.') }}
        </p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Correo electrónico')" />
            <x-text-input id="email" class="block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="nombre@academica.test" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div>
            <div class="flex items-center justify-between">
                <x-input-label for="password" :value="__('Contraseña')" />

                @if (Route::has('password.request'))
                    <a class="text-xs font-medium text-brand-700 transition hover:text-brand-900" href="{{ route('password.request') }}">
                        {{ __('¿Olvidaste tu contraseña?') }}
                    </a>
                @endif
            </div>

            <x-text-input id="password" class="block w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password"
                            placeholder="••••••••" />

            <x-input-error :messages="$errors->get('password')" />
        </div>

        <label for="remember_me" class="inline-flex cursor-pointer items-center gap-2">
            <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-brand-600 shadow-sm focus:ring-brand-500" name="remember">
            <span class="text-sm text-slate-600">{{ __('Recordarme') }}</span>
        </label>

        <x-primary-button class="w-full">
            {{ __('Entrar') }}
            <x-icon name="arrow-right" class="h-4 w-4" />
        </x-primary-button>
    </form>

    @if (Route::has('register'))
        <p class="mt-8 text-center text-sm text-slate-500">
            {{ __('¿No tienes una cuenta?') }}
            <a href="{{ route('register') }}" class="font-semibold text-brand-700 transition hover:text-brand-900">
                {{ __('Regístrate') }}
            </a>
        </p>
    @endif
</x-guest-layout>
