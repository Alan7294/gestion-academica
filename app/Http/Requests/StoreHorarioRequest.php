<?php

namespace App\Http\Requests;

use App\Enums\DiaSemana;
use App\Enums\Modalidad;
use App\Models\Curso;
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
        $esModular = $this->esModular();

        return [
            'dia_semana' => $esModular
                ? ['prohibited']
                : ['required', 'integer', Rule::enum(DiaSemana::class)],
            'fecha_inicio' => $esModular
                ? ['required', 'date_format:Y-m-d']
                : ['prohibited'],
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
            'dia_semana.prohibited' => 'El día de la semana no aplica a los cursos modulares.',
            'fecha_inicio.required' => 'Indica la fecha de inicio del módulo.',
            'fecha_inicio.date_format' => 'La fecha de inicio debe tener el formato AAAA-MM-DD.',
            'fecha_inicio.prohibited' => 'La fecha de inicio solo aplica a los cursos modulares.',
            'hora_fin.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
        ];
    }

    private function esModular(): bool
    {
        $curso = $this->route('curso');

        return $curso instanceof Curso && $curso->modalidad === Modalidad::Modular;
    }
}
