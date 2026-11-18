<?php

namespace App\Models;

use App\Enums\Rol;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'carrera_id',
    'periodo_id',
    'docente_id',
    'codigo',
    'nombre',
    'creditos',
    'semestre',
    'cupo',
    'activo',
])]
class Curso extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'creditos' => 'integer',
            'semestre' => 'integer',
            'cupo' => 'integer',
            'activo' => 'boolean',
        ];
    }

    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class);
    }

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(Periodo::class);
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'docente_id');
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(Horario::class)->orderBy('dia_semana')->orderBy('hora_inicio');
    }

    public function inscripciones(): HasMany
    {
        return $this->hasMany(Inscripcion::class);
    }

    public function alumnosInscritos(): HasMany
    {
        return $this->hasMany(Inscripcion::class)->where('estado', 'inscrito');
    }

    /**
     * Indica si el curso tiene cupo disponible (cupo 0 = sin límite).
     */
    public function tieneCupoDisponible(): bool
    {
        if ($this->cupo === 0) {
            return true;
        }

        return $this->alumnosInscritos()->count() < $this->cupo;
    }

    public function estaInscrito(User $estudiante): bool
    {
        return $this->alumnosInscritos()->where('user_id', $estudiante->id)->exists();
    }

    public function scopeActivo(Builder $query): void
    {
        $query->where('activo', true);
    }

    public function scopeDelDocente(Builder $query, User $docente): void
    {
        $query->where('docente_id', $docente->id);
    }

    /**
     * Docentes disponibles para asignar a un curso.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    public static function docentesDisponibles()
    {
        return User::role(Rol::Docente->value)->orderBy('name')->get();
    }
}
