<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Modalidad;
use App\Enums\Rol;
use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register', [
            'roles' => Rol::autoregistrables(),
            'carreras' => Carrera::query()->where('activo', true)->orderBy('nombre')->get(),
            'modalidades' => Modalidad::cases(),
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'ci' => ['nullable', 'string', 'max:20', 'unique:'.User::class],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'rol' => ['required', Rule::in(Rol::valoresAutoregistrables())],
            // La carrera y la modalidad son obligatorias solo para estudiantes.
            'carrera_id' => [
                'nullable',
                'required_if:rol,'.Rol::Estudiante->value,
                'integer',
                Rule::exists('carreras', 'id')->where('activo', true),
            ],
            'modalidad' => [
                'nullable',
                'required_if:rol,'.Rol::Estudiante->value,
                Rule::enum(Modalidad::class),
            ],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'carrera_id.required_if' => 'Selecciona la carrera a la que te inscribes.',
            'carrera_id.exists' => 'La carrera seleccionada no está disponible.',
            'modalidad.required_if' => 'Selecciona la modalidad (modular o semestral).',
        ]);

        $esEstudiante = $datos['rol'] === Rol::Estudiante->value;

        $user = User::create([
            'name' => $datos['name'],
            'ci' => $datos['ci'] ?? null,
            'email' => $datos['email'],
            'password' => $datos['password'],
            'carrera_id' => $esEstudiante ? $datos['carrera_id'] : null,
            'modalidad' => $esEstudiante ? $datos['modalidad'] : null,
        ]);

        $user->assignRole($datos['rol']);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
