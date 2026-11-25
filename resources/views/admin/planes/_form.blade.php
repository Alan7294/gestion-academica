@php
    $plan = $plan ?? null;

    $catalogo = collect($materias)->map(fn (array $materia) => [
        'id' => (int) $materia['id'],
        'codigo' => $materia['codigo'],
        'nombre' => $materia['nombre'],
    ])->values()->all();

    $catalogoPorId = collect($catalogo)->keyBy('id');

    // Se conservan los datos enviados si la validación falla. Si se quitaron
    // todas las materias, no se envía la clave: old() devuelve null, pero la
    // sesión sí tiene entrada previa, así que hay que respetar el vacío en vez
    // de volver a cargar las materias del plan.
    $materiasIniciales = session()->hasOldInput() ? old('materias', []) : null;

    if ($materiasIniciales !== null && ! is_array($materiasIniciales)) {
        $materiasIniciales = [];
    }

    if ($materiasIniciales === null) {
        $materiasIniciales = $plan
            ? $plan->materias->map(fn ($materia) => [
                'id' => (int) $materia->id,
                'codigo' => $materia->codigo,
                'nombre' => $materia->nombre,
                'semestre' => (int) $materia->pivot->semestre,
                'creditos' => (int) $materia->pivot->creditos,
                'requisitos' => $materia->requisitos->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            ])->values()->all()
            : [];
    }

    // El formulario solo envía el id de cada materia, así que al fallar la
    // validación el código y el nombre se recuperan desde el catálogo.
    $materiasIniciales = collect($materiasIniciales)
        ->map(function (array $materia) use ($catalogoPorId) {
            $id = isset($materia['id']) && $materia['id'] !== '' ? (int) $materia['id'] : null;
            $enCatalogo = $id !== null ? $catalogoPorId->get($id) : null;

            return [
                'id' => $id,
                'codigo' => (string) ($materia['codigo'] ?? $enCatalogo['codigo'] ?? ''),
                'nombre' => (string) ($materia['nombre'] ?? $enCatalogo['nombre'] ?? ''),
                'semestre' => (int) ($materia['semestre'] ?? 1),
                'creditos' => (int) ($materia['creditos'] ?? 1),
                'requisitos' => collect($materia['requisitos'] ?? [])
                    ->filter(fn ($id) => $id !== '')
                    ->map(fn ($id) => (int) $id)
                    ->values()
                    ->all(),
            ];
        })
        ->values()
        ->all();

    $carreraInicial = (string) old('carrera_id', $plan?->carrera_id ?? '');
    $duracionInicial = (int) old('semestres', $plan?->semestres ?? 2);

    $erroresMaterias = collect($errors->getBag('default')->keys())
        ->filter(fn (string $clave) => $clave === 'materias' || str_starts_with($clave, 'materias.'))
        ->map(fn (string $clave) => $errors->first($clave))
        ->unique()
        ->values();
@endphp

