<?php

namespace App\Http\Requests;

use App\Models\Materia;
use App\Models\PlanEstudio;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMateriaRequest extends FormRequest
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
        /** @var Materia|null $materia */
        $materia = $this->route('materia');
        $enAlta = $this->isMethod('POST') && ! $this->expectsJson();

        return [
            'codigo' => [
                'required',
                'string',
                'max:20',
                Rule::unique('materias', 'codigo')->ignore($materia?->id),
            ],
            'nombre' => ['required', 'string', 'max:255'],
            // El formulario de planes crea materias solo para el catálogo y luego
            // hace la vinculación; el alta directa incluye ya plan, semestre y créditos.
            'plan_estudio_id' => [
                Rule::requiredIf($enAlta),
                'nullable',
                'integer',
                Rule::exists('planes_estudio', 'id'),
            ],
            'semestre' => [
                Rule::requiredIf($enAlta),
                'nullable',
                'integer',
                'min:1',
                'max:12',
            ],
            'creditos' => [
                Rule::requiredIf($enAlta),
                'nullable',
                'integer',
                'min:1',
                'max:20',
            ],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->filled('plan_estudio_id')) {
                    return;
                }

                $duracion = PlanEstudio::query()
                    ->whereKey($this->input('plan_estudio_id'))
                    ->value('semestres');

                if ($duracion === null) {
                    return;
                }

                $semestre = (int) $this->input('semestre');

                if ($semestre > (int) $duracion) {
                    $validator->errors()->add(
                        'semestre',
                        "El semestre no puede superar la duración del plan ({$duracion} semestres).",
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
            'codigo.required' => 'Ingresa el código de la materia.',
            'codigo.unique' => 'Ya existe una materia con ese código.',
            'nombre.required' => 'Ingresa el nombre de la materia.',
            'plan_estudio_id.required' => 'Selecciona el plan de estudio donde incluir la materia.',
            'plan_estudio_id.exists' => 'El plan de estudio seleccionado no existe.',
            'semestre.required' => 'Selecciona el semestre de la materia en el plan.',
            'creditos.required' => 'Ingresa los créditos de la materia en el plan.',
        ];
    }
}
