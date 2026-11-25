<?php

namespace App\Models;

use App\Enums\EstadoMatricula;
use App\Enums\Modalidad;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'carrera_id',
    'plan_estudio_id',
    'estado',
    'es_principal',
    'modalidad',
    'fecha_inicio',
    'fecha_fin',
])]
class Matricula extends Model
{
    use HasFactory;

    protected $table = 'carrera_user';

    protected function casts(): array
    {
        return [
            'estado' => EstadoMatricula::class,
            'es_principal' => 'boolean',
            'modalidad' => Modalidad::class,
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
        ];
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class);
    }

    public function planEstudio(): BelongsTo
    {
        return $this->belongsTo(PlanEstudio::class);
    }

    public function scopeActivas(Builder $query): void
    {
        $query->where('estado', EstadoMatricula::Activa->value);
    }

    public function estaActiva(): bool
    {
        return $this->estado === EstadoMatricula::Activa;
    }
}
