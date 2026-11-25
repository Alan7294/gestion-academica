<?php

namespace App\Models;

use App\Enums\DiaSemana;
use App\Enums\Modalidad;
use App\Enums\Rol;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'carrera_id',
    'plan_estudio_id',
    'modalidad',
    'materia_id',
    'periodo_id',
    'docente_id',
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
            'modalidad' => Modalidad::class,
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

    /**
     * Plan que aporta el semestre y los créditos de la materia del curso.
     */
    public function planEstudio(): BelongsTo
    {
        return $this->belongsTo(PlanEstudio::class);
    }

    /**
     * Planes habilitados para inscribirse en este curso. Todos comparten el mismo cupo.
     */
    public function planesEstudio(): BelongsToMany
    {
        return $this->belongsToMany(PlanEstudio::class, 'curso_plan_estudio')
            ->withTimestamps();
    }

    public function materia(): BelongsTo
    {
        return $this->belongsTo(Materia::class);
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

    public function evaluaciones(): HasMany
    {
        return $this->hasMany(CursoEvaluacion::class)->orderBy('id');
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
     * Resumen del horario para listados: días, franjas y aulas sin repetir.
     * Los módulos tienen veinte sesiones, pero muestran una sola vez cada dato.
     *
     * @return array{dias: string, horario: string, aula: string}
     */
    public function resumenHorarios(): array
    {
        $horarios = $this->horarios;

        $dias = $horarios
            ->pluck('dia_semana')
            ->unique(fn (DiaSemana $dia) => $dia->value)
            ->sortBy(fn (DiaSemana $dia) => $dia->value)
            ->map(fn (DiaSemana $dia) => $dia->etiqueta())
            ->implode(', ');

        $franjas = $horarios
            ->map(fn (Horario $horario) => $horario->rango_texto)
            ->unique()
            ->implode(', ');

        $aulas = $horarios
            ->pluck('aula')
            ->filter()
            ->unique()
            ->implode(', ');

        return [
            'dias' => $dias !== '' ? $dias : '—',
            'horario' => $franjas !== '' ? $franjas : '—',
            'aula' => $aulas !== '' ? $aulas : '—',
        ];
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

    /**
     * Devuelve la matrícula activa cuyo plan está autorizado en el curso.
     * Si hay varias, gana la matrícula principal para no mezclar planes distintos.
     */
    public function matriculaAutorizadaPara(User $estudiante): ?Matricula
    {
        return $estudiante->matriculas()
            ->activas()
            ->whereNotNull('plan_estudio_id')
            ->orderByDesc('es_principal')
            ->orderBy('id')
            ->get()
            ->first(fn (Matricula $matricula) => $this->planesEstudio
                ->contains('id', $matricula->plan_estudio_id));
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
     * @return Collection<int, User>
     */
    public static function docentesDisponibles()
    {
        return User::role(Rol::Docente->value)->orderBy('name')->get();
    }
}
