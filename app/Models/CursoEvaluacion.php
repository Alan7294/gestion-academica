<?php

namespace App\Models;

use Database\Factories\CursoEvaluacionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['curso_id', 'nombre', 'ponderacion'])]
class CursoEvaluacion extends Model
{
    /** @use HasFactory<CursoEvaluacionFactory> */
    use HasFactory;

    protected $table = 'curso_evaluaciones';

    protected function casts(): array
    {
        return [
            'ponderacion' => 'decimal:2',
        ];
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function calificaciones(): HasMany
    {
        return $this->hasMany(Calificacion::class);
    }
}
