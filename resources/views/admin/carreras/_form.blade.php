@php
    $carrera = $carrera ?? null;
    $facultadSeleccionada = (string) old('facultad_id', $carrera?->facultad_id ?? '');
@endphp

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
        <x-input-label for="facultad_id" :value="__('Facultad')" />
        <select id="facultad_id" name="facultad_id" required
                class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
            <option value="" disabled @selected($facultadSeleccionada === '')>{{ __('Seleccione una facultad') }}</option>
            @foreach ($facultades as $facultad)
                <option value="{{ $facultad->id }}" @selected($facultadSeleccionada === (string) $facultad->id)>
                    {{ $facultad->nombre }}{{ $facultad->activo ? '' : ' ('.__('inactiva').')' }}
                </option>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('facultad_id')" />
    </div>

    <input type="hidden" name="activo" value="0">

    <label class="inline-flex items-center">
        <input type="checkbox" name="activo" value="1" class="rounded border-slate-300 text-brand-600 shadow-sm"
               @checked(old('activo', $carrera?->activo ?? true))>
        <span class="ms-2 text-sm text-slate-600">{{ __('Activa') }}</span>
    </label>
</div>
