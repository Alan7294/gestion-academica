<?php

namespace App\Http\Requests;

use App\Models\Periodo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePeriodoRequest extends FormRequest
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
        $periodo = $this->route('periodo');

        return [
            'nombre' => [
                'required',
                'string',
                'max:50',
                Rule::unique(Periodo::class)->ignore($periodo?->id),
            ],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after:fecha_inicio'],
            'activo' => ['boolean'],
        ];
    }
}
