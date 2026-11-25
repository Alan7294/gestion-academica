<?php

namespace App\Http\Requests;

use App\Enums\Modalidad;
use App\Models\Curso;
use App\Models\Materia;
use App\Models\Matricula;
use App\Models\PlanEstudio;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePlanEstudioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['activo' => $this->boolean('activo')]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var PlanEstudio|null $plan */
        $plan = $this->route('planEstudio');

        $reglaMateria = fn () => Rule::exists('materias', 'id');

        return [
            'carrera_id' => ['required', 'integer', Rule::exists('carreras', 'id')],
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('planes_estudio', 'nombre')
                    ->where(fn ($query) => $query->where('carrera_id', $this->input('carrera_id')))
                    ->ignore($plan?->id),
            ],
            'modalidad' => ['required', Rule::enum(Modalidad::class)],
            'semestres' => ['required', 'integer', 'min:1', 'max:12'],
            'activo' => ['boolean'],
            'materias' => ['required', 'array', 'min:1'],
            'materias.*.id' => ['required', 'integer', 'distinct', $reglaMateria()],
            'materias.*.semestre' => ['required', 'integer', 'min:1', 'max:12'],
            'materias.*.creditos' => ['required', 'integer', 'min:1', 'max:20'],
            'materias.*.requisitos' => ['nullable', 'array'],
            'materias.*.requisitos.*' => ['integer', $reglaMateria()],
        ];
    }

    /**
     * Valida la duración del plan, los requisitos declarados y que no haya
     * otro plan activo para la misma carrera y modalidad.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $duracion = (int) $this->input('semestres');
                $materias = $this->input('materias');

                if ($duracion < 1 || ! is_array($materias)) {
                    return;
                }

                foreach ($materias as $indice => $materia) {
                    $semestre = (int) ($materia['semestre'] ?? 0);

                    if ($semestre > $duracion) {
                        $validator->errors()->add(
                            "materias.{$indice}.semestre",
                            "El semestre de la materia no puede superar la duración del plan ({$duracion} semestres).",
                        );
                    }
                }
            },

            function (Validator $validator): void {
                $materias = $this->input('materias');

                if (! is_array($materias)) {
                    return;
                }

                $ids = collect($materias)
                    ->pluck('id')
                    ->filter(fn ($id) => is_numeric($id))
                    ->map(fn ($id) => (int) $id)
                    ->values()
                    ->all();

                $codigos = Materia::whereIn('id', $ids)->pluck('codigo', 'id');
                $semestresPorId = collect($materias)->mapWithKeys(fn ($materia) => [
                    (int) ($materia['id'] ?? 0) => (int) ($materia['semestre'] ?? 0),
                ]);

                foreach ($materias as $indice => $materia) {
                    $id = (int) ($materia['id'] ?? 0);
                    $requisitos = collect($materia['requisitos'] ?? [])
                        ->filter(fn ($id) => is_numeric($id))
                        ->map(fn ($id) => (int) $id)
                        ->values();

                    if ($requisitos->count() !== $requisitos->unique()->count()) {
                        $validator->errors()->add(
                            "materias.{$indice}.requisitos",
                            'No repitas una materia en la lista de requisitos.',
                        );
                    }

                    foreach ($requisitos->unique() as $idRequisito) {
                        $codigo = $codigos->get($idRequisito, (string) $idRequisito);

                        if (! in_array($idRequisito, $ids, true)) {
                            $validator->errors()->add(
                                "materias.{$indice}.requisitos",
                                "La materia '{$codigo}' no existe en este plan.",
                            );

                            continue;
                        }

                        if ($idRequisito === $id) {
                            $validator->errors()->add(
                                "materias.{$indice}.requisitos",
                                'Una materia no puede ser requisito de sí misma.',
                            );

                            continue;
                        }

                        $semestreRequisito = (int) $semestresPorId->get($idRequisito, 0);

                        if ($semestreRequisito >= (int) ($materia['semestre'] ?? 0)) {
                            $validator->errors()->add(
                                "materias.{$indice}.requisitos",
                                "El requisito '{$codigo}' debe pertenecer a un semestre anterior.",
                            );
                        }
                    }
                }
            },

            function (Validator $validator): void {
                if (! $this->boolean('activo')) {
                    return;
                }

                $carreraId = $this->input('carrera_id');
                $modalidad = $this->input('modalidad');

                if (! $carreraId || ! $modalidad) {
                    return;
                }

                /** @var PlanEstudio|null $plan */
                $plan = $this->route('planEstudio');

                $conflicto = PlanEstudio::query()
                    ->where('carrera_id', $carreraId)
                    ->where('modalidad', $modalidad)
                    ->where('activo', true)
                    ->when($plan, fn ($query) => $query->whereKeyNot($plan->id))
                    ->first(['id', 'nombre']);

                if ($conflicto) {
                    $validator->errors()->add(
                        'activo',
                        "Ya existe un plan activo para esa carrera y modalidad: «{$conflicto->nombre}». Desactívalo antes de activar este.",
                    );
                }
            },

            function (Validator $validator): void {
                /** @var PlanEstudio|null $plan */
                $plan = $this->route('planEstudio');

                if (! $plan) {
                    return;
                }

                $carreraId = $this->input('carrera_id');
                $modalidad = $this->input('modalidad');

                if (! $carreraId || ! $modalidad) {
                    return;
                }

                $tieneMatriculas = Matricula::where('plan_estudio_id', $plan->id)->exists();
                $tieneCursos = Curso::whereIn('materia_id', $plan->materias()->pluck('materias.id'))->exists();

                if (! $tieneMatriculas && ! $tieneCursos) {
                    return;
                }

                if ((int) $carreraId !== $plan->carrera_id) {
                    $validator->errors()->add(
                        'carrera_id',
                        'No puedes cambiar la carrera de un plan que ya tiene matrículas o cursos asociados. Crea un plan nuevo y reasigna las matrículas.',
                    );
                }

                if ($modalidad !== $plan->modalidad->value) {
                    $validator->errors()->add(
                        'modalidad',
                        'No puedes cambiar la modalidad de un plan que ya tiene matrículas o cursos asociados. Crea un plan nuevo y reasigna las matrículas.',
                    );
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'materias.required' => 'Agrega al menos una materia al plan de estudios.',
            'materias.min' => 'Agrega al menos una materia al plan de estudios.',
            'materias.*.id.required' => 'Selecciona una materia del catálogo.',
            'materias.*.id.distinct' => 'No repitas una materia dentro del mismo plan.',
            'materias.*.id.exists' => 'La materia seleccionada no existe en el catálogo.',
            'materias.*.creditos.min' => 'Cada materia debe tener al menos 1 crédito.',
            'materias.*.requisitos.array' => 'La lista de requisitos no es válida.',
            'materias.*.requisitos.*.exists' => 'Uno de los requisitos no existe en el catálogo.',
            'nombre.unique' => 'Ya existe un plan de estudios con ese nombre para la carrera seleccionada.',
        ];
    }
}
