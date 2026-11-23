<?php

namespace App\Http\Requests;

use App\Models\Curso;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCursoRequest extends FormRequest
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
        /** @var Curso|null $curso */
        $curso = $this->route('curso');

        return [
            'carrera_id' => ['required', 'integer', Rule::exists('carreras', 'id')],
            'materia_id' => [
                'required',
                'integer',
                Rule::exists('materias', 'id')->where(function ($query) {
                    $query->whereIn('plan_estudio_id', function ($sub) {
                        $sub->select('id')
                            ->from('planes_estudio')
                            ->where('carrera_id', $this->input('carrera_id'));
                    });
                }),
            ],
            'periodo_id' => ['required', 'integer', Rule::exists('periodos', 'id')],
            'docente_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function (Builder $query) {
                    $query->whereIn('id', function ($sub) {
                        $sub->select('model_id')
                            ->from('model_has_roles')
                            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                            ->where('roles.name', 'docente');
                    });
                }),
            ],
            'codigo' => [
                'required',
                'string',
                'max:20',
                Rule::unique('cursos', 'codigo')
                    ->where('periodo_id', $this->input('periodo_id'))
                    ->ignore($curso?->id),
            ],
            'nombre' => ['required', 'string', 'max:255'],
            'creditos' => ['required', 'integer', 'min:0', 'max:20'],
            'semestre' => ['required', 'integer', 'min:1', 'max:12'],
            'cupo' => ['required', 'integer', 'min:0', 'max:500'],
            'activo' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'docente_id.exists' => 'El usuario seleccionado no tiene el rol de docente.',
            'materia_id.required' => 'Selecciona la materia del plan que corresponde a este curso.',
            'materia_id.exists' => 'La materia seleccionada no pertenece a la carrera del curso.',
            'codigo.unique' => 'Ya existe un curso con ese código en el periodo seleccionado.',
        ];
    }
}
