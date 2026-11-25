@php
    $usuario = $usuario ?? null;
    $rolActual = old('rol', $usuario?->rolPrincipal()?->value ?? \App\Enums\Rol::Estudiante->value);
    $matriculasIniciales = old('matriculas', $usuario
        ? $usuario->matriculas
            ->filter(fn ($matricula) => $matricula->estaActiva())
            ->map(fn ($matricula) => [
                'carrera_id' => (string) $matricula->carrera_id,
                'modalidad' => $matricula->modalidad?->value ?? '',
                'es_principal' => (bool) $matricula->es_principal,
                'plan_estudio_id' => $matricula->plan_estudio_id ? (string) $matricula->plan_estudio_id : '',
            ])
            ->values()
            ->all()
        : [['carrera_id' => '', 'modalidad' => '', 'es_principal' => true, 'plan_estudio_id' => '']]);
    $matriculasBaja = $usuario?->matriculas->filter(fn ($matricula) => ! $matricula->estaActiva()) ?? collect();
    $certificadosIniciales = old('certificados', $usuario
        ? $usuario->certificadosDocentes
            ->map(fn ($certificado) => [
                'titulo' => $certificado->titulo,
                'institucion' => $certificado->institucion,
                'codigo' => $certificado->codigo,
            ])
            ->values()
            ->all()
        : [['titulo' => '', 'institucion' => '', 'codigo' => '']]);
@endphp

