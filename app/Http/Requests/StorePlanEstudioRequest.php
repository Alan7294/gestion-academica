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
            'materias.*.codigo' => ['required', 'string', 'max:20', 'distinct'],
            'materias.*.nombre' => ['required', 'string', 'max:255'],
            'materias.*.semestre' => ['required', 'integer', 'min:1', 'max:12'],
            'materias.*.creditos' => ['required', 'integer', 'min:0', 'max:20'],
        ];
    }

    /**
     * Una materia no puede ubicarse en un semestre posterior a la duración del plan.
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
            'nombre.unique' => 'Ya existe un plan de estudios con ese nombre para la carrera seleccionada.',
        ];
    }
}
