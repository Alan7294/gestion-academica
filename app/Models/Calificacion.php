<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['inscripcion_id', 'curso_evaluacion_id', 'registrado_por', 'nota', 'fecha'])]
class Calificacion extends Model
{
    use HasFactory;

    protected $table = 'calificaciones';

    protected function casts(): array
    {
        return [
            'nota' => 'decimal:2',
            'fecha' => 'date',
        ];
    }

    public function inscripcion(): BelongsTo
    {
        return $this->belongsTo(Inscripcion::class);
    }

    public function evaluacion(): BelongsTo
    {
        return $this->belongsTo(CursoEvaluacion::class, 'curso_evaluacion_id');
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