<div x-data="{
        semestres: @js($duracionInicial),
        catalogo: @js($catalogo),
        materias: @js($materiasIniciales),
        materiaNuevaId: '',
        nueva: { codigo: '', nombre: '' },
        creando: false,
        errorAlta: '',
        get disponibles() {
            return this.catalogo.filter((materia) =>
                ! this.materias.some((seleccionada) => Number(seleccionada.id) === Number(materia.id))
            );
        },
        agregarMateria() {
            const materia = this.catalogo.find((opcion) => Number(opcion.id) === Number(this.materiaNuevaId));

            if (! materia) {
                return;
            }

            this.materias.push({
                id: materia.id,
                codigo: materia.codigo,
                nombre: materia.nombre,
                semestre: 1,
                creditos: 1,
                requisitos: [],
            });
            this.materiaNuevaId = '';
        },
        requisitosDisponibles(indice) {
            const materia = this.materias[indice];
            const semestre = Number(materia?.semestre);

            if (! materia || ! Number.isInteger(semestre) || semestre < 1) {
                return [];
            }

            return this.materias.filter((otra, posicion) => {
                const semestreOtro = Number(otra.semestre);

                return posicion !== indice
                    && otra.id
                    && Number.isInteger(semestreOtro)
                    && semestreOtro >= 1
                    && semestreOtro < semestre;
            });
        },
        depurarRequisitos() {
            // Mientras haya un semestre vacío no se poda nada: x-model.number lo
            // convierte en 0 y eso borraría requisitos válidos al teclear otro valor.
            const semestresValidos = this.materias.every((materia) => {
                const semestre = Number(materia.semestre);

                return Number.isInteger(semestre) && semestre >= 1;
            });

            if (! semestresValidos) {
                return;
            }

            // Al mover un semestre, los requisitos que dejaron de ser previos se quitan.
            this.materias.forEach((materia, indice) => {
                const permitidos = this.requisitosDisponibles(indice).map((otra) => Number(otra.id));

                materia.requisitos = (materia.requisitos ?? [])
                    .map((id) => Number(id))
                    .filter((id) => permitidos.includes(id));
            });
        },
        quitarMateria(indice) {
            const [quitada] = this.materias.splice(indice, 1);

            if (! quitada) {
                return;
            }

            // Ninguna materia que queda puede seguir exigiendo la que se quitó.
            this.materias.forEach((materia) => {
                materia.requisitos = (materia.requisitos ?? [])
                    .filter((id) => Number(id) !== Number(quitada.id));
            });
        },
        async crearMateria() {
            this.errorAlta = '';
            this.creando = true;

            try {
                const respuesta = await fetch(@js(route('admin.materias.store')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': @js(csrf_token()),
                    },
                    body: JSON.stringify({
                        codigo: this.nueva.codigo,
                        nombre: this.nueva.nombre,
                    }),
                });
                const datos = await respuesta.json();

                if (! respuesta.ok) {
                    this.errorAlta = Object.values(datos.errors ?? {}).flat().join(' ');

                    return;
                }

                this.catalogo.push(datos);
                this.materiaNuevaId = datos.id;
                this.agregarMateria();
                this.nueva = { codigo: '', nombre: '' };
            } catch (error) {
                this.errorAlta = @js(__('No se pudo crear la materia. Inténtalo de nuevo.'));
            } finally {
                this.creando = false;
            }
        },
     }" class="space-y-6">
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
                <option value="" disabled @selected($carreraInicial === '')>{{ __('Seleccione una carrera') }}</option>
                @foreach ($carreras as $carrera)
                    <option value="{{ $carrera->id }}" @selected($carreraInicial === (string) $carrera->id)>
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
            <div>
                <label class="inline-flex items-center">
                    <input type="checkbox" name="activo" value="1" class="rounded border-slate-300 text-brand-600 shadow-sm"
                           @checked(old('activo', $plan?->activo ?? true))>
                    <span class="ms-2 text-sm text-slate-600">{{ __('Plan activo') }}</span>
                </label>
                <p class="mt-1 text-xs text-slate-500">{{ __('Solo puede haber un plan activo por carrera y modalidad.') }}</p>
                <x-input-error class="mt-2" :messages="$errors->get('activo')" />
            </div>
        </div>
    </div>

    <div class="border-t border-slate-100 pt-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="font-semibold text-slate-900">{{ __('Materias del plan') }}</h3>
                <p class="mt-0.5 text-sm text-slate-500">
                    {{ __('Selecciona materias del catálogo, ajusta su semestre y créditos, e indica sus requisitos.') }}
                </p>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-4 rounded-xl border border-slate-200 bg-slate-50/60 p-4 lg:grid-cols-2">
            <div>
                <x-input-label for="materia-nueva" :value="__('Agregar materia existente')" />
                <div class="mt-1 flex gap-2">
                    <select id="materia-nueva" x-model.number="materiaNuevaId"
                            class="block w-full rounded-md border-slate-300 shadow-sm disabled:opacity-50">
                        <option value="">{{ __('Seleccione una materia') }}</option>
                        <template x-for="materia in disponibles" :key="materia.id">
                            <option :value="materia.id" x-text="`${materia.codigo} - ${materia.nombre}`"></option>
                        </template>
                    </select>
                    <button type="button" @click="agregarMateria()" :disabled="! materiaNuevaId"
                            class="inline-flex shrink-0 items-center gap-2 rounded-lg border border-brand-200 bg-brand-50 px-3.5 py-2 text-sm font-semibold text-brand-700 transition hover:bg-brand-100 disabled:cursor-not-allowed disabled:opacity-40">
                        <x-icon name="plus" class="h-4 w-4" />
                        {{ __('Agregar') }}
                    </button>
                </div>
                <p class="mt-1 text-xs text-slate-500">{{ __('El catálogo es compartido; cada plan define el semestre y los créditos de la materia.') }}</p>
            </div>

            <div>
                <p class="text-sm font-medium text-slate-700">{{ __('Crear materia nueva') }}</p>
                <div class="mt-1 flex flex-col gap-2 sm:flex-row">
                    <input type="text" x-model="nueva.codigo" maxlength="20"
                           @keydown.enter.prevent="crearMateria()"
                           placeholder="{{ __('Código') }}"
                           class="block w-full rounded-md border-slate-300 shadow-sm disabled:opacity-50 sm:w-1/3">
                    <input type="text" x-model="nueva.nombre" maxlength="255"
                           @keydown.enter.prevent="crearMateria()"
                           placeholder="{{ __('Nombre de la materia') }}"
                           class="block w-full rounded-md border-slate-300 shadow-sm disabled:opacity-50">
                    <button type="button" @click="crearMateria()" :disabled="creando || ! nueva.codigo || ! nueva.nombre"
                            class="inline-flex shrink-0 items-center justify-center rounded-lg bg-brand-600 px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-40">
                        <span x-text="creando ? @js(__('Creando...')) : @js(__('Crear'))"></span>
                    </button>
                </div>
                <p class="mt-1 text-xs text-slate-500">{{ __('Se guarda en el catálogo y queda seleccionada para este plan.') }}</p>
                <p class="mt-1 text-xs text-red-700" x-show="errorAlta" x-text="errorAlta"></p>
            </div>
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
            <template x-for="(materia, indice) in materias" :key="`${indice}-${materia.id}`">
                <div class="grid grid-cols-1 gap-4 rounded-xl border border-slate-200 bg-slate-50/60 p-4 sm:grid-cols-12">
                    <input type="hidden" :name="`materias[${indice}][id]`" :value="materia.id">

                    <div class="sm:col-span-3">
                        <p class="text-xs font-medium text-slate-600">{{ __('Código') }}</p>
                        <p class="mt-1 font-mono text-sm text-slate-700" x-text="materia.codigo"></p>
                        <p class="mt-0.5 text-sm text-slate-900" x-text="materia.nombre"></p>
                    </div>

                    <div class="sm:col-span-2">
                        <label :for="`materia-semestre-${indice}`" class="block text-xs font-medium text-slate-600">
                            {{ __('Semestre') }}
                        </label>
                        <input :id="`materia-semestre-${indice}`" :name="`materias[${indice}][semestre]`"
                               type="number" min="1" max="12" x-model.number="materia.semestre" required
                               @change="depurarRequisitos()"
                               class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                    </div>

                    <div class="sm:col-span-2">
                        <label :for="`materia-creditos-${indice}`" class="block text-xs font-medium text-slate-600">
                            {{ __('Créditos') }}
                        </label>
                        <input :id="`materia-creditos-${indice}`" :name="`materias[${indice}][creditos]`"
                               type="number" min="1" max="20" x-model.number="materia.creditos" required
                               class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                    </div>

                    <div class="sm:col-span-4">
                        <p class="block text-xs font-medium text-slate-600">
                            {{ __('Requisitos (materias que deben aprobarse antes)') }}
                        </p>
                        <div class="mt-1 max-h-36 space-y-1 overflow-y-auto rounded-md border border-slate-300 bg-white p-2">
                            <template x-for="otra in requisitosDisponibles(indice)" :key="otra.id">
                                <label class="flex items-start gap-2 text-sm text-slate-700">
                                    <input type="checkbox"
                                           :name="`materias[${indice}][requisitos][]`"
                                           :value="otra.id"
                                           x-model.number="materia.requisitos"
                                           class="mt-0.5 rounded border-slate-300 text-brand-600 shadow-sm focus:ring-brand-500">
                                    <span x-text="`${otra.codigo} - ${otra.nombre} (semestre ${otra.semestre})`"></span>
                                </label>
                            </template>
                            <p class="text-xs text-slate-400" x-show="requisitosDisponibles(indice).length === 0">
                                {{ __('No hay materias de semestres anteriores.') }}
                            </p>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ __('Solo se ofrecen materias de semestres anteriores.') }}
                        </p>
                    </div>

                    <div class="flex items-end justify-end sm:col-span-1">
                        <button type="button" @click="quitarMateria(indice)"
                                title="{{ __('Quitar del plan') }}"
                                aria-label="{{ __('Quitar del plan') }}"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-red-200 bg-white text-red-600 transition hover:border-red-300 hover:bg-red-50">
                            <x-icon name="trash" class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </template>

            <p class="text-sm text-slate-500" x-show="materias.length === 0">
                {{ __('Agrega al menos una materia del catálogo.') }}
            </p>
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
