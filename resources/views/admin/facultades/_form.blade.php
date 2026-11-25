@php($facultad = $facultad ?? null)

<div class="space-y-6">
    <div>
        <x-input-label for="nombre" :value="__('Nombre de la facultad')" />
        <x-text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full"
                      :value="old('nombre', $facultad?->nombre)" required autofocus
                      placeholder="{{ __('Por ejemplo: Facultad de Ingeniería') }}" />
        <x-input-error class="mt-2" :messages="$errors->get('nombre')" />
    </div>

    <input type="hidden" name="activo" value="0">

    <label class="inline-flex items-center">
        <input type="checkbox" name="activo" value="1" class="rounded border-slate-300 text-brand-600 shadow-sm"
               @checked(old('activo', $facultad?->activo ?? true))>
        <span class="ms-2 text-sm text-slate-600">{{ __('Activa') }}</span>
    </label>
</div>
