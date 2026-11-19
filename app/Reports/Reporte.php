<?php

namespace App\Reports;

/**
 * Contrato de un reporte tabular del sistema (RF12).
 *
 * Cualquier reporte se define con un título, encabezados, filas y un contexto
 * opcional. Los formatos (Excel/PDF) se encargan de presentarlo.
 */
interface Reporte
{
    public function titulo(): string;

    public function descripcion(): string;

    /**
     * @return array<int, string>
     */
    public function encabezados(): array;

    /**
     * @return array<int, array<int, string|int|float|null>>
     */
    public function filas(): array;

    /**
     * Datos de contexto que se muestran en el encabezado del reporte.
     *
     * @return array<string, string>
     */
    public function contexto(): array;
}
