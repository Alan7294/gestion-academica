<?php

namespace App\Services;

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
        public float $nota,
        public Carbon $fecha,
        public ?int $creditos,
    ) {}

    public function origen(): string
    {
        return "{$this->equivalente->codigo} - {$this->equivalente->nombre} ({$this->equivalente->carrera->nombre})";
    }
}
