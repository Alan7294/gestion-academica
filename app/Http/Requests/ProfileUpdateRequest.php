<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombres' => ['required', 'string', 'max:80', 'regex:/^[\p{L} ]+$/u'],
            'primer_apellido' => ['required', 'string', 'max:80', 'regex:/^[\p{L} ]+$/u'],
            'segundo_apellido' => ['nullable', 'string', 'max:80', 'regex:/^[\p{L} ]+$/u'],
            'ci' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'telefono' => ['nullable', 'string', 'max:30'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombres.regex' => 'Los nombres solo pueden contener letras.',
            'primer_apellido.regex' => 'El primer apellido solo puede contener letras.',
            'segundo_apellido.regex' => 'El segundo apellido solo puede contener letras.',
        ];
    }
}
