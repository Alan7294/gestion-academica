<?php

namespace App\Models;

use App\Enums\EstadoInscripcion;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['curso_id', 'user_id', 'estado', 'inscrito_en', 'nota_final'])]
class Inscripcion extends Model
{
    use HasFactory;

    protected $table = 'inscripciones';

    protected function casts(): array
    {
        return [
            'estado' => EstadoInscripcion::class,
            'inscrito_en' => 'datetime',
            'nota_final' => 'decimal:2',
        ];
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeActivas(Builder $query): void
    {
        $query->where('estado', EstadoInscripcion::Inscrito->value);
    }
}
