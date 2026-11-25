@php($materia = $materia ?? null)

<div class="space-y-6">
    <div>
        <x-input-label for="carrera_id" :value="__('Carrera')" />
        <select id="carrera_id" name="carrera_id" required
                class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
            <option value="" disabled @selected(! old('carrera_id', $materia?->carrera_id))>{{ __('Seleccione una carrera') }}</option>
            @foreach ($carreras as $carrera)
                <option value="{{ $carrera->id }}" @selected(old('carrera_id', $materia?->carrera_id) == $carrera->id)>
                    {{ $carrera->nombre }}
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-slate-500">{{ __('La materia podrá usarse en los planes y cursos de esta carrera.') }}</p>
        <x-input-error class="mt-2" :messages="$errors->get('carrera_id')" />
    </div>

    <div>
        <x-input-label for="codigo" :value="__('Código')" />
        <x-text-input id="codigo" name="codigo" type="text" class="mt-1 block w-full"
                      :value="old('codigo', $materia?->codigo)" required autofocus maxlength="20"
                      placeholder="{{ __('Por ejemplo: MAT-101') }}" />
        <x-input-error class="mt-2" :messages="$errors->get('codigo')" />
    </div>

    <div>
        <x-input-label for="nombre" :value="__('Nombre')" />
        <x-text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full"
                      :value="old('nombre', $materia?->nombre)" required
                      placeholder="{{ __('Por ejemplo: Cálculo I') }}" />
        <x-input-error class="mt-2" :messages="$errors->get('nombre')" />
    </div>
</div>
