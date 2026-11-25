<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Equivalencias entre materias') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card p-6">
                <p class="text-sm text-slate-600">
                    {{ __('La materia destino reconoce la aprobación de la materia origen cursada en otra carrera. La equivalencia es global; los planes activos del estudiante determinan cuándo se aplica.') }}
                </p>
                <p class="text-sm text-slate-600">
                    {{ __('Elige primero la carrera de cada lado para filtrar las materias disponibles. Marca la opción bilateral si ambas materias se reconocen mutuamente.') }}
                </p>

                <form method="POST"
                      action="{{ route('admin.equivalencias.store') }}"
                      x-data="{
                          carreraDestinoId: @js((string) old('carrera_destino_id', '')),
                          carreraOrigenId: @js((string) old('carrera_origen_id', '')),
                          materiaDestinoId: @js((string) old('materia_id', '')),
                          materiaOrigenId: @js((string) old('equivalente_id', '')),
                          bilateral: @js((string) old('bilateral', '') === '1'),
                          carrerasPorMateria: @js($carrerasPorMateria),
                          cambiarCarreraDestino() {
                              if (this.materiaDestinoId !== ''
                                  && ! (this.carrerasPorMateria[this.materiaDestinoId] ?? []).includes(Number(this.carreraDestinoId))) {
                                  this.materiaDestinoId = '';
                              }

                              if (this.carreraOrigenId !== ''
                                  && Number(this.carreraOrigenId) === Number(this.carreraDestinoId)) {
                                  this.carreraOrigenId = '';
                                  this.materiaOrigenId = '';
                              }
                          },
                          cambiarCarreraOrigen() {
                              if (this.materiaOrigenId !== ''
                                  && ! (this.carrerasPorMateria[this.materiaOrigenId] ?? []).includes(Number(this.carreraOrigenId))) {
                                  this.materiaOrigenId = '';
                              }
                          },
                      }"
                      class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 items-end">
                    @csrf

                    <div class="sm:col-span-1">
                        <x-input-label for="carrera_destino_id" :value="__('Carrera destino')" />
                        <select id="carrera_destino_id" name="carrera_destino_id" x-model="carreraDestinoId" @change="cambiarCarreraDestino()"
                                class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" required>
                            <option value="">{{ __('Selecciona una carrera') }}</option>
                            @foreach ($carreras as $carrera)
                                <option value="{{ $carrera->id }}">{{ $carrera->nombre }}</option>
                            @endforeach
                        </select>
                        @error('carrera_destino_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-1">
                        <x-input-label for="carrera_origen_id" :value="__('Carrera origen')" />
                        <select id="carrera_origen_id" name="carrera_origen_id" x-model="carreraOrigenId" @change="cambiarCarreraOrigen()"
                                class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" required>
                            <option value="">{{ __('Selecciona una carrera') }}</option>
                            @foreach ($carreras as $carrera)
                                <option value="{{ $carrera->id }}"
                                        :disabled="Number(carreraDestinoId) === {{ $carrera->id }}">
                                    {{ $carrera->nombre }}
                                </option>
                            @endforeach
                        </select>
                        @error('carrera_origen_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-1">
                        <x-input-label for="materia_id" :value="__('Materia destino')" />
                        <select id="materia_id" name="materia_id" x-model="materiaDestinoId"
                                :disabled="! carreraDestinoId"
                                class="mt-1 block w-full rounded-md border-slate-300 shadow-sm disabled:bg-slate-100" required>
                            <option value="">{{ __('Selecciona una materia') }}</option>
                            @foreach ($materias as $materia)
                                <option value="{{ $materia->id }}"
                                        :hidden="! carrerasPorMateria[{{ $materia->id }}].includes(Number(carreraDestinoId))"
                                        :disabled="! carrerasPorMateria[{{ $materia->id }}].includes(Number(carreraDestinoId))">
                                    {{ $materia->codigo }} - {{ $materia->nombre }}
                                </option>
                            @endforeach
                        </select>
                        @error('materia_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-1">
                        <x-input-label for="equivalente_id" :value="__('Reconoce')" />
                        <select id="equivalente_id" name="equivalente_id" x-model="materiaOrigenId"
                                :disabled="! carreraOrigenId"
                                class="mt-1 block w-full rounded-md border-slate-300 shadow-sm disabled:bg-slate-100" required>
                            <option value="">{{ __('Selecciona una materia') }}</option>
                            @foreach ($materias as $materia)
                                <option value="{{ $materia->id }}"
                                        :hidden="! carrerasPorMateria[{{ $materia->id }}].includes(Number(carreraOrigenId))"
                                        :disabled="! carrerasPorMateria[{{ $materia->id }}].includes(Number(carreraOrigenId))">
                                    {{ $materia->codigo }} - {{ $materia->nombre }}
                                </option>
                            @endforeach
                        </select>
                        @error('equivalente_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2 flex items-start gap-3">
                        <input id="bilateral" name="bilateral" type="checkbox" value="1" x-model="bilateral"
                               class="mt-1 rounded border-slate-300 text-brand-600 shadow-sm focus:ring-brand-500" />
                        <div>
                            <x-input-label for="bilateral" :value="__('Registrar también la equivalencia inversa')" />
                            <p class="text-xs text-slate-500">
                                {{ __('La materia origen también reconocerá a la materia destino. Se crean dos registros independientes.') }}
                            </p>
                            @error('bilateral')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <x-primary-button class="mb-0 sm:col-span-2">{{ __('Agregar equivalencia') }}</x-primary-button>
                </form>
            </div>

            <div class="rounded-2xl border border-slate-200/70 bg-white shadow-card overflow-hidden">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Materia destino') }}</th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Reconoce') }}</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($equivalencias as $equivalencia)
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-slate-900">{{ $equivalencia->materia->codigo }} - {{ $equivalencia->materia->nombre }}</div>
                                    <div class="text-xs text-slate-500">
                                        {{ $equivalencia->materia->planesEstudio->pluck('carrera.nombre')->filter()->unique()->join(', ') ?: '—' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-slate-900">{{ $equivalencia->equivalente->codigo }} - {{ $equivalencia->equivalente->nombre }}</div>
                                    <div class="text-xs text-slate-500">
                                        {{ $equivalencia->equivalente->planesEstudio->pluck('carrera.nombre')->filter()->unique()->join(', ') ?: '—' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <form method="POST" action="{{ route('admin.equivalencias.destroy', $equivalencia) }}" class="inline"
                                          onsubmit="return confirm('{{ __('¿Eliminar esta equivalencia?') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900">{{ __('Eliminar') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-8 text-center text-slate-500">
                                    {{ __('Aún no hay equivalencias registradas.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
