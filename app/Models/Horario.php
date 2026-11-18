<?php

namespace App\Models;

use App\Enums\DiaSemana;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['curso_id', 'docente_id', 'dia_semana', 'hora_inicio', 'hora_fin', 'aula'])]
class Horario extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'dia_semana' => DiaSemana::class,
        ];
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'docente_id');
    }

    public function getRangoTextoAttribute(): string
    {
        return sprintf('%s - %s', substr($this->hora_inicio, 0, 5), substr($this->hora_fin, 0, 5));
    }
}
