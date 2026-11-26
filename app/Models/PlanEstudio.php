<?php

namespace App\Models;

use App\Enums\Modalidad;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

#[Fillable(['carrera_id', 'nombre', 'modalidad', 'semestres', 'activo'])]
class PlanEstudio extends Model
{
    use HasFactory;

    /** El plural de PlanEstudio no coincide con el nombre de la tabla. */
    protected $table = 'planes_estudio';

    protected function casts(): array
    {
        return [
            'modalidad' => Modalidad::class,
            'semestres' => 'integer',
            'activo' => 'boolean',
        ];
    }

    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class);
    }

    /**
     * Materias del catálogo incluidas en el plan, con el semestre asignado.
     */
    public function materias(): BelongsToMany
    {
        return $this->belongsToMany(Materia::class, 'materia_plan_estudio')
            ->withPivot(['semestre'])
            ->withTimestamps()
            ->orderBy('materia_plan_estudio.semestre')
            ->orderBy('materias.nombre');
    }

    /**
     * Materias agrupadas por el semestre asignado dentro del plan.
     *
     * @return Collection<int, Collection<int, Materia>>
     */
    public function materiasPorSemestre(): Collection
    {
        return $this->materias->groupBy(fn (Materia $materia) => $materia->pivot->semestre);
    }

    /**
     * Vincula una materia del catálogo al plan sin duplicarla ni crear otra.
     */
    public function vincularMateria(Materia $materia, int $semestre): void
    {
        $this->materias()->syncWithoutDetaching([
            $materia->id => ['semestre' => $semestre],
        ]);
    }

    /**
     * @param  Builder<PlanEstudio>  $query
     */
    public function scopeActivo(Builder $query): void
    {
        $query->where('activo', true);
    }
}
