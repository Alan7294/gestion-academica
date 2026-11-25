<?php

namespace App\Models;

use Database\Factories\MateriaEquivalenciaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['materia_id', 'equivalente_id'])]
class MateriaEquivalencia extends Model
{
    /** @use HasFactory<MateriaEquivalenciaFactory> */
    use HasFactory;

    protected $table = 'materia_equivalencias';

    /**
     * Materia de la carrera destino que reconoce la equivalencia.
     */
    public function materia(): BelongsTo
    {
        return $this->belongsTo(Materia::class);
    }

    /**
     * Materia de otra carrera cuya aprobación se reconoce.
     */
    public function equivalente(): BelongsTo
    {
        return $this->belongsTo(Materia::class, 'equivalente_id');
    }
}
