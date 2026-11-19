<x-guest-layout>
    <div class="mb-8">
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">{{ __('Crear cuenta') }}</h1>
        <p class="mt-1.5 text-sm text-slate-500">
            {{ __('Regístrate como estudiante o docente para acceder al sistema.') }}
        </p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="name" :value="__('Nombre completo')" />
            <x-text-input id="name" class="block w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div>
                <x-input-label for="ci" :value="__('Cédula de identidad')" />
                <x-text-input id="ci" class="block w-full" type="text" name="ci" :value="old('ci')" autocomplete="off" />
                <x-input-error :messages="$errors->get('ci')" />
            </div>

            <div>
                <x-input-label for="rol" :value="__('Rol')" />
                <select id="rol" name="rol" required
                        class="block w-full rounded-lg border-slate-300 text-slate-900 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                    <option value="" disabled @selected(old('rol') === null)>{{ __('Seleccione un rol') }}</option>
                    @foreach ($roles as $rol)
                        <option value="{{ $rol->value }}" @selected(old('rol') === $rol->value)>
                            {{ $rol->etiqueta() }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('rol')" />
            </div>
        </div>

        <p class="-mt-2 text-xs text-slate-500">
            {{ __('El rol de administrador solo puede ser asignado por otro administrador.') }}
        </p>

        <div>
            <x-input-label for="email" :value="__('Correo electrónico')" />
            <x-text-input id="email" class="block w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Contraseña')" />
            <x-text-input id="password" class="block w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Confirmar contraseña')" />
            <x-text-input id="password_confirmation" class="block w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <x-primary-button class="w-full">
            {{ __('Crear cuenta') }}
        </x-primary-button>
    </form>

    <p class="mt-8 text-center text-sm text-slate-500">
        {{ __('¿Ya tienes una cuenta?') }}
        <a href="{{ route('login') }}" class="font-semibold text-brand-700 transition hover:text-brand-900">
            {{ __('Inicia sesión') }}
        </a>
    </p>
</x-guest-layout>
