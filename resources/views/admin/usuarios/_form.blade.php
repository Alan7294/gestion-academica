@php($usuario = $usuario ?? null)

<div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
    <div>
        <x-input-label for="name" :value="__('Nombre completo')" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                      :value="old('name', $usuario?->name)" required autofocus />
        <x-input-error class="mt-2" :messages="$errors->get('name')" />
    </div>

    <div>
        <x-input-label for="ci" :value="__('Carnet de identidad')" />
        <x-text-input id="ci" name="ci" type="text" class="mt-1 block w-full"
                      :value="old('ci', $usuario?->ci)" required />
        <x-input-error class="mt-2" :messages="$errors->get('ci')" />
    </div>

    <div>
        <x-input-label for="email" :value="__('Correo electrónico')" />
        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                      :value="old('email', $usuario?->email)" required />
        <x-input-error class="mt-2" :messages="$errors->get('email')" />
    </div>

    <div>
        <x-input-label for="telefono" :value="__('Teléfono')" />
        <x-text-input id="telefono" name="telefono" type="text" class="mt-1 block w-full"
                      :value="old('telefono', $usuario?->telefono)" />
        <x-input-error class="mt-2" :messages="$errors->get('telefono')" />
    </div>

    <div>
        <x-input-label for="rol" :value="__('Rol')" />
        <select id="rol" name="rol" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            @foreach ($roles as $rol)
                <option value="{{ $rol->value }}"
                        @selected(old('rol', $usuario?->rolPrincipal()?->value) === $rol->value)>
                    {{ $rol->etiqueta() }}
                </option>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('rol')" />
    </div>

    <div>
        <x-input-label for="password" :value="__('Contraseña')" />
        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full"
                      :required="$usuario === null" autocomplete="new-password" />
        @if ($usuario)
            <p class="mt-1 text-xs text-gray-500">{{ __('Déjala vacía para conservar la contraseña actual.') }}</p>
        @endif
        <x-input-error class="mt-2" :messages="$errors->get('password')" />
    </div>

    <div>
        <x-input-label for="password_confirmation" :value="__('Confirmar contraseña')" />
        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full"
                      :required="$usuario === null" autocomplete="new-password" />
    </div>
</div>

<label class="mt-6 inline-flex items-center">
    <input type="checkbox" name="activo" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm"
           @checked(old('activo', $usuario?->activo ?? true))>
    <span class="ms-2 text-sm text-gray-600">{{ __('Usuario activo') }}</span>
</label>
