<?php

namespace App\Http\Requests;

use App\Models\Curso;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreNotasCursoRequest extends FormRequest
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
        return [
            'notas' => ['nullable', 'array'],
            'notas.*' => ['array'],
            'notas.*.*' => ['nullable', 'numeric', 'min:0', 'max:100', 'decimal:0,2'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'notas.*.*.min' => 'Las notas deben estar entre 0 y 100.',
            'notas.*.*.max' => 'Las notas deben estar entre 0 y 100.',
            'notas.*.*.decimal' => 'Las notas admiten como máximo dos decimales.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var Curso $curso */
            $curso = $this->route('curso');
            $inscripciones = $curso->inscripciones()->pluck('id')->map(fn (int $id) => (string) $id)->all();
            $evaluaciones = $curso->evaluaciones()->pluck('id')->map(fn (int $id) => (string) $id)->all();

            foreach ((array) $this->input('notas', []) as $inscripcionId => $valores) {
                if (! in_array((string) $inscripcionId, $inscripciones, true)) {
                    $validator->errors()->add('notas', 'La grilla contiene un alumno que no pertenece al curso.');

                    continue;
                }

                foreach ((array) $valores as $evaluacionId => $valor) {
                    if (! in_array((string) $evaluacionId, $evaluaciones, true)) {
                        $validator->errors()->add('notas', 'La grilla contiene una actividad que no pertenece al curso.');
                    }
                }
            }

            $suma = round((float) $curso->evaluaciones()->sum('ponderacion'), 2);

            if ($curso->evaluaciones()->exists() && abs($suma - 100) > 0.001) {
                $validator->errors()->add('notas', 'Las ponderaciones del curso deben sumar exactamente 100.00% antes de guardar notas.');
            }
        });
    }
}
