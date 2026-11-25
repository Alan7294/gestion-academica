<?php

namespace App\Services;

use App\Models\Carrera;
use App\Models\Materia;
use Illuminate\Support\Carbon;

/**
 * Convalidación derivada de una aprobación en otra carrera.
 */
final readonly class Convalidacion
{
    public function __construct(
        public Materia $materia,
        public Materia $equivalente,
        public ?Carrera $carreraDestino,
        public ?Carrera $carreraOrigen,
        public float $nota,
        public Carbon $fecha,
        public ?int $creditos,
    ) {}

    public function origen(): string
    {
        $carrera = $this->carreraOrigen?->nombre ?? '—';

        return "{$this->equivalente->codigo} - {$this->equivalente->nombre} ({$carrera})";
    }
}
