@php
    $plan = $plan ?? null;

    // Se conservan los datos enviados si la validación falla.
    $materiasIniciales = old('materias');

    if ($materiasIniciales === null) {
        $materiasIniciales = $plan
            ? $plan->materias->map(fn ($materia) => [
                'id' => $materia->id,
                'codigo' => $materia->codigo,
                'nombre' => $materia->nombre,
                'semestre' => $materia->semestre,
                'creditos' => $materia->creditos,
                'requisitos' => $materia->requisitos->pluck('codigo')->values()->all(),
            ])->values()->all()
            : [];
    }

    $materiasIniciales = collect($materiasIniciales)
        ->map(fn (array $materia) => array_merge(['id' => null, 'requisitos' => []], $materia))
        ->values()
        ->all();

    if (empty($materiasIniciales)) {
        $materiasIniciales = [[
            'id' => null,
            'codigo' => '',
            'nombre' => '',
            'semestre' => 1,
            'creditos' => 0,
            'requisitos' => [],
        ]];
    }

    $duracionInicial = (int) old('semestres', $plan?->semestres ?? 2);

    $erroresMaterias = collect($errors->getBag('default')->keys())
        ->filter(fn (string $clave) => $clave === 'materias' || str_starts_with($clave, 'materias.'))
        ->map(fn (string $clave) => $errors->first($clave))
        ->unique()
        ->values();
@endphp

