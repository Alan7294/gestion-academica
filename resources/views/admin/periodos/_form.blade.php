@php($periodo = $periodo ?? null)

<div class="space-y-6">
    <div>
        <x-input-label for="nombre" :value="__('Nombre del periodo')" />
        <x-text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full"
                      :value="old('nombre', $periodo?->nombre)" placeholder="2026-II" required autofocus />
        <x-input-error class="mt-2" :messages="$errors->get('nombre')" />
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div>
            <x-input-label for="fecha_inicio" :value="__('Fecha de inicio')" />
            <x-text-input id="fecha_inicio" name="fecha_inicio" type="date" class="mt-1 block w-full"
                          :value="old('fecha_inicio', $periodo?->fecha_inicio?->format('Y-m-d'))" required />
            <x-input-error class="mt-2" :messages="$errors->get('fecha_inicio')" />
        </div>

        <div>
            <x-input-label for="fecha_fin" :value="__('Fecha de fin')" />
            <x-text-input id="fecha_fin" name="fecha_fin" type="date" class="mt-1 block w-full"
                          :value="old('fecha_fin', $periodo?->fecha_fin?->format('Y-m-d'))" required />
            <x-input-error class="mt-2" :messages="$errors->get('fecha_fin')" />
        </div>
    </div>

    <label class="inline-flex items-center">
        <input type="checkbox" name="activo" value="1" class="rounded border-slate-300 text-brand-600 shadow-sm"
               @checked(old('activo', $periodo?->activo ?? false))>
        <span class="ms-2 text-sm text-slate-600">{{ __('Marcar como periodo vigente') }}</span>
    </label>
</div>
