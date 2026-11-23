<?php

namespace App\Models;

use App\Enums\Modalidad;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public function materias(): HasMany
    {
        return $this->hasMany(Materia::class)
            ->orderBy('semestre')
            ->orderBy('nombre');
    }

    public function totalCreditos(): int
    {
        return (int) $this->materias->sum('creditos');
    }

    /**
     * Materias agrupadas por número de semestre.
     *
     * @return Collection<int, Collection<int, Materia>>
     */
    public function materiasPorSemestre(): Collection
    {
        return $this->materias->groupBy('semestre');
    }

    public function scopeActivo(Builder $query): void
    {
        $query->where('activo', true);
    }
}
