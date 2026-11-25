<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['carrera_id', 'codigo', 'nombre'])]
class Materia extends Model
{
    use HasFactory;

    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class);
    }

    /**
     * Planes que incluyen esta materia, con el semestre y los créditos propios de cada plan.
     */
    public function planesEstudio(): BelongsToMany
    {
        return $this->belongsToMany(PlanEstudio::class, 'materia_plan_estudio')
            ->withPivot(['semestre', 'creditos'])
            ->withTimestamps();
    }

    public function cursos(): HasMany
    {
        return $this->hasMany(Curso::class);
    }

    /**
     * Materias que deben aprobarse antes de cursar esta.
     */
    public function requisitos(): BelongsToMany
    {
        return $this->belongsToMany(Materia::class, 'materia_requisitos', 'materia_id', 'requisito_id')
            ->withTimestamps();
    }

    /**
     * Requisitos que este plan declara para la materia.
     *
     * Se limita al plan mediante el valor del pivote, de modo que las
     * operaciones de lectura y de sincronización solo afecten a ese plan.
     */
    public function requisitosDelPlan(PlanEstudio $plan): BelongsToMany
    {
        return $this->requisitos()
            ->withPivotValue('plan_estudio_id', $plan->id);
    }

    /**
     * Materias que tienen esta materia como requisito.
     */
    public function requeridaPor(): BelongsToMany
    {
        return $this->belongsToMany(Materia::class, 'materia_requisitos', 'requisito_id', 'materia_id')
            ->withTimestamps();
    }

    /**
     * @param  Builder<Materia>  $query
     */
    public function scopeDeCarrera(Builder $query, int $carreraId): void
    {
        $query->where('carrera_id', $carreraId);
    }
}
