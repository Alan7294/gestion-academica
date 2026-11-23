<?php

namespace App\Http\Requests;

use App\Enums\Modalidad;
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
            'materias.*.id' => [
                'nullable',
                'integer',
                Rule::exists('materias', 'id')
                    ->where(fn ($query) => $query->where('plan_estudio_id', $plan?->id)),
            ],
            'materias.*.codigo' => ['required', 'string', 'max:20', 'distinct'],
            'materias.*.nombre' => ['required', 'string', 'max:255'],
            'materias.*.semestre' => ['required', 'integer', 'min:1', 'max:12'],
            'materias.*.creditos' => ['required', 'integer', 'min:0', 'max:20'],
            'materias.*.requisitos' => ['nullable', 'array'],
            'materias.*.requisitos.*' => ['string', 'max:20'],
        ];
    }

    /**
     * Valida la duración del plan y los requisitos declarados entre materias.
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

                $porCodigo = [];

                foreach ($materias as $materia) {
                    if (isset($materia['codigo'])) {
                        $porCodigo[$materia['codigo']] = $materia;
                    }
                }

                foreach ($materias as $indice => $materia) {
                    $requisitos = $materia['requisitos'] ?? [];

                    if (! is_array($requisitos) || ! isset($materia['codigo'], $materia['semestre'])) {
                        continue;
                    }

                    if (count($requisitos) !== count(array_unique($requisitos))) {
                        $validator->errors()->add(
                            "materias.{$indice}.requisitos",
                            'No repitas una materia en la lista de requisitos.',
                        );
                    }

                    foreach ($requisitos as $codigoRequisito) {
                        if (! is_string($codigoRequisito)) {
                            continue;
                        }

                        if (! isset($porCodigo[$codigoRequisito])) {
                            $validator->errors()->add(
                                "materias.{$indice}.requisitos",
                                "La materia '{$codigoRequisito}' no existe en este plan.",
                            );

                            continue;
                        }

                        if ($codigoRequisito === $materia['codigo']) {
                            $validator->errors()->add(
                                "materias.{$indice}.requisitos",
                                'Una materia no puede ser requisito de sí misma.',
                            );

                            continue;
                        }

                        $semestreRequisito = (int) ($porCodigo[$codigoRequisito]['semestre'] ?? 0);

                        if ($semestreRequisito >= (int) $materia['semestre']) {
                            $validator->errors()->add(
                                "materias.{$indice}.requisitos",
                                "El requisito '{$codigoRequisito}' debe pertenecer a un semestre anterior.",
                            );
                        }
                    }
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
            'materias.*.codigo.distinct' => 'No repitas el código de una materia dentro del mismo plan.',
            'materias.*.id.exists' => 'La materia seleccionada no pertenece a este plan de estudios.',
            'materias.*.requisitos.array' => 'La lista de requisitos no es válida.',
            'nombre.unique' => 'Ya existe un plan de estudios con ese nombre para la carrera seleccionada.',
        ];
    }
}
