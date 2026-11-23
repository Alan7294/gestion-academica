<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['plan_estudio_id', 'codigo', 'nombre', 'semestre', 'creditos'])]
class Materia extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'semestre' => 'integer',
            'creditos' => 'integer',
        ];
    }

    public function planEstudio(): BelongsTo
    {
        return $this->belongsTo(PlanEstudio::class);
    }

    /**
     * Materias que deben aprobarse antes de cursar esta.
     */
    public function requisitos(): BelongsToMany
    {
        return $this->belongsToMany(Materia::class, 'materia_requisitos', 'materia_id', 'requisito_id');
    }

    /**
     * Materias que tienen esta materia como requisito.
     */
    public function requeridaPor(): BelongsToMany
    {
        return $this->belongsToMany(Materia::class, 'materia_requisitos', 'requisito_id', 'materia_id');
    }
}
