<?php

namespace App\Models;

use App\Enums\Rol;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'nombres', 'primer_apellido', 'segundo_apellido', 'ci', 'telefono', 'email', 'password', 'activo'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    /**
     * El nombre completo se deriva de los campos separados para conservar una sola columna en reportes y listados.
     */
    protected static function booted(): void
    {
        static::saving(function (User $usuario): void {
            if (! filled($usuario->nombres) || ! filled($usuario->primer_apellido)) {
                return;
            }

            $usuario->name = Str::squish(implode(' ', array_filter([
                $usuario->nombres,
                $usuario->primer_apellido,
                $usuario->segundo_apellido,
            ], fn (?string $valor): bool => filled($valor))));
        });
    }

    public function certificadosDocentes(): HasMany
    {
        return $this->hasMany(CertificadoDocente::class);
    }

    public function matriculas(): HasMany
    {
        return $this->hasMany(Matricula::class);
    }

    /**
     * @return Collection<int, Matricula>
     */
    public function carrerasActivas(): Collection
    {
        return $this->matriculas()->activas()->with('carrera')->get();
    }

    public function carreraPrincipal(): ?Carrera
    {
        return $this->matriculas()
            ->activas()
            ->where('es_principal', true)
            ->with('carrera')
            ->first()
            ?->carrera;
    }

    public function estaMatriculadoEn(int $carreraId): bool
    {
        return $this->matriculas()
            ->activas()
            ->where('carrera_id', $carreraId)
            ->exists();
    }

    public function esAdministrador(): bool
    {
        return $this->hasRole(Rol::Administrador->value);
    }

    public function esDocente(): bool
    {
        return $this->hasRole(Rol::Docente->value);
    }

    public function esEstudiante(): bool
    {
        return $this->hasRole(Rol::Estudiante->value);
    }

    public function rolPrincipal(): ?Rol
    {
        foreach (Rol::cases() as $rol) {
            if ($this->hasRole($rol->value)) {
                return $rol;
            }
        }

        return null;
    }
}
