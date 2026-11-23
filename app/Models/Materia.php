<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
