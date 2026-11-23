<x-guest-layout>
    <div class="mb-8">
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">{{ __('Crear cuenta') }}</h1>
        <p class="mt-1.5 text-sm text-slate-500">
            {{ __('Regístrate como estudiante o docente para acceder al sistema.') }}
        </p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-5"
          x-data="{ rol: @js(old('rol', '')) }">
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
                <select id="rol" name="rol" required x-model="rol"
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

        <div x-show="rol === 'estudiante'" x-cloak
             class="space-y-5 rounded-xl border border-brand-100 bg-brand-50/40 p-4">
            <div>
                <x-input-label for="carrera_id" :value="__('Carrera a la que te inscribes')" />
                <select id="carrera_id" name="carrera_id" :required="rol === 'estudiante'"
                        class="mt-1 block w-full rounded-lg border-slate-300 text-slate-900 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                    <option value="">{{ __('Selecciona tu carrera') }}</option>
                    @foreach ($carreras as $carrera)
                        <option value="{{ $carrera->id }}" @selected(old('carrera_id') == $carrera->id)>
                            {{ $carrera->nombre }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('carrera_id')" />
            </div>

            <div>
                <x-input-label :value="__('Modalidad')" />
                <div class="mt-1 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    @foreach ($modalidades as $modalidad)
                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-300 bg-white p-3 transition hover:border-brand-300 has-[:checked]:border-brand-500 has-[:checked]:ring-1 has-[:checked]:ring-brand-500">
                            <input type="radio" name="modalidad" value="{{ $modalidad->value }}"
                                   class="mt-0.5 border-slate-300 text-brand-600 focus:ring-brand-500"
                                   :required="rol === 'estudiante'"
                                   @checked(old('modalidad') === $modalidad->value)>
                            <span>
                                <span class="block text-sm font-medium text-slate-900">{{ $modalidad->etiqueta() }}</span>
                                <span class="block text-xs text-slate-500">{{ $modalidad->descripcion() }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                <x-input-error :messages="$errors->get('modalidad')" />
            </div>

            <p class="text-xs text-slate-500">
                {{ __('Estos datos identifican tu inscripción académica y no se pueden cambiar desde aquí.') }}
            </p>
        </div>

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
