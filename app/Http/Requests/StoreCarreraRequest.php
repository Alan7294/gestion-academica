<?php

namespace App\Http\Requests;

use App\Models\Carrera;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCarreraRequest extends FormRequest
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
        $carrera = $this->route('carrera');

        return [
            'codigo' => [
                'required',
                'string',
                'max:20',
                Rule::unique(Carrera::class)->ignore($carrera?->id),
            ],
            'nombre' => ['required', 'string', 'max:255'],
            'facultad' => ['nullable', 'string', 'max:255'],
            'activo' => ['boolean'],
        ];
    }
}
