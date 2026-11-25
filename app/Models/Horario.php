<?php

namespace App\Models;

use App\Enums\DiaSemana;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['curso_id', 'periodo_id', 'docente_id', 'dia_semana', 'hora_inicio', 'hora_fin', 'fecha_inicio', 'fecha_fin', 'fecha_sesion', 'aula'])]
class Horario extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'dia_semana' => DiaSemana::class,
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'fecha_sesion' => 'date',
        ];
    }

    /**
     * Todo horario hereda el periodo del curso; los módulos acotan después su vigencia.
     */
    protected static function booted(): void
    {
        static::creating(function (Horario $horario): void {
            if ($horario->periodo_id !== null && $horario->fecha_inicio !== null && $horario->fecha_fin !== null) {
                return;
            }

            $periodo = $horario->curso->periodo;

            $horario->periodo_id ??= $periodo->id;
            $horario->fecha_inicio ??= $periodo->fecha_inicio->toDateString();
            $horario->fecha_fin ??= $periodo->fecha_fin->toDateString();
        });
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(Periodo::class);
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'docente_id');
    }

    public function getRangoTextoAttribute(): string
    {
        return sprintf('%s - %s', substr($this->hora_inicio, 0, 5), substr($this->hora_fin, 0, 5));
    }

    public function getVigenciaTextoAttribute(): string
    {
        if ($this->fecha_inicio === null || $this->fecha_fin === null) {
            return '—';
        }

        return $this->fecha_inicio->format('d/m/Y').' – '.$this->fecha_fin->format('d/m/Y');
    }

    public function getDescripcionAttribute(): string
    {
        return $this->dia_semana->etiqueta().' '.$this->rango_texto.' · '.$this->vigencia_texto;
    }
}
