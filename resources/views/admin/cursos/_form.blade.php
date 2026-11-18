@php($curso = $curso ?? null)

<div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
    <div>
        <x-input-label for="carrera_id" :value="__('Carrera')" />
        <select id="carrera_id" name="carrera_id" required
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            <option value="" disabled @selected(! old('carrera_id', $curso?->carrera_id))>{{ __('Seleccione una carrera') }}</option>
            @foreach ($carreras as $carrera)
                <option value="{{ $carrera->id }}" @selected(old('carrera_id', $curso?->carrera_id) == $carrera->id)>
                    {{ $carrera->nombre }}
                </option>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('carrera_id')" />
    </div>

    <div>
        <x-input-label for="periodo_id" :value="__('Periodo')" />
        <select id="periodo_id" name="periodo_id" required
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            <option value="" disabled @selected(! old('periodo_id', $curso?->periodo_id))>{{ __('Seleccione un periodo') }}</option>
            @foreach ($periodos as $periodo)
                <option value="{{ $periodo->id }}" @selected(old('periodo_id', $curso?->periodo_id) == $periodo->id)>
                    {{ $periodo->nombre }}{{ $periodo->activo ? ' (vigente)' : '' }}
                </option>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('periodo_id')" />
    </div>

    <div>
        <x-input-label for="codigo" :value="__('Código')" />
        <x-text-input id="codigo" name="codigo" type="text" class="mt-1 block w-full"
                      :value="old('codigo', $curso?->codigo)" required />
        <x-input-error class="mt-2" :messages="$errors->get('codigo')" />
    </div>

    <div>
        <x-input-label for="nombre" :value="__('Nombre del curso')" />
        <x-text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full"
                      :value="old('nombre', $curso?->nombre)" required />
        <x-input-error class="mt-2" :messages="$errors->get('nombre')" />
    </div>

    <div>
        <x-input-label for="docente_id" :value="__('Docente')" />
        <select id="docente_id" name="docente_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            <option value="">{{ __('Sin asignar') }}</option>
            @foreach ($docentes as $docente)
                <option value="{{ $docente->id }}" @selected(old('docente_id', $curso?->docente_id) == $docente->id)>
                    {{ $docente->name }}
                </option>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('docente_id')" />
    </div>

    <div>
        <x-input-label for="semestre" :value="__('Semestre')" />
        <x-text-input id="semestre" name="semestre" type="number" min="1" max="12" class="mt-1 block w-full"
                      :value="old('semestre', $curso?->semestre ?? 1)" required />
        <x-input-error class="mt-2" :messages="$errors->get('semestre')" />
    </div>

    <div>
        <x-input-label for="creditos" :value="__('Créditos')" />
        <x-text-input id="creditos" name="creditos" type="number" min="0" max="20" class="mt-1 block w-full"
                      :value="old('creditos', $curso?->creditos ?? 0)" required />
        <x-input-error class="mt-2" :messages="$errors->get('creditos')" />
    </div>

    <div>
        <x-input-label for="cupo" :value="__('Cupo (0 = sin límite)')" />
        <x-text-input id="cupo" name="cupo" type="number" min="0" max="500" class="mt-1 block w-full"
                      :value="old('cupo', $curso?->cupo ?? 0)" required />
        <x-input-error class="mt-2" :messages="$errors->get('cupo')" />
    </div>
</div>

<label class="mt-6 inline-flex items-center">
    <input type="checkbox" name="activo" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm"
           @checked(old('activo', $curso?->activo ?? true))>
    <span class="ms-2 text-sm text-gray-600">{{ __('Curso activo') }}</span>
</label>
