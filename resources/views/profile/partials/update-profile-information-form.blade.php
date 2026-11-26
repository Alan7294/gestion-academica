<section>
    <header>
        <h2 class="text-lg font-medium text-slate-900">
            {{ __('Información del perfil') }}
        </h2>

        <p class="mt-1 text-sm text-slate-600">
            {{ __('Actualiza tus datos personales y tu correo electrónico.') }}
        </p>
    </header>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="nombres" :value="__('Nombres')" />
            <x-text-input id="nombres" name="nombres" type="text" class="mt-1 block w-full"
                          :value="old('nombres', $user->nombres ?? $user->name)" required autofocus autocomplete="given-name" />
            <x-input-error class="mt-2" :messages="$errors->get('nombres')" />
        </div>

        <div>
            <x-input-label for="primer_apellido" :value="__('Primer apellido')" />
            <x-text-input id="primer_apellido" name="primer_apellido" type="text" class="mt-1 block w-full"
                          :value="old('primer_apellido', $user->primer_apellido)" required autocomplete="family-name" />
            <x-input-error class="mt-2" :messages="$errors->get('primer_apellido')" />
        </div>

        <div>
            <x-input-label for="segundo_apellido" :value="__('Segundo apellido (opcional)')" />
            <x-text-input id="segundo_apellido" name="segundo_apellido" type="text" class="mt-1 block w-full"
                          :value="old('segundo_apellido', $user->segundo_apellido)" />
            <x-input-error class="mt-2" :messages="$errors->get('segundo_apellido')" />
        </div>

        <div>
            <x-input-label for="ci" :value="__('Cédula de identidad')" />
            <x-text-input id="ci" name="ci" type="text" class="mt-1 block w-full" :value="old('ci', $user->ci)" autocomplete="off" />
            <x-input-error class="mt-2" :messages="$errors->get('ci')" />
        </div>

        <div>
            <x-input-label for="telefono" :value="__('Teléfono')" />
            <x-text-input id="telefono" name="telefono" type="text" class="mt-1 block w-full" :value="old('telefono', $user->telefono)" autocomplete="tel" />
            <x-input-error class="mt-2" :messages="$errors->get('telefono')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Correo electrónico')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Guardar') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-slate-600"
                >{{ __('Guardado.') }}</p>
            @endif
        </div>
    </form>
</section>
