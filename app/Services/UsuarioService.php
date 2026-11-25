<?php

namespace App\Services;

use App\Enums\Rol;
use App\Models\Calificacion;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UsuarioService
{
    public function __construct(private readonly MatriculaService $matriculas) {}

    /**
     * RF09: crea un usuario desde el panel y le asigna un rol.
     *
     * @param  array{name: string, ci: string, telefono?: string|null, email: string, password: string, rol: string, activo?: bool, matriculas?: array<int, array{carrera_id: int, modalidad?: string|null, es_principal?: bool, plan_estudio_id?: int|null}>, certificados?: array<int, array{titulo: string, institucion: string, codigo: string}>}  $datos
     */
    public function crear(array $datos): User
    {
        return DB::transaction(function () use ($datos) {
            $usuario = User::create([
                'name' => $datos['name'],
                'ci' => $datos['ci'],
                'telefono' => $datos['telefono'] ?? null,
                'email' => $datos['email'],
                'password' => $datos['password'],
                'activo' => $datos['activo'] ?? true,
            ]);

            $usuario->syncRoles([$datos['rol']]);

            if ($datos['rol'] === Rol::Estudiante->value) {
                $this->matriculas->sincronizar($usuario, $datos['matriculas'] ?? []);
            }

            if ($datos['rol'] === Rol::Docente->value) {
                $this->sincronizarCertificados($usuario, $datos['certificados'] ?? []);
            }

            return $usuario;
        });
    }

    /**
     * RF09: actualiza los datos y el rol sin dejar el sistema sin administradores.
     *
     * @param  array{name: string, ci: string, telefono?: string|null, email: string, password?: string|null, rol: string, activo?: bool, matriculas?: array<int, array{carrera_id: int, modalidad?: string|null, es_principal?: bool, plan_estudio_id?: int|null}>, certificados?: array<int, array{titulo: string, institucion: string, codigo: string}>}  $datos
     *
     * @throws ValidationException
     */
    public function actualizar(User $usuario, array $datos): User
    {
        $this->verificarUltimoAdministrador(
            $usuario,
            $datos['rol'],
            (bool) ($datos['activo'] ?? true),
        );

        return DB::transaction(function () use ($usuario, $datos) {
            $atributos = [
                'name' => $datos['name'],
                'ci' => $datos['ci'],
                'telefono' => $datos['telefono'] ?? null,
                'email' => $datos['email'],
                'activo' => $datos['activo'] ?? true,
            ];

            if (! empty($datos['password'])) {
                $atributos['password'] = $datos['password'];
            }

            $usuario->update($atributos);
            $usuario->syncRoles([$datos['rol']]);

            $this->matriculas->sincronizar(
                $usuario,
                $datos['rol'] === Rol::Estudiante->value ? ($datos['matriculas'] ?? []) : [],
            );

            if ($datos['rol'] === Rol::Docente->value) {
                $this->sincronizarCertificados($usuario, $datos['certificados'] ?? []);
            } else {
                $usuario->certificadosDocentes()->delete();
            }

            return $usuario->refresh();
        });
    }

    /**
     * RF09: elimina un usuario con protección de la cuenta propia y del historial académico.
     *
     * @throws AuthorizationException|ValidationException
     */
    public function eliminar(User $usuario, User $actor): void
    {
        if ($usuario->is($actor)) {
            throw new AuthorizationException('No puedes eliminar tu propia cuenta.');
        }

        if ($usuario->esAdministrador() && $this->esUltimoAdministrador()) {
            throw ValidationException::withMessages([
                'usuario' => 'No puedes eliminar al último administrador del sistema.',
            ]);
        }

        if (Calificacion::where('registrado_por', $usuario->id)->exists()) {
            throw ValidationException::withMessages([
                'usuario' => 'El usuario tiene calificaciones registradas. Desactívalo en lugar de eliminarlo para conservar el historial.',
            ]);
        }

        $usuario->delete();
    }

    /**
     * @throws ValidationException
     */
    private function verificarUltimoAdministrador(User $usuario, string $rolNuevo, bool $activo): void
    {
        if (! $usuario->esAdministrador() || ! $this->esUltimoAdministrador()) {
            return;
        }

        if ($rolNuevo !== Rol::Administrador->value) {
            throw ValidationException::withMessages([
                'rol' => 'No puedes quitar el rol de administrador al último administrador del sistema.',
            ]);
        }

        if (! $activo) {
            throw ValidationException::withMessages([
                'activo' => 'No puedes desactivar al último administrador del sistema.',
            ]);
        }
    }

    private function esUltimoAdministrador(): bool
    {
        return User::role(Rol::Administrador->value)->count() <= 1;
    }

    /**
     * @param  array<int, array{titulo: string, institucion: string, codigo: string}>  $certificados
     */
    private function sincronizarCertificados(User $usuario, array $certificados): void
    {
        $usuario->certificadosDocentes()->delete();

        foreach ($certificados as $certificado) {
            $usuario->certificadosDocentes()->create($certificado);
        }
    }
}
