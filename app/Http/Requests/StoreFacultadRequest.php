<?php

namespace App\Http\Requests;

use App\Models\Facultad;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFacultadRequest extends FormRequest
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
        $facultad = $this->route('facultad');

        return [
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Facultad::class)->ignore($facultad?->id),
            ],
            'activo' => ['boolean'],
        ];
    }
}
