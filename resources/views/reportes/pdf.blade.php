<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $reporte->titulo() }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 10px;
            color: #1f2937;
            margin: 24px;
        }
        h1 {
            font-size: 18px;
            margin: 0 0 4px;
            color: #111827;
        }
        .descripcion {
            margin: 0 0 12px;
            color: #4b5563;
            font-size: 10px;
        }
        .contexto {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .contexto td {
            padding: 3px 8px;
            border: 1px solid #e5e7eb;
            font-size: 9px;
        }
        .contexto .etiqueta {
            background: #f3f4f6;
            font-weight: bold;
            width: 140px;
        }
        table.datos {
            width: 100%;
            border-collapse: collapse;
        }
        table.datos thead th {
            background: #e5e7eb;
            border: 1px solid #9ca3af;
            padding: 6px;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        table.datos tbody td {
            border: 1px solid #d1d5db;
            padding: 5px 6px;
            font-size: 9px;
            vertical-align: top;
        }
        table.datos tbody tr:nth-child(even) td {
            background: #f9fafb;
        }
        .sin-datos {
            text-align: center;
            color: #6b7280;
            padding: 18px;
        }
        .pie {
            margin-top: 16px;
            font-size: 8px;
            color: #6b7280;
            text-align: right;
        }
    </style>
</head>
<body>
    <h1>{{ $reporte->titulo() }}</h1>
    <p class="descripcion">{{ $reporte->descripcion() }}</p>

    @if ($reporte->contexto() !== [])
        <table class="contexto">
            @foreach ($reporte->contexto() as $etiqueta => $valor)
                <tr>
                    <td class="etiqueta">{{ $etiqueta }}</td>
                    <td>{{ $valor }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <table class="datos">
        <thead>
            <tr>
                @foreach ($reporte->encabezados() as $encabezado)
                    <th>{{ $encabezado }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($reporte->filas() as $fila)
                <tr>
                    @foreach ($fila as $celda)
                        <td>{{ $celda }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($reporte->encabezados()) }}" class="sin-datos">
                        Sin datos para mostrar.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p class="pie">
        Generado el {{ $generadoEn->format('d/m/Y H:i') }} · Sistema de Gestión Académica
    </p>
</body>
</html>
