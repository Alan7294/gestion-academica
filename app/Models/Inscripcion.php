<?php

namespace App\Models;

use App\Enums\EstadoInscripcion;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['curso_id', 'user_id', 'plan_estudio_id', 'estado', 'inscrito_en', 'nota_final'])]
class Inscripcion extends Model
{
    use HasFactory;

    public const NOTA_MINIMA_APROBATORIA = 51;

    protected $table = 'inscripciones';

    protected function casts(): array
    {
        return [
            'estado' => EstadoInscripcion::class,
            'inscrito_en' => 'datetime',
            'nota_final' => 'decimal:2',
        ];
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    /**
     * Plan vigente para esta inscripción, congelado al momento de inscribirse.
     */
    public function planEstudio(): BelongsTo
    {
        return $this->belongsTo(PlanEstudio::class);
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function calificaciones(): HasMany
    {
        return $this->hasMany(Calificacion::class)->orderBy('fecha');
    }

    public function scopeActivas(Builder $query): void
    {
        $query->where('estado', EstadoInscripcion::Inscrito->value);
    }

    /**
     * Una materia se considera aprobada cuando tiene nota final y supera el umbral.
     */
    public function estaAprobada(): bool
    {
        return $this->nota_final !== null
            && (float) $this->nota_final >= self::NOTA_MINIMA_APROBATORIA;
    }

    /**
     * Créditos del plan con el que se cursó la materia. Si no quedó registrado,
     * se usa el valor del curso, que conserva el plan anfitrión.
     */
    public function creditosSegunPlan(): int
    {
        $plan = $this->relationLoaded('planEstudio')
            ? $this->planEstudio
            : $this->planEstudio()->with('materias')->first();

        if ($plan === null || $this->curso->materia_id === null) {
            return (int) $this->curso->creditos;
        }

        $materias = $plan->relationLoaded('materias')
            ? $plan->materias
            : $plan->materias()->whereKey($this->curso->materia_id)->get();
        $materia = $materias->firstWhere('id', $this->curso->materia_id);

        return $materia?->pivot?->creditos === null
            ? (int) $this->curso->creditos
            : (int) $materia->pivot->creditos;
    }

    /**
     * Promedio ponderado de las evaluaciones registradas.
     */
    public function promedioPonderado(): ?float
    {
        $calificaciones = $this->relationLoaded('calificaciones')
            ? $this->calificaciones
            : $this->calificaciones()->with('evaluacion')->get();

        $calificaciones->loadMissing('evaluacion');

        $ponderacion = $calificaciones->sum(fn (Calificacion $calificacion) => (float) $calificacion->evaluacion->ponderacion);

        if ($ponderacion <= 0) {
            return null;
        }

        $acumulado = $calificaciones->sum(
            fn (Calificacion $calificacion) => (float) $calificacion->nota * (float) $calificacion->evaluacion->ponderacion,
        );

        return round($acumulado / $ponderacion, 2);
    }
}
