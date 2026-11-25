<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Planilla de calificaciones') }} — {{ $curso->nombre }}
            </h2>

            <a href="{{ route('docente.cursos.show', $curso) }}" class="text-sm text-slate-600 hover:text-slate-900">
                {{ __('Volver al curso') }}
            </a>
        </div>
    </x-slot>

    @php
        $evaluaciones = $curso->evaluaciones;
        $sumaPonderaciones = round($evaluaciones->sum(fn ($evaluacion) => (float) $evaluacion->ponderacion), 2);
        $ponderacionCompleta = $evaluaciones->isNotEmpty() && abs($sumaPonderaciones - 100) <= 0.001;

        $formularioEvaluaciones = collect(old('evaluaciones', $evaluaciones->map(fn ($evaluacion) => [
            'id' => $evaluacion->id,
            'nombre' => $evaluacion->nombre,
            'ponderacion' => (float) $evaluacion->ponderacion,
        ])->all()))
            ->values()
            ->map(fn (array $evaluacion, int $indice) => [
                'clave' => $indice + 1,
                'id' => $evaluacion['id'] ?? '',
                'nombre' => $evaluacion['nombre'] ?? '',
                'ponderacion' => $evaluacion['ponderacion'] ?? '',
            ])
            ->all();

        if ($formularioEvaluaciones === []) {
            $formularioEvaluaciones = [['clave' => 1, 'id' => '', 'nombre' => '', 'ponderacion' => '']];
        }
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <ul class="list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="rounded-2xl border border-slate-200/70 bg-white shadow-card p-6"
                     x-data="{
                         filas: @js($formularioEvaluaciones),
                         contador: {{ count($formularioEvaluaciones) }},
                         agregar() {
                             this.contador++;
                             this.filas.push({ clave: this.contador, id: '', nombre: '', ponderacion: '' });
                         },
                         quitar(indice) {
                             this.filas.splice(indice, 1);
                         },
                         total() {
                             const suma = this.filas.reduce((acumulado, fila) => acumulado + (parseFloat(fila.ponderacion) || 0), 0);

                             return suma.toFixed(2);
                         },
                     }">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="font-semibold text-slate-900">{{ __('Actividades y ponderaciones') }}</h3>
                        <p class="mt-1 text-sm text-slate-600">
                            {{ __('Define las actividades una sola vez para todo el curso. La suma debe ser exactamente 100.00%.') }}
                        </p>
                    </div>

                    <span class="rounded-full px-3 py-1 text-sm font-semibold"
                          :class="total() === '100.00' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">
                        {{ __('Total') }}: <span x-text="total()"></span>%
                    </span>
                </div>

                <form method="POST" action="{{ route('docente.evaluaciones.update', $curso) }}" class="mt-5 space-y-3">
                    @csrf
                    @method('PUT')

                    <template x-for="(fila, indice) in filas" :key="fila.clave">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
                            <input type="hidden" :name="`evaluaciones[${indice}][id]`" x-model="fila.id">

                            <div class="flex-1">
                                <label class="text-xs text-slate-500">{{ __('Actividad') }}</label>
                                <input type="text" :name="`evaluaciones[${indice}][nombre]`" x-model="fila.nombre"
                                       maxlength="100" required placeholder="{{ __('Ej. Primer parcial') }}"
                                       class="mt-1 block w-full border-slate-300 rounded-md shadow-sm text-sm">
                            </div>

                            <div class="w-full sm:w-40">
                                <label class="text-xs text-slate-500">{{ __('Ponderación %') }}</label>
                                <input type="number" step="0.01" min="0.01" max="100"
                                       :name="`evaluaciones[${indice}][ponderacion]`" x-model="fila.ponderacion"
                                       required placeholder="30.00"
                                       class="mt-1 block w-full border-slate-300 rounded-md shadow-sm text-sm">
                            </div>

                            <button type="button" @click="quitar(indice)"
                                    class="text-sm text-red-600 hover:text-red-900 sm:pb-2">
                                {{ __('Quitar') }}
                            </button>
                        </div>
                    </template>

                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4">
                        <button type="button" @click="agregar()" class="text-sm font-medium text-brand-600 hover:text-brand-900">
                            + {{ __('Agregar actividad') }}
                        </button>

                        <x-primary-button>{{ __('Guardar actividades') }}</x-primary-button>
                    </div>
                </form>
            </section>

            <section class="rounded-2xl border border-slate-200/70 bg-white shadow-card overflow-hidden">
                <div class="border-b border-slate-100 px-6 py-5">
                    <h3 class="font-semibold text-slate-900">{{ __('Notas por alumno') }}</h3>
                    <p class="mt-1 text-sm text-slate-600">
                        {{ __('Escribe la nota de cada actividad. Deja la celda vacía para quitar la calificación. El promedio se recalcula automáticamente.') }}
                    </p>
                </div>

                @if ($evaluaciones->isEmpty())
                    <div class="px-6 py-8 text-center text-sm text-slate-500">
                        {{ __('Primero define las actividades del curso y sus ponderaciones.') }}
                    </div>
                @elseif (! $ponderacionCompleta)
                    <div class="px-6 py-8 text-center text-sm text-amber-700">
                        {{ __('Las ponderaciones del curso suman :suma%. Ajusta las actividades hasta completar exactamente 100.00% para poder cargar notas.', ['suma' => number_format($sumaPonderaciones, 2)]) }}
                    </div>
                @elseif ($curso->alumnosInscritos->isEmpty())
                    <div class="px-6 py-8 text-center text-sm text-slate-500">
                        {{ __('Este curso todavía no tiene alumnos inscritos.') }}
                    </div>
                @else
                    <form method="POST" action="{{ route('docente.calificaciones.actualizar', $curso) }}">
                        @csrf
                        @method('PUT')

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left font-medium text-slate-500">{{ __('Estudiante') }}</th>

                                        @foreach ($evaluaciones as $evaluacion)
                                            <th class="px-4 py-3 text-center font-medium text-slate-500">
                                                <span class="block">{{ $evaluacion->nombre }}</span>
                                                <span class="block text-xs font-normal text-slate-400">
                                                    {{ number_format((float) $evaluacion->ponderacion, 2) }}%
                                                </span>
                                            </th>
                                        @endforeach

                                        <th class="px-6 py-3 text-center font-medium text-slate-500">{{ __('Promedio') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($curso->alumnosInscritos as $inscripcion)
                                        <tr>
                                            <td class="px-6 py-3">
                                                <p class="font-medium text-slate-900">{{ $inscripcion->estudiante->name }}</p>
                                                <p class="text-xs text-slate-500">{{ $inscripcion->estudiante->email }}</p>
                                            </td>

                                            @foreach ($evaluaciones as $evaluacion)
                                                @php
                                                    $calificacion = $inscripcion->calificaciones
                                                        ->firstWhere('curso_evaluacion_id', $evaluacion->id);
                                                    $valor = old(
                                                        "notas.{$inscripcion->id}.{$evaluacion->id}",
                                                        $calificacion?->nota,
                                                    );
                                                @endphp

                                                <td class="px-4 py-3">
                                                    <input type="number" step="0.01" min="0" max="100"
                                                           name="notas[{{ $inscripcion->id }}][{{ $evaluacion->id }}]"
                                                           value="{{ $valor }}"
                                                           aria-label="{{ __('Nota de :actividad para :estudiante', ['actividad' => $evaluacion->nombre, 'estudiante' => $inscripcion->estudiante->name]) }}"
                                                           class="block w-24 mx-auto border-slate-300 rounded-md shadow-sm text-sm text-center">
                                                </td>
                                            @endforeach

                                            <td class="px-6 py-3 text-center">
                                                <span class="text-base font-semibold text-slate-900">
                                                    {{ $inscripcion->nota_final === null ? '—' : number_format((float) $inscripcion->nota_final, 2) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="flex justify-end border-t border-slate-100 px-6 py-4">
                            <x-primary-button>{{ __('Guardar calificaciones') }}</x-primary-button>
                        </div>
                    </form>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
