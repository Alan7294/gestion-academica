<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

#[Fillable(['codigo', 'nombre', 'facultad_id', 'activo'])]
class Carrera extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function facultad(): BelongsTo
    {
        return $this->belongsTo(Facultad::class);
    }

    public function cursos(): HasMany
    {
        return $this->hasMany(Curso::class);
    }

    public function planesEstudio(): HasMany
    {
        return $this->hasMany(PlanEstudio::class);
    }

    public function matriculas(): HasMany
    {
        return $this->hasMany(Matricula::class);
    }

    public function estudiantes(): HasManyThrough
    {
        return $this->hasManyThrough(User::class, Matricula::class, 'carrera_id', 'id', 'id', 'user_id');
    }
}
