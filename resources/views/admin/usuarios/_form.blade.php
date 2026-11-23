@php
    $usuario = $usuario ?? null;
    $rolActual = old('rol', $usuario?->rolPrincipal()?->value ?? \App\Enums\Rol::Estudiante->value);
@endphp

<div x-data="{ rol: @js($rolActual) }">
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
            <select id="rol" name="rol" required x-model="rol" class="mt-1 block w-full border-slate-300 rounded-md shadow-sm">
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
                <p class="mt-1 text-xs text-slate-500">{{ __('Déjala vacía para conservar la contraseña actual.') }}</p>
            @endif
            <x-input-error class="mt-2" :messages="$errors->get('password')" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Confirmar contraseña')" />
            <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full"
                          :required="$usuario === null" autocomplete="new-password" />
        </div>

        {{-- La carrera y la modalidad solo aplican a estudiantes. --}}
        <div x-show="rol === 'estudiante'" x-cloak
             class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-6 rounded-xl border border-brand-100 bg-brand-50/40 p-4">
            <div>
                <x-input-label for="carrera_id" :value="__('Carrera')" />
                <select id="carrera_id" name="carrera_id" class="mt-1 block w-full border-slate-300 rounded-md shadow-sm">
                    <option value="">{{ __('Selecciona una carrera') }}</option>
                    @foreach ($carreras as $carrera)
                        <option value="{{ $carrera->id }}" @selected(old('carrera_id', $usuario?->carrera_id) == $carrera->id)>
                            {{ $carrera->nombre }}
                        </option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('carrera_id')" />
            </div>

            <div>
                <x-input-label for="modalidad" :value="__('Modalidad')" />
                <select id="modalidad" name="modalidad" class="mt-1 block w-full border-slate-300 rounded-md shadow-sm">
                    <option value="">{{ __('Selecciona una modalidad') }}</option>
                    @foreach ($modalidades as $modalidad)
                        <option value="{{ $modalidad->value }}"
                                @selected(old('modalidad', $usuario?->modalidad?->value) === $modalidad->value)>
                            {{ $modalidad->etiqueta() }}
                        </option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('modalidad')" />
            </div>
        </div>
    </div>

    <label class="mt-6 inline-flex items-center">
        <input type="checkbox" name="activo" value="1" class="rounded border-slate-300 text-brand-600 shadow-sm"
               @checked(old('activo', $usuario?->activo ?? true))>
        <span class="ms-2 text-sm text-slate-600">{{ __('Usuario activo') }}</span>
    </label>
</div>
