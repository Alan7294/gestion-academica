<?php

namespace App\Http\Requests\Admin;

use App\Enums\Modalidad;
use App\Enums\Rol;
use App\Models\PlanEstudio;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->esAdministrador() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['activo' => $this->boolean('activo')]);
        $this->merge(['matriculas' => $this->matriculasNormalizadas()]);
        $this->merge(['certificados' => $this->certificadosNormalizados()]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User|null $usuario */
        $usuario = $this->route('usuario');

        return [
            'name' => ['required', 'string', 'max:255', 'regex:/^[\p{L} ]+$/u'],
            'ci' => [
                'required',
                'string',
                'min:6',
                'max:20',
                'regex:/^\d+$/',
                Rule::unique('users', 'ci')->ignore($usuario?->id),
            ],
            'telefono' => ['nullable', 'string', 'min:8', 'max:30', 'regex:/^\d+$/'],
            'email' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $mensaje = $this->mensajeCorreoInvalido($value);

                    if ($mensaje !== null) {
                        $fail($mensaje);
                    }
                },
                Rule::unique('users', 'email')->ignore($usuario?->id),
            ],
            // En la edición la contraseña es opcional.
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'rol' => ['required', Rule::enum(Rol::class)],
            'matriculas' => [
                'nullable',
                Rule::requiredIf($this->input('rol') === Rol::Estudiante->value),
                'array',
                'max:10',
            ],
            'matriculas.*.carrera_id' => [
                'required',
                'integer',
                Rule::exists('carreras', 'id'),
                'distinct',
            ],
            'matriculas.*.modalidad' => [
                'nullable',
                Rule::requiredIf($this->input('rol') === Rol::Estudiante->value),
                Rule::enum(Modalidad::class),
            ],
            'matriculas.*.plan_estudio_id' => ['nullable', 'integer', Rule::exists('planes_estudio', 'id')],
            'matriculas.*.es_principal' => ['nullable', 'boolean'],
            'certificados' => ['nullable', 'array', 'max:20'],
            'certificados.*.titulo' => ['required', 'string', 'max:255'],
            'certificados.*.institucion' => ['required', 'string', 'max:255'],
            'certificados.*.codigo' => ['required', 'string', 'max:100'],
            'activo' => ['boolean'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->input('rol') !== Rol::Estudiante->value) {
                    return;
                }

                $principales = collect($this->input('matriculas', []))
                    ->filter(fn ($matricula) => (bool) ($matricula['es_principal'] ?? false))
                    ->count();

                if ($principales !== 1) {
                    $validator->errors()->add('matriculas', 'Marca una sola carrera como principal.');
                }
            },

            function (Validator $validator): void {
                $planes = PlanEstudio::query()
                    ->whereIn('id', collect($this->input('matriculas', []))
                        ->pluck('plan_estudio_id')
                        ->filter(fn ($id) => is_numeric($id))
                        ->all())
                    ->pluck('carrera_id', 'id');

                foreach ($this->input('matriculas', []) as $indice => $matricula) {
                    $planId = $matricula['plan_estudio_id'] ?? null;

                    if (! is_numeric($planId)) {
                        continue;
                    }

                    $carreraDelPlan = $planes->get((int) $planId);

                    if ($carreraDelPlan === null) {
                        continue;
                    }

                    if ((int) $carreraDelPlan !== (int) ($matricula['carrera_id'] ?? 0)) {
                        $validator->errors()->add(
                            "matriculas.{$indice}.plan_estudio_id",
                            'El plan de estudios no pertenece a la carrera seleccionada.',
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
            'name.regex' => 'El nombre solo puede contener letras.',
            'ci.unique' => 'Ya existe un usuario registrado con ese carnet de identidad.',
            'ci.min' => 'El carnet de identidad debe tener al menos 6 dígitos.',
            'ci.regex' => 'El carnet de identidad solo puede contener números.',
            'telefono.min' => 'El teléfono debe tener al menos 8 dígitos.',
            'telefono.regex' => 'El teléfono solo puede contener números.',
            'email.unique' => 'Ya existe un usuario registrado con ese correo.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'matriculas.required' => 'Selecciona al menos una carrera para el estudiante.',
            'matriculas.min' => 'Selecciona al menos una carrera para el estudiante.',
            'matriculas.*.carrera_id.required' => 'Selecciona la carrera del estudiante.',
            'matriculas.*.carrera_id.distinct' => 'No puedes repetir la misma carrera.',
            'matriculas.*.modalidad.required' => 'Selecciona la modalidad de la carrera.',
            'certificados.*.titulo.required' => 'Escribe el título del certificado.',
            'certificados.*.institucion.required' => 'Indica dónde obtuvo el certificado.',
            'certificados.*.codigo.required' => 'Indica el código del certificado.',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function matriculasNormalizadas(): array
    {
        return collect($this->input('matriculas', []))
            ->filter(fn ($matricula) => is_array($matricula) && filled($matricula['carrera_id'] ?? null))
            ->map(fn (array $matricula, int $indice) => [
                'carrera_id' => $matricula['carrera_id'] ?? null,
                'modalidad' => $matricula['modalidad'] ?? null,
                'plan_estudio_id' => $matricula['plan_estudio_id'] ?? null,
                'es_principal' => $this->boolean("matriculas.{$indice}.es_principal"),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function certificadosNormalizados(): array
    {
        return collect($this->input('certificados', []))
            ->filter(fn ($certificado) => is_array($certificado) && (
                filled($certificado['titulo'] ?? null)
                || filled($certificado['institucion'] ?? null)
                || filled($certificado['codigo'] ?? null)
            ))
            ->map(fn (array $certificado) => [
                'titulo' => $certificado['titulo'] ?? null,
                'institucion' => $certificado['institucion'] ?? null,
                'codigo' => $certificado['codigo'] ?? null,
            ])
            ->values()
            ->all();
    }

    private function mensajeCorreoInvalido(mixed $correo): ?string
    {
        // Los campos vacíos ya son cubiertos por la regla required.
        if (! is_string($correo) || trim($correo) === '') {
            return null;
        }

        if (substr_count($correo, '@') !== 1) {
            return 'Escribe el correo con un solo @, por ejemplo nombre@dominio.com.';
        }

        if (preg_match('/\s/', $correo)) {
            return 'El correo no debe contener espacios y debe tener texto antes y después de @.';
        }

        [$usuario, $dominio] = explode('@', $correo);

        if ($usuario === '' || $dominio === '') {
            return 'El correo debe tener texto antes y después de @.';
        }

        if (! str_contains($dominio, '.')) {
            return 'El dominio del correo debe incluir un punto, por ejemplo dominio.com.';
        }

        if (str_starts_with($dominio, '.') || str_ends_with($dominio, '.') || str_contains($dominio, '..')) {
            return 'El dominio del correo no debe empezar ni terminar con punto, ni tener puntos consecutivos.';
        }

        $extension = substr($dominio, strrpos($dominio, '.') + 1);

        if (! preg_match('/^[A-Za-z]{2,}$/', $extension)) {
            return 'Después del punto del dominio deben ir al menos 2 letras, por ejemplo .com.';
        }

        return null;
    }
}
