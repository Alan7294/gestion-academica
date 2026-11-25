<?php

namespace App\Http\Requests;

use App\Models\Materia;
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

        return [
            'carrera_id' => ['required', 'integer', Rule::exists('carreras', 'id')],
            'codigo' => [
                'required',
                'string',
                'max:20',
                Rule::unique('materias', 'codigo')
                    ->where(fn ($query) => $query->where('carrera_id', $this->input('carrera_id')))
                    ->ignore($materia?->id),
            ],
            'nombre' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'carrera_id.required' => 'Selecciona la carrera de la materia.',
            'codigo.required' => 'Ingresa el código de la materia.',
            'codigo.unique' => 'Ya existe una materia con ese código en la carrera seleccionada.',
            'nombre.required' => 'Ingresa el nombre de la materia.',
        ];
    }
}