<div x-data="{ rol: @js($rolActual), matriculas: @js($matriculasIniciales), certificados: @js($certificadosIniciales) }">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div>
            <x-input-label for="name" :value="__('Nombre completo')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                          :value="old('name', $usuario?->name)" required autofocus
                          pattern="[\p{L} ]+" title="Solo se permiten letras" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="ci" :value="__('Carnet de identidad')" />
            <x-text-input id="ci" name="ci" type="text" class="mt-1 block w-full"
                          :value="old('ci', $usuario?->ci)" required
                          inputmode="numeric" minlength="6" maxlength="20" pattern="[0-9]+"
                          title="Mínimo 6 dígitos, solo números" />
            <x-input-error class="mt-2" :messages="$errors->get('ci')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Correo electrónico')" />
            <x-text-input id="email" name="email" type="text" class="mt-1 block w-full"
                          :value="old('email', $usuario?->email)" required
                          inputmode="email" autocomplete="email"
                          title="Ejemplo: nombre@dominio.com" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />
        </div>

        <div>
            <x-input-label for="telefono" :value="__('Teléfono')" />
            <x-text-input id="telefono" name="telefono" type="text" class="mt-1 block w-full"
                          :value="old('telefono', $usuario?->telefono)"
                          inputmode="numeric" minlength="8" maxlength="30" pattern="[0-9]*"
                          title="Mínimo 8 dígitos, solo números" />
            <x-input-error class="mt-2" :messages="$errors->get('telefono')" />
        </div>

        <div>
            <x-input-label for="rol" :value="__('Rol')" />
            <select id="rol" name="rol" required x-model="rol" class="mt-1 block w-full border-slate-300 rounded-md shadow-sm">
                @foreach ($roles as $rol)
                    <option value="{{ $rol->value }}"
                            @selected(old('rol', $usuario?->rolPrincipal()?->value) === $rol->value)>
                        {{ $rol->etiqueta() }}
                    </option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('rol')" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Contraseña')" />
            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full"
                          :required="$usuario === null" autocomplete="new-password" />
            @if ($usuario)
                <p class="mt-1 text-xs text-slate-500">{{ __('Déjala vacía para conservar la contraseña actual.') }}</p>
            @endif
            <x-input-error class="mt-2" :messages="$errors->get('password')" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Confirmar contraseña')" />
            <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full"
                          :required="$usuario === null" autocomplete="new-password" />
        </div>

        {{-- Un estudiante puede tener matrículas activas en más de una carrera. --}}
        <div x-show="rol === 'estudiante'" x-cloak
             class="sm:col-span-2 rounded-xl border border-brand-100 bg-brand-50/40 p-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <p class="text-sm font-medium text-slate-800">{{ __('Matrículas académicas') }}</p>
                    <p class="mt-0.5 text-xs text-slate-500">
                        {{ __('Marcar una carrera como principal; las demás serán secundarias.') }}
                    </p>
                </div>

                <button type="button"
                        @click="matriculas.push({ carrera_id: '', modalidad: '', es_principal: matriculas.length === 0, plan_estudio_id: '' })"
                        class="text-sm font-medium text-brand-700 hover:text-brand-900">
                    + {{ __('Agregar carrera') }}
                </button>
            </div>

            <div class="mt-4 space-y-3">
                <template x-for="(matricula, indice) in matriculas" :key="indice">
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 rounded-lg border border-slate-200 bg-white p-3">
                        <div>
                            <x-input-label :value="__('Carrera')" />
                            <select :id="'carrera_' + indice" :name="`matriculas[${indice}][carrera_id]`"
                                    x-model="matricula.carrera_id" :required="rol === 'estudiante'"
                                    class="mt-1 block w-full border-slate-300 rounded-md shadow-sm text-sm">
                                <option value="">{{ __('Selecciona una carrera') }}</option>
                                @foreach ($carreras as $carrera)
                                    <option value="{{ $carrera->id }}">{{ $carrera->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label :value="__('Modalidad')" />
                            <select :id="'modalidad_' + indice" :name="`matriculas[${indice}][modalidad]`"
                                    x-model="matricula.modalidad" :required="rol === 'estudiante'"
                                    class="mt-1 block w-full border-slate-300 rounded-md shadow-sm text-sm">
                                <option value="">{{ __('Selecciona una modalidad') }}</option>
                                @foreach ($modalidades as $modalidad)
                                    <option value="{{ $modalidad->value }}">{{ $modalidad->etiqueta() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex items-end">
                            <label class="inline-flex items-center text-sm text-slate-600">
                                <input type="checkbox" :name="`matriculas[${indice}][es_principal]`" value="1"
                                       x-model="matricula.es_principal"
                                       @change="if (matricula.es_principal) { matriculas.forEach((otra, i) => { if (i !== indice) otra.es_principal = false; }); }"
                                       class="rounded border-slate-300 text-brand-600 shadow-sm">
                                <span class="ms-2">{{ __('Principal') }}</span>
                            </label>
                        </div>

                        <div class="flex items-end justify-end gap-2">
                            <input type="hidden" :name="`matriculas[${indice}][plan_estudio_id]`" x-model="matricula.plan_estudio_id">
                            <button type="button" @click="matriculas.splice(indice, 1)"
                                    class="text-sm text-red-600 hover:text-red-900"
                                    :disabled="matriculas.length === 1">
                                {{ __('Quitar') }}
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            @foreach ($errors->get('matriculas') as $mensaje)
                <p class="mt-2 text-sm text-red-600">{{ $mensaje }}</p>
            @endforeach
            @foreach ($errors->get('matriculas.*.carrera_id') as $mensajes)
                @foreach ($mensajes as $mensaje)
                    <p class="mt-2 text-sm text-red-600">{{ $mensaje }}</p>
                @endforeach
            @endforeach
            @foreach ($errors->get('matriculas.*.modalidad') as $mensajes)
                @foreach ($mensajes as $mensaje)
                    <p class="mt-2 text-sm text-red-600">{{ $mensaje }}</p>
                @endforeach
            @endforeach
            @foreach ($errors->get('matriculas.*.plan_estudio_id') as $mensajes)
                @foreach ($mensajes as $mensaje)
                    <p class="mt-2 text-sm text-red-600">{{ $mensaje }}</p>
                @endforeach
            @endforeach

            @if ($matriculasBaja->isNotEmpty())
                <div class="mt-4 border-t border-slate-200 pt-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Historial de bajas') }}</p>
                    <ul class="mt-1 text-xs text-slate-500 space-y-0.5">
                        @foreach ($matriculasBaja as $matricula)
                            <li>
                                {{ $matricula->carrera->nombre }}
                                @if ($matricula->fecha_fin)
                                    · {{ __('Baja el') }} {{ $matricula->fecha_fin->format('d/m/Y') }}
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        {{-- Los certificados son texto; no se adjuntan archivos. --}}
        <div x-show="rol === 'docente'" x-cloak
             class="sm:col-span-2 rounded-xl border border-brand-100 bg-brand-50/40 p-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <p class="text-sm font-medium text-slate-800">{{ __('Certificados del docente') }}</p>
                    <p class="mt-0.5 text-xs text-slate-500">
                        {{ __('Registra el título, dónde lo obtuvo y su código.') }}
                    </p>
                </div>

                <button type="button"
                        @click="certificados.push({ titulo: '', institucion: '', codigo: '' })"
                        class="text-sm font-medium text-brand-700 hover:text-brand-900">
                    + {{ __('Agregar certificado') }}
                </button>
            </div>

            <div class="mt-4 space-y-3">
                <template x-for="(certificado, indice) in certificados" :key="indice">
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 rounded-lg border border-slate-200 bg-white p-3">
                        <div>
                            <x-input-label :value="__('Título')" />
                            <x-text-input x-bind:name="'certificados[' + indice + '][titulo]'" x-model="certificado.titulo"
                                          class="mt-1 block w-full text-sm" />
                        </div>

                        <div>
                            <x-input-label :value="__('Institution')" />
                            <x-text-input x-bind:name="'certificados[' + indice + '][institucion]'" x-model="certificado.institucion"
                                          class="mt-1 block w-full text-sm" />
                        </div>

                        <div>
                            <x-input-label :value="__('Codigo')" />
                            <x-text-input x-bind:name="'certificados[' + indice + '][codigo]'" x-model="certificado.codigo"
                                          class="mt-1 block w-full text-sm" />
                        </div>

                        <div class="flex items-end justify-end">
                            <button type="button" @click="certificados.splice(indice, 1)"
                                    class="text-sm text-red-600 hover:text-red-900">
                                {{ __('Quitar') }}
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            @foreach ($errors->get('certificados.*.titulo') as $mensajes)
                @foreach ($mensajes as $mensaje)
                    <p class="mt-2 text-sm text-red-600">{{ $mensaje }}</p>
                @endforeach
            @endforeach
            @foreach ($errors->get('certificados.*.institucion') as $mensajes)
                @foreach ($mensajes as $mensaje)
                    <p class="mt-2 text-sm text-red-600">{{ $mensaje }}</p>
                @endforeach
            @endforeach
            @foreach ($errors->get('certificados.*.codigo') as $mensajes)
                @foreach ($mensajes as $mensaje)
                    <p class="mt-2 text-sm text-red-600">{{ $mensaje }}</p>
                @endforeach
            @endforeach
        </div>
    </div>

    <label class="mt-6 inline-flex items-center">
        <input type="checkbox" name="activo" value="1" class="rounded border-slate-300 text-brand-600 shadow-sm"
               @checked(old('activo', $usuario?->activo ?? true))>
        <span class="ms-2 text-sm text-slate-600">{{ __('Usuario activo') }}</span>
    </label>
</div>
