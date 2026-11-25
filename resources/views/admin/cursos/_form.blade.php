@php($curso = $curso ?? null)
@php($creando = $creando ?? false)

<div x-data="{
          carreraId: @js((string) old('carrera_id', $curso?->carrera_id)),
          planId: @js((string) old('plan_estudio_id', $curso?->plan_estudio_id)),
          materiaId: @js((string) old('materia_id', $curso?->materia_id)),
          modalidad: @js((string) old('modalidad', $curso?->modalidad?->value ?? '')),
          horarios: @js(old('horarios', [['dia_semana' => '', 'hora_inicio' => '', 'hora_fin' => '']])),
          planes: @js($planes),
           planesSeleccionados: @js(old('planes_estudio_ids', $curso?->planesEstudio->pluck('id')->map(fn ($id) => (string) $id)->all() ?? [])),
          errores: @js($errors->messages()),
          get planesDisponibles() {
              return this.planes.filter((plan) => String(plan.carrera_id) === String(this.carreraId)
                  && (this.modalidad === '' || plan.modalidad === this.modalidad));
          },
          get planesParticipantes() {
               return this.planes.filter((plan) => String(plan.id) !== String(this.planId)
                   && plan.modalidad === this.modalidad
                   && (plan.materias ?? []).some((materia) => String(materia.id) === String(this.materiaId)));
           },
           get planSeleccionado() {
              return this.planes.find((plan) => String(plan.id) === String(this.planId)) ?? null;
          },
          get materiasDisponibles() {
              return this.planSeleccionado?.materias ?? [];
          },
          get materiaSeleccionada() {
              return this.materiasDisponibles.find((materia) => String(materia.id) === String(this.materiaId)) ?? null;
          },
          get esModular() {
              return this.modalidad === 'modular';
          },
          get esSemestral() {
              return this.modalidad === 'semestral';
          },
          cambiarCarrera() {
               this.planesSeleccionados = [];
              this.planId = '';
              this.materiaId = '';
          },
          cambiarModalidad() {
               this.planesSeleccionados = [];
              this.planId = '';
              this.materiaId = '';
          },
          cambiarPlan() {
               this.planesSeleccionados = this.planId === '' ? [] : [String(this.planId)];
              this.materiaId = '';
              this.modalidad = this.planSeleccionado?.modalidad ?? this.modalidad;
          },
          cambiarMateria() {
               const permitidos = new Set([
                   String(this.planId),
                   ...this.planesParticipantes.map((plan) => String(plan.id)),
               ]);

               this.planesSeleccionados = this.planesSeleccionados.filter((id) => permitidos.has(String(id)));
           },
           agregarHorario() {
              this.horarios.push({dia_semana: '', hora_inicio: '', hora_fin: ''});
          },
          quitarHorario(indice) {
              if (this.horarios.length > 1) {
                  this.horarios.splice(indice, 1);
              }
          }
       }">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div>
            <x-input-label for="carrera_id" :value="__('Carrera')" />
            <select id="carrera_id" name="carrera_id" x-model="carreraId" @change="cambiarCarrera()" required
                    class="mt-1 block w-full border-slate-300 rounded-md shadow-sm">
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
            <x-input-label for="modalidad" :value="__('Modalidad del curso')" />
            <select id="modalidad" name="modalidad" x-model="modalidad" @change="cambiarModalidad()" required
                    class="mt-1 block w-full border-slate-300 rounded-md shadow-sm">
                <option value="" disabled>{{ __('Seleccione una modalidad') }}</option>
                @foreach ($modalidades as $modalidadOpcion)
                    <option value="{{ $modalidadOpcion->value }}"
                            @selected(old('modalidad', $curso?->modalidad?->value) === $modalidadOpcion->value)>
                        {{ $modalidadOpcion->etiqueta() }}
                    </option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-slate-500">
                {{ __('Filtra los planes disponibles. Al elegir un plan se usa su modalidad.') }}
            </p>
            <x-input-error class="mt-2" :messages="$errors->get('modalidad')" />
        </div>

        <div>
            <x-input-label for="plan_estudio_id" :value="__('Plan de estudios')" />
            <select id="plan_estudio_id" name="plan_estudio_id" x-model="planId" @change="cambiarPlan()" required
                    class="mt-1 block w-full border-slate-300 rounded-md shadow-sm">
                <option value="" disabled>{{ __('Seleccione un plan') }}</option>
                <template x-for="plan in planesDisponibles" :key="plan.id">
                    <option :value="String(plan.id)" x-text="plan.etiqueta"></option>
                </template>
            </select>
            <p class="mt-1 text-xs text-slate-500">{{ __('Solo se muestran los planes de la carrera y modalidad elegidas. El plan define semestre y créditos.') }}</p>
            <x-input-error class="mt-2" :messages="$errors->get('plan_estudio_id')" />
        </div>

        <div>
            <x-input-label for="materia_id" :value="__('Materia')" />
            <select id="materia_id" name="materia_id" x-model="materiaId" @change="cambiarMateria()" required
                    class="mt-1 block w-full border-slate-300 rounded-md shadow-sm">
                <option value="" disabled>{{ __('Seleccione una materia') }}</option>
                <template x-for="materia in materiasDisponibles" :key="materia.id">
                    <option :value="String(materia.id)" x-text="materia.etiqueta"></option>
                </template>
            </select>
            <input type="hidden" name="planes_estudio_ids[]" :value="String(planId)">
            <p class="mt-1 text-xs text-slate-500">{{ __('Solo se muestran las materias incluidas en el plan elegido.') }}</p>
            <x-input-error class="mt-2" :messages="$errors->get('materia_id')" />

            <div class="mt-4 border-t border-slate-200 pt-4">
                <p class="text-sm font-medium text-slate-700">{{ __('Planes participantes') }}</p>
                <p class="mt-1 text-xs text-slate-500">
                    {{ __('El plan anfitrión se incluye automáticamente. Todos los planes comparten el cupo del curso.') }}
                </p>

                <div class="mt-3 space-y-2">
                    <p x-show="planesParticipantes.length === 0" class="text-xs text-slate-500" x-cloak>
                        {{ __('No hay otros planes con esta materia y modalidad.') }}
                    </p>

                    <template x-for="plan in planesParticipantes" :key="plan.id">
                        <label class="flex items-start gap-2 text-sm text-slate-700">
                            <input type="checkbox" name="planes_estudio_ids[]" :value="String(plan.id)"
                                   x-model="planesSeleccionados"
                                   class="mt-0.5 rounded border-slate-300 text-brand-600 shadow-sm">
                            <span x-text="`${plan.carrera_nombre} · ${plan.etiqueta}`"></span>
                        </label>
                    </template>
                </div>

                <x-input-error class="mt-2" :messages="$errors->get('planes_estudio_ids')" />
            </div>
        </div>

        <div>
            <x-input-label for="periodo_id" :value="__('Periodo')" />
            <select id="periodo_id" name="periodo_id" required
                    class="mt-1 block w-full border-slate-300 rounded-md shadow-sm">
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
            <x-input-label for="docente_id" :value="__('Docente')" />
            <select id="docente_id" name="docente_id" class="mt-1 block w-full border-slate-300 rounded-md shadow-sm"
                    @if ($creando) required @endif>
                <option value="">{{ $creando ? __('Seleccione un docente') : __('Sin asignar') }}</option>
                @foreach ($docentes as $docente)
                    <option value="{{ $docente->id }}" @selected(old('docente_id', $curso?->docente_id) == $docente->id)>
                        {{ $docente->name }}
                    </option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('docente_id')" />
        </div>

        <div x-show="materiaSeleccionada" class="sm:col-span-2 rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
            <p class="font-medium text-slate-800" x-text="materiaSeleccionada?.etiqueta"></p>
            <p class="mt-1">
                {{ __('Semestre') }}: <span x-text="materiaSeleccionada?.semestre ?? '—'"></span>
                · {{ __('Créditos') }}: <span x-text="materiaSeleccionada?.creditos ?? '—'"></span>
            </p>
            <p class="mt-1 text-xs text-slate-500">
                {{ __('El nombre, el semestre y los créditos se toman del plan y no se editan aquí.') }}
            </p>
        </div>

        <div>
            <x-input-label for="cupo" :value="__('Cupo (0 = sin límite)')" />
            <x-text-input id="cupo" name="cupo" type="number" min="0" max="500" class="mt-1 block w-full"
                          :value="old('cupo', $curso?->cupo ?? 0)" required />
            <x-input-error class="mt-2" :messages="$errors->get('cupo')" />
        </div>
    </div>

    <label class="mt-6 inline-flex items-center">
        <input type="checkbox" name="activo" value="1" class="rounded border-slate-300 text-brand-600 shadow-sm"
               @checked(old('activo', $curso?->activo ?? true))>
        <span class="ms-2 text-sm text-slate-600">{{ __('Curso activo') }}</span>
    </label>

    @if ($creando)
        <div class="mt-8 border-t border-slate-200 pt-6">
            <h3 class="font-semibold text-slate-900">{{ __('Horario del curso') }}</h3>
            <p class="mt-1 text-sm text-slate-600">
                {{ __('Define el horario inicial junto con el curso. El docente y el aula son obligatorios.') }}
            </p>

            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <x-input-label for="aula" :value="__('Aula')" />
                    <x-text-input id="aula" name="aula" type="text" class="mt-1 block w-full"
                                  :value="old('aula')" placeholder="Ej. Aula 101" required />
                    <x-input-error class="mt-2" :messages="$errors->get('aula')" />
                </div>
            </div>

            <template x-if="esSemestral">
                <div class="mt-6 space-y-4">
                    <p class="text-sm font-medium text-slate-700">
                        {{ __('Días en que se cursa la materia, cada uno con su horario.') }}
                    </p>

                    <template x-for="(horario, indice) in horarios" :key="indice">
                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-start rounded-lg border border-slate-200 bg-slate-50 p-4">
                            <div>
                                <x-input-label :value="__('Día')" />
                                <select :name="`horarios[${indice}][dia_semana]`" x-model="horario.dia_semana" required
                                        class="mt-1 block w-full border-slate-300 rounded-md shadow-sm text-sm">
                                    <option value="">{{ __('Seleccione un día') }}</option>
                                    @foreach (\App\Enums\DiaSemana::opciones() as $valor => $etiqueta)
                                        <option value="{{ $valor }}">{{ $etiqueta }}</option>
                                    @endforeach
                                </select>
                                <template x-for="mensaje in (errores['horarios.' + indice + '.dia_semana'] ?? [])" :key="mensaje">
                                    <p class="mt-1.5 text-sm text-rose-600" x-text="mensaje"></p>
                                </template>
                            </div>

                            <div>
                                <x-input-label :value="__('Desde')" />
                                <input :name="`horarios[${indice}][hora_inicio]`" x-model="horario.hora_inicio"
                                       type="time" required
                                       class="mt-1 block w-full rounded-lg border-slate-300 text-slate-900 shadow-sm sm:text-sm">
                                <template x-for="mensaje in (errores['horarios.' + indice + '.hora_inicio'] ?? [])" :key="mensaje">
                                    <p class="mt-1.5 text-sm text-rose-600" x-text="mensaje"></p>
                                </template>
                            </div>

                            <div>
                                <x-input-label :value="__('Hasta')" />
                                <input :name="`horarios[${indice}][hora_fin]`" x-model="horario.hora_fin"
                                       type="time" required
                                       class="mt-1 block w-full rounded-lg border-slate-300 text-slate-900 shadow-sm sm:text-sm">
                                <template x-for="mensaje in (errores['horarios.' + indice + '.hora_fin'] ?? [])" :key="mensaje">
                                    <p class="mt-1.5 text-sm text-rose-600" x-text="mensaje"></p>
                                </template>
                            </div>

                            <div class="flex items-end justify-end">
                                <button type="button" @click="quitarHorario(indice)" x-show="horarios.length > 1"
                                        class="text-sm text-red-600 hover:text-red-900">
                                    {{ __('Quitar') }}
                                </button>
                            </div>
                        </div>
                    </template>

                    <x-primary-button type="button" @click="agregarHorario()" class="bg-slate-200 text-slate-800 hover:bg-slate-300">
                        {{ __('Agregar otro día') }}
                    </x-primary-button>

                    <x-input-error class="mt-2" :messages="$errors->get('horarios')" />
                    <x-input-error class="mt-2" :messages="$errors->get('hora_inicio')" />
                    <x-input-error class="mt-2" :messages="$errors->get('hora_fin')" />
                    <p class="text-xs text-slate-500">
                        {{ __('Cada día rige durante todo el periodo. El sistema rechaza cruces con otras clases del docente.') }}
                    </p>
                </div>
            </template>

            <template x-if="esModular">
                <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <div>
                        <x-input-label for="fecha_inicio" :value="__('Inicio del módulo')" />
                        <x-text-input id="fecha_inicio" name="fecha_inicio" type="date" class="mt-1 block w-full"
                                      :value="old('fecha_inicio')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('fecha_inicio')" />
                    </div>
                    <div>
                        <x-input-label for="hora_inicio" :value="__('Desde')" />
                        <x-text-input id="hora_inicio" name="hora_inicio" type="time" class="mt-1 block w-full"
                                      :value="old('hora_inicio')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('hora_inicio')" />
                    </div>
                    <div>
                        <x-input-label for="hora_fin" :value="__('Hasta')" />
                        <x-text-input id="hora_fin" name="hora_fin" type="time" class="mt-1 block w-full"
                                      :value="old('hora_fin')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('hora_fin')" />
                    </div>
                    <p class="sm:col-span-3 text-xs text-slate-500">
                        {{ __('Se generan 20 sesiones de lunes a viernes dentro del periodo elegido.') }}
                    </p>
                </div>
            </template>
        </div>
    @endif
</div>
