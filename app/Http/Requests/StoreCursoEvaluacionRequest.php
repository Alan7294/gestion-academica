<?php

namespace App\Http\Requests;

use App\Models\Curso;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCursoEvaluacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Curso $curso */
        $curso = $this->route('curso');

        return [
            'evaluaciones' => ['required', 'array', 'min:1'],
            'evaluaciones.*.id' => [
                'nullable',
                'integer',
                Rule::exists('curso_evaluaciones', 'id')
                    ->where(fn ($query) => $query->where('curso_id', $curso->id)),
            ],
            'evaluaciones.*.nombre' => ['required', 'string', 'max:100', 'distinct'],
            'evaluaciones.*.ponderacion' => ['required', 'numeric', 'min:0.01', 'max:100', 'decimal:0,2'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'evaluaciones.required' => 'Debes registrar al menos una actividad.',
            'evaluaciones.min' => 'Debes registrar al menos una actividad.',
            'evaluaciones.*.nombre.distinct' => 'No puedes repetir el nombre de una actividad.',
            'evaluaciones.*.ponderacion.min' => 'Cada ponderación debe ser mayor que cero.',
            'evaluaciones.*.ponderacion.decimal' => 'La ponderación admite como máximo dos decimales.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $suma = round(collect($this->input('evaluaciones', []))->sum(
                fn (array $evaluacion) => (float) ($evaluacion['ponderacion'] ?? 0),
            ), 2);

            if (abs($suma - 100) > 0.001) {
                $validator->errors()->add(
                    'evaluaciones',
                    'La suma de las ponderaciones debe ser exactamente 100.00%.',
                );
            }
        });
    }
}
