<?php

namespace App\Http\Requests;

use App\Enums\DiaSemana;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHorarioRequest extends FormRequest
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
            'dia_semana' => ['required', 'integer', Rule::enum(DiaSemana::class)],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'aula' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'hora_fin.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
        ];
    }
}
