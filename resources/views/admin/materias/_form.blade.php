@php($materia = $materia ?? null)
@php($creando = $creando ?? false)

<div class="space-y-6">
    <p class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
        @if ($creando)
            {{ __('La materia se registra en el catálogo general y se incluye de una vez en el plan elegido. Puedes ajustar su semestre y créditos desde el plan.') }}
        @else
            {{ __('La materia se registra en el catálogo general. Se usa en los planes de estudio que la incluyan, incluso en carreras distintas.') }}
        @endif
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

    @if ($creando)
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <x-input-label for="plan_estudio_id" :value="__('Plan de estudio')" />
                <select id="plan_estudio_id" name="plan_estudio_id" required
                        class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                    <option value="" disabled @selected(! old('plan_estudio_id'))>{{ __('Selecciona un plan') }}</option>
                    @foreach ($planes as $plan)
                        <option value="{{ $plan->id }}" @selected((string) old('plan_estudio_id') === (string) $plan->id)>
                            {{ $plan->carrera?->nombre }} · {{ $plan->nombre }}
                        </option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('plan_estudio_id')" />
            </div>

            <div>
                <x-input-label for="semestre" :value="__('Semestre')" />
                <select id="semestre" name="semestre" required
                        class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                    @foreach (range(1, 12) as $semestre)
                        <option value="{{ $semestre }}" @selected((int) old('semestre', 1) === $semestre)>
                            {{ $semestre }}
                        </option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('semestre')" />
            </div>

            <div>
                <x-input-label for="creditos" :value="__('Créditos')" />
                <x-text-input id="creditos" name="creditos" type="number" class="mt-1 block w-full"
                              :value="old('creditos', 1)" required min="1" max="20" />
                <x-input-error class="mt-2" :messages="$errors->get('creditos')" />
            </div>
        </div>
    @endif
</div>
