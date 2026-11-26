@php($materia = $materia ?? null)

<div class="space-y-6">
    <p class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
        {{ __('La materia se registra en el catálogo general. Se incluye en un plan de estudio desde el formulario del plan, donde se define su semestre.') }}
    </p>

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
