<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCalificacionRequest extends FormRequest
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
            'nombre' => ['required', 'string', 'max:100'],
            'ponderacion' => ['required', 'integer', 'min:0', 'max:100'],
            'nota' => ['required', 'numeric', 'min:0', 'max:100'],
            'fecha' => ['required', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nota.min' => 'La nota debe estar entre 0 y 100.',
            'nota.max' => 'La nota debe estar entre 0 y 100.',
        ];
    }
}
