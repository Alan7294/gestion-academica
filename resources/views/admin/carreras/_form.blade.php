@php($carrera = $carrera ?? null)

<div class="space-y-6">
    <div>
        <x-input-label for="codigo" :value="__('Código')" />
        <x-text-input id="codigo" name="codigo" type="text" class="mt-1 block w-full"
                      :value="old('codigo', $carrera?->codigo)" required autofocus />
        <x-input-error class="mt-2" :messages="$errors->get('codigo')" />
    </div>

    <div>
        <x-input-label for="nombre" :value="__('Nombre')" />
        <x-text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full"
                      :value="old('nombre', $carrera?->nombre)" required />
        <x-input-error class="mt-2" :messages="$errors->get('nombre')" />
    </div>

    <div>
        <x-input-label for="facultad" :value="__('Facultad')" />
        <x-text-input id="facultad" name="facultad" type="text" class="mt-1 block w-full"
                      :value="old('facultad', $carrera?->facultad)" />
        <x-input-error class="mt-2" :messages="$errors->get('facultad')" />
    </div>

    <label class="inline-flex items-center">
        <input type="checkbox" name="activo" value="1" class="rounded border-slate-300 text-brand-600 shadow-sm"
               @checked(old('activo', $carrera?->activo ?? true))>
        <span class="ms-2 text-sm text-slate-600">{{ __('Activa') }}</span>
    </label>
</div>
