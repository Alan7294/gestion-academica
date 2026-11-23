<?php

namespace App\Models;

use App\Enums\Modalidad;
use App\Enums\Rol;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'ci', 'telefono', 'email', 'password', 'activo', 'carrera_id', 'modalidad'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
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
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
            'modalidad' => Modalidad::class,
        ];
    }

    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class);
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
