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
            'codigo' => [
                'required',
                'string',
                'max:20',
                Rule::unique('materias', 'codigo')->ignore($materia?->id),
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
            'codigo.required' => 'Ingresa el código de la materia.',
            'codigo.unique' => 'Ya existe una materia con ese código.',
            'nombre.required' => 'Ingresa el nombre de la materia.',
        ];
    }
}
