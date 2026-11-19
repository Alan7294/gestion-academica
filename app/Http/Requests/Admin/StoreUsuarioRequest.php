<?php

namespace App\Http\Requests\Admin;

use App\Enums\Rol;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUsuarioRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'ci' => ['required', 'string', 'max:20', Rule::unique('users', 'ci')],
            'telefono' => ['nullable', 'string', 'max:30'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::min(8)],
            'rol' => ['required', Rule::enum(Rol::class)],
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
        ];
    }
}
