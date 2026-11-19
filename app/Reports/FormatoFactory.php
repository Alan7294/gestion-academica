<?php

namespace App\Reports;

use App\Reports\Formatos\FormatoExcel;
use App\Reports\Formatos\FormatoPdf;
use App\Reports\Formatos\FormatoReporte;
use Illuminate\Validation\ValidationException;

/**
 * Factory de formatos (RF12): traduce el parámetro de la URL a una estrategia.
 */
class FormatoFactory
{
    /**
     * @var array<string, class-string<FormatoReporte>>
     */
    private const FORMATOS = [
        'excel' => FormatoExcel::class,
        'pdf' => FormatoPdf::class,
    ];

    /**
     * @throws ValidationException
     */
    public function crear(string $formato): FormatoReporte
    {
        $clase = self::FORMATOS[$formato] ?? null;

        if ($clase === null) {
            throw ValidationException::withMessages([
                'formato' => 'El formato solicitado no está soportado. Usa "pdf" o "excel".',
            ]);
        }

        return app($clase);
    }

    /**
     * @return array<int, string>
     */
    public static function disponibles(): array
    {
        return array_keys(self::FORMATOS);
    }
}