<div x-data="{ materias: @js($materiasIniciales), semestres: @js($duracionInicial) }" class="space-y-6">
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <x-input-label for="nombre" :value="__('Nombre del plan')" />
            <x-text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full"
                          :value="old('nombre', $plan?->nombre)" required autofocus
                          placeholder="{{ __('Por ejemplo: Plan de estudios 2026') }}" />
            <x-input-error class="mt-2" :messages="$errors->get('nombre')" />
        </div>

        <div>
            <x-input-label for="carrera_id" :value="__('Carrera')" />
            <select id="carrera_id" name="carrera_id" required
                    class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                <option value="" disabled @selected(! old('carrera_id', $plan?->carrera_id))>{{ __('Seleccione una carrera') }}</option>
                @foreach ($carreras as $carrera)
                    <option value="{{ $carrera->id }}" @selected(old('carrera_id', $plan?->carrera_id) == $carrera->id)>
                        {{ $carrera->nombre }}{{ $carrera->activo ? '' : ' (' . __('inactiva') . ')' }}
                    </option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('carrera_id')" />
        </div>

        <div>
            <x-input-label for="modalidad" :value="__('Modalidad')" />
            <select id="modalidad" name="modalidad" required
                    class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                <option value="" disabled @selected(! old('modalidad', $plan?->modalidad?->value))>{{ __('Seleccione una modalidad') }}</option>
                @foreach ($modalidades as $modalidad)
                    <option value="{{ $modalidad->value }}"
                            @selected(old('modalidad', $plan?->modalidad?->value) === $modalidad->value)>
                        {{ $modalidad->etiqueta() }}
                    </option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-slate-500">{{ __('Modular: avanzas aprobando módulos. Semestral: avanzas por semestres.') }}</p>
            <x-input-error class="mt-2" :messages="$errors->get('modalidad')" />
        </div>

        <div>
            <x-input-label for="semestres" :value="__('Duración en semestres')" />
            <x-text-input id="semestres" name="semestres" type="number" min="1" max="12" class="mt-1 block w-full"
                          x-model.number="semestres"
                          :value="old('semestres', $plan?->semestres ?? 2)" required />
            <p class="mt-1 text-xs text-slate-500">{{ __('Entre 1 y 12 semestres.') }}</p>
            <x-input-error class="mt-2" :messages="$errors->get('semestres')" />
        </div>

        <div class="flex items-end pb-2">
            <label class="inline-flex items-center">
                <input type="checkbox" name="activo" value="1" class="rounded border-slate-300 text-brand-600 shadow-sm"
                       @checked(old('activo', $plan?->activo ?? true))>
                <span class="ms-2 text-sm text-slate-600">{{ __('Plan activo') }}</span>
            </label>
        </div>
    </div>

    <div class="border-t border-slate-100 pt-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="font-semibold text-slate-900">{{ __('Materias del plan') }}</h3>
                <p class="mt-0.5 text-sm text-slate-500">
                    {{ __('Indica el código, el nombre, el semestre, los créditos y los requisitos de cada materia.') }}
                </p>
            </div>

            <button type="button"
                    @click="materias.push({ id: null, codigo: '', nombre: '', semestre: 1, creditos: 0, requisitos: [] })"
                    class="inline-flex items-center gap-2 rounded-lg border border-brand-200 bg-brand-50 px-3.5 py-2 text-sm font-semibold text-brand-700 transition hover:bg-brand-100">
                <x-icon name="plus" class="h-4 w-4" />
                {{ __('Agregar materia') }}
            </button>
        </div>

        @if ($erroresMaterias->isNotEmpty())
            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <ul class="list-disc space-y-1 ps-4">
                    @foreach ($erroresMaterias as $mensaje)
                        <li>{{ $mensaje }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mt-4 space-y-3">
            <template x-for="(materia, indice) in materias" :key="indice">
                <div class="grid grid-cols-1 gap-4 rounded-xl border border-slate-200 bg-slate-50/60 p-4 sm:grid-cols-12">
                    <input type="hidden" :name="`materias[${indice}][id]`" :value="materia.id ?? ''">

                    <div class="sm:col-span-2">
                        <label :for="`materia-codigo-${indice}`" class="block text-xs font-medium text-slate-600">
                            {{ __('Código') }}
                        </label>
                        <input :id="`materia-codigo-${indice}`" :name="`materias[${indice}][codigo]`"
                               type="text" x-model="materia.codigo" maxlength="20" required
                               class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                    </div>

                    <div class="sm:col-span-4">
                        <label :for="`materia-nombre-${indice}`" class="block text-xs font-medium text-slate-600">
                            {{ __('Materia') }}
                        </label>
                        <input :id="`materia-nombre-${indice}`" :name="`materias[${indice}][nombre]`"
                               type="text" x-model="materia.nombre" maxlength="255" required
                               class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                    </div>

                    <div class="sm:col-span-2">
                        <label :for="`materia-semestre-${indice}`" class="block text-xs font-medium text-slate-600">
                            {{ __('Semestre') }}
                        </label>
                        <input :id="`materia-semestre-${indice}`" :name="`materias[${indice}][semestre]`"
                               type="number" min="1" max="12" x-model.number="materia.semestre" required
                               class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                    </div>

                    <div class="sm:col-span-2">
                        <label :for="`materia-creditos-${indice}`" class="block text-xs font-medium text-slate-600">
                            {{ __('Créditos') }}
                        </label>
                        <input :id="`materia-creditos-${indice}`" :name="`materias[${indice}][creditos]`"
                               type="number" min="0" max="20" x-model.number="materia.creditos" required
                               class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                    </div>

                    <div class="sm:col-span-12">
                        <label :for="`materia-requisitos-${indice}`" class="block text-xs font-medium text-slate-600">
                            {{ __('Requisitos (materias que deben aprobarse antes)') }}
                        </label>
                        <select :id="`materia-requisitos-${indice}`" :name="`materias[${indice}][requisitos][]`"
                                multiple size="3" x-model="materia.requisitos"
                                class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                            <template x-for="otra in materias.filter((m, i) => i !== indice && m.codigo)" :key="otra.codigo">
                                <option :value="otra.codigo"
                                        x-text="`${otra.codigo} - ${otra.nombre} (semestre ${otra.semestre})`"></option>
                            </template>
                        </select>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ __('Puedes seleccionar varias materias, todas de semestres anteriores.') }}
                        </p>
                    </div>

                    <div class="flex items-end sm:col-span-2">
                        <button type="button" @click="materias.splice(indice, 1)"
                                :disabled="materias.length === 1"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-red-200 bg-white px-3 py-2 text-sm font-medium text-red-700 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-40">
                            <x-icon name="trash" class="h-4 w-4" />
                            {{ __('Quitar') }}
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <p class="mt-3 text-sm text-slate-500">
            <span x-text="materias.length"></span> {{ __('materias') }} ·
            <span x-text="materias.reduce((total, materia) => total + (Number(materia.creditos) || 0), 0)"></span>
            {{ __('créditos') }}
        </p>

        <p class="mt-1 text-xs text-slate-500">
            {{ __('La duración del plan limita el semestre de cada materia.') }}
        </p>
    </div>
</div>
