<?php

namespace App\Reports\Formatos;

use App\Reports\Reporte;
use Symfony\Component\HttpFoundation\Response;

/**
 * Estrategia de presentación de un reporte (RF12).
 */
interface FormatoReporte
{
    public function extension(): string;

    public function mime(): string;

    public function generar(Reporte $reporte): Response;
}
