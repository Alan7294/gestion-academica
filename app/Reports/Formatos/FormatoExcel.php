<?php

namespace App\Reports\Formatos;

use App\Reports\Reporte;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;

/**
 * Estrategia Excel: genera un archivo .xlsx real con PhpSpreadsheet.
 */
class FormatoExcel implements FormatoReporte
{
    public function extension(): string
    {
        return 'xlsx';
    }

    public function mime(): string
    {
        return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    }

    public function generar(Reporte $reporte): Response
    {
        $libro = new Spreadsheet;
        $hoja = $libro->getActiveSheet();
        $hoja->setTitle($this->tituloHoja($reporte->titulo()));

        $hoja->fromArray($reporte->encabezados(), null, 'A1');
        $ultimaColumna = $hoja->getHighestColumn();

        $rangoEncabezado = "A1:{$ultimaColumna}1";
        $hoja->getStyle($rangoEncabezado)->getFont()->setBold(true);
        $hoja->getStyle($rangoEncabezado)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE5E7EB');
        $hoja->getStyle($rangoEncabezado)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $fila = 2;

        if ($reporte->filas() === []) {
            $hoja->setCellValue('A2', 'Sin datos para mostrar.');
        }

        foreach ($reporte->filas() as $datos) {
            $hoja->fromArray(array_values($datos), null, "A{$fila}");
            $fila++;
        }

        foreach (range('A', $ultimaColumna) as $columna) {
            $hoja->getColumnDimension($columna)->setAutoSize(true);
        }

        $hoja->freezePane('A2');

        $escritor = new Xlsx($libro);
        $nombre = Str::slug($reporte->titulo()).'-'.now()->format('Ymd-His').'.xlsx';

        return response()->streamDownload(
            fn () => $escritor->save('php://output'),
            $nombre,
            ['Content-Type' => $this->mime()],
        );
    }

    /**
     * Excel limita el nombre de una hoja a 31 caracteres y prohíbe algunos símbolos.
     */
    private function tituloHoja(string $titulo): string
    {
        return Str::limit(str_replace(['\\', '/', '*', '?', ':', '[', ']'], ' ', $titulo), 31, '');
    }
}
