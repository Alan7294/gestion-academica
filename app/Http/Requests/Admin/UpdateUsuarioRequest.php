<?php

namespace App\Http\Requests\Admin;

use App\Enums\Modalidad;
use App\Enums\Rol;
use App\Models\User;
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
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User|null $usuario */
        $usuario = $this->route('usuario');

        return [
            'name' => ['required', 'string', 'max:255'],
            'ci' => [
                'required',
                'string',
                'max:20',
                Rule::unique('users', 'ci')->ignore($usuario?->id),
            ],
            'telefono' => ['nullable', 'string', 'max:30'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($usuario?->id),
            ],
            // En la edición la contraseña es opcional.
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'rol' => ['required', Rule::enum(Rol::class)],
            'carrera_id' => [
                'nullable',
                'required_if:rol,'.Rol::Estudiante->value,
                'integer',
                Rule::exists('carreras', 'id'),
            ],
            'modalidad' => [
                'nullable',
                'required_if:rol,'.Rol::Estudiante->value,
                Rule::enum(Modalidad::class),
            ],
            'activo' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ci.unique' => 'Ya existe un usuario registrado con ese carnet de identidad.',
            'email.unique' => 'Ya existe un usuario registrado con ese correo.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'carrera_id.required_if' => 'Selecciona la carrera del estudiante.',
            'modalidad.required_if' => 'Selecciona la modalidad del estudiante.',
        ];
    }
}
