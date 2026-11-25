<?php

namespace App\Http\Requests;

use App\Enums\DiaSemana;
use App\Enums\Modalidad;
use App\Enums\Rol;
use App\Models\PlanEstudio;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
        $creando = $this->isMethod('POST');
        $semestral = $creando && $this->input('modalidad') === Modalidad::Semestral->value;
        $modular = $creando && $this->input('modalidad') === Modalidad::Modular->value;

        return [
            'carrera_id' => ['required', 'integer', Rule::exists('carreras', 'id')],
            'plan_estudio_id' => [
                'required',
                'integer',
                Rule::exists('planes_estudio', 'id')
                    ->where(fn ($query) => $query->where('carrera_id', $this->input('carrera_id'))),
            ],
            'materia_id' => [
                'required',
                'integer',
                Rule::exists('materia_plan_estudio', 'materia_id')
                    ->where(fn ($query) => $query->where('plan_estudio_id', $this->input('plan_estudio_id'))),
            ],
            'modalidad' => ['required', Rule::enum(Modalidad::class)],
            'periodo_id' => ['required', 'integer', Rule::exists('periodos', 'id')],
            'docente_id' => array_merge(
                [$creando ? 'required' : 'nullable', 'integer'],
                [Rule::exists('users', 'id')->where(function (Builder $query) {
                    $query->whereIn('id', function ($sub) {
                        $sub->select('model_id')
                            ->from('model_has_roles')
                            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                            ->where('model_has_roles.model_type', User::class)
                            ->where('roles.name', Rol::Docente->value);
                    });
                })],
            ),
            'cupo' => ['required', 'integer', 'min:0', 'max:500'],
            'activo' => ['boolean'],

            'aula' => $creando
                ? ['required', 'string', 'max:50']
                : ['sometimes', 'nullable', 'string', 'max:50'],
            'horarios' => $semestral
                ? ['required', 'array', 'min:1']
                : ['sometimes', 'array'],
            'horarios.*.dia_semana' => $semestral
                ? ['required', 'integer', 'distinct', Rule::enum(DiaSemana::class)]
                : ['sometimes', 'integer', Rule::enum(DiaSemana::class)],
            'horarios.*.hora_inicio' => $semestral
                ? ['required', 'date_format:H:i']
                : ['sometimes', 'date_format:H:i'],
            'horarios.*.hora_fin' => $semestral
                ? ['required', 'date_format:H:i']
                : ['sometimes', 'date_format:H:i'],
            'fecha_inicio' => $modular
                ? ['required', 'date_format:Y-m-d']
                : ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'hora_inicio' => $modular
                ? ['required', 'date_format:H:i']
                : ['sometimes', 'nullable', 'date_format:H:i'],
            'hora_fin' => $modular
                ? ['required', 'date_format:H:i', 'after:hora_inicio']
                : ['sometimes', 'nullable', 'date_format:H:i'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $planId = $this->input('plan_estudio_id');
                $modalidad = $this->input('modalidad');

                if (! $planId || ! $modalidad) {
                    return;
                }

                $modalidadDelPlan = PlanEstudio::find($planId)?->modalidad;

                if ($modalidadDelPlan && $modalidadDelPlan->value !== $modalidad) {
                    $validator->errors()->add(
                        'modalidad',
                        'La modalidad debe coincidir con la del plan seleccionado.',
                    );
                }
            },
            function (Validator $validator): void {
                if (! $this->isMethod('POST') || $this->input('modalidad') !== Modalidad::Semestral->value) {
                    return;
                }

                foreach ($this->input('horarios', []) as $indice => $horario) {
                    $inicio = $horario['hora_inicio'] ?? null;
                    $fin = $horario['hora_fin'] ?? null;

                    if ($inicio && $fin && $fin <= $inicio) {
                        $validator->errors()->add(
                            "horarios.{$indice}.hora_fin",
                            'La hora de fin debe ser posterior a la hora de inicio.',
                        );
                    }
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
            'docente_id.required' => 'Selecciona el docente del curso.',
            'docente_id.exists' => 'El usuario seleccionado no tiene el rol de docente.',
            'modalidad.required' => 'Selecciona la modalidad del curso.',
            'modalidad.enum' => 'Selecciona una modalidad válida.',
            'plan_estudio_id.required' => 'Selecciona el plan de estudios del curso.',
            'plan_estudio_id.exists' => 'El plan seleccionado no pertenece a la carrera elegida.',
            'materia_id.required' => 'Selecciona la materia que corresponde a este curso.',
            'materia_id.exists' => 'La materia seleccionada no pertenece al plan elegido.',
            'aula.required' => 'Indica el aula donde se dictará el curso.',
            'horarios.required' => 'Indica al menos un día con su horario.',
            'horarios.min' => 'Indica al menos un día con su horario.',
            'horarios.*.dia_semana.required' => 'Selecciona el día de la semana.',
            'horarios.*.dia_semana.distinct' => 'No puedes repetir el mismo día en el horario semestral.',
            'horarios.*.dia_semana.enum' => 'Selecciona un día válido.',
            'horarios.*.hora_inicio.required' => 'Indica la hora de inicio.',
            'horarios.*.hora_fin.required' => 'Indica la hora de fin.',
            'fecha_inicio.required' => 'Indica la fecha de inicio del módulo.',
            'fecha_inicio.date_format' => 'La fecha de inicio debe tener el formato AAAA-MM-DD.',
            'hora_inicio.required' => 'Indica la hora de inicio.',
            'hora_fin.required' => 'Indica la hora de fin.',
            'hora_fin.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
        ];
    }
}
