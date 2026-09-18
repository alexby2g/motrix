<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $reporte['titulo'] ?? 'Reporte MOTRIX' }}</title>
    <style>
        @page { margin: 22px 24px 28px; }
        body { font-family: DejaVu Sans, sans-serif; color: #263238; font-size: 9px; }
        .header { width: 100%; border-bottom: 3px solid #23752f; margin-bottom: 12px; padding-bottom: 8px; }
        .brand { font-size: 24px; font-weight: 800; color: #145a22; letter-spacing: 1px; }
        .title { font-size: 16px; font-weight: 700; margin-top: 3px; }
        .subtitle { font-size: 10px; color: #546e7a; margin-top: 2px; }
        .meta { margin: 8px 0 12px; color: #546e7a; line-height: 1.45; }
        table { width: 100%; border-collapse: collapse; table-layout: auto; }
        th { background: #e8f5e9; color: #145a22; font-weight: 700; border: 1px solid #b7c9b9; padding: 5px 4px; }
        td { border: 1px solid #d6ded7; padding: 4px; vertical-align: top; word-wrap: break-word; }
        tr:nth-child(even) td { background: #fafafa; }
        .totals { margin-top: 12px; border: 1px solid #cfd8dc; background: #f5f7f7; padding: 8px 10px; }
        .total-item { display: inline-block; margin-right: 18px; margin-bottom: 3px; }
        .footer { position: fixed; bottom: -16px; left: 0; right: 0; text-align: center; color: #78909c; font-size: 7px; }
        .empty { padding: 18px; text-align: center; color: #78909c; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">MOTRIX</div>
        <div class="title">{{ $reporte['titulo'] ?? 'Reporte MOTRIX' }}</div>
        @if(!empty($reporte['subtitulo']))
            <div class="subtitle">{{ $reporte['subtitulo'] }}</div>
        @endif
    </div>

    <div class="meta">
        <strong>Generado:</strong> {{ $reporte['generado_en'] ?? '' }}
        &nbsp;&nbsp;|&nbsp;&nbsp;
        <strong>Usuario:</strong> {{ $reporte['generado_por'] ?? '' }}
        &nbsp;&nbsp;|&nbsp;&nbsp;
        <strong>Rol:</strong> {{ $reporte['rol_generador'] ?? '' }}
        &nbsp;&nbsp;|&nbsp;&nbsp;
        <strong>Alcance:</strong> {{ $reporte['alcance'] ?? '' }}
    </div>

    @if(empty($reporte['filas']))
        <div class="empty">No existen registros para los filtros seleccionados.</div>
    @else
        <table>
            <thead>
                <tr>
                    @foreach(($reporte['columnas'] ?? []) as $etiqueta)
                        <th>{{ $etiqueta }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach(($reporte['filas'] ?? []) as $fila)
                    <tr>
                        @foreach(array_keys($reporte['columnas'] ?? []) as $clave)
                            <td>{{ $fila[$clave] ?? '' }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if(!empty($reporte['totales']))
        <div class="totals">
            @foreach($reporte['totales'] as $clave => $valor)
                <span class="total-item">
                    <strong>{{ ucfirst(str_replace('_', ' ', $clave)) }}:</strong>
                    {{ $valor }}
                </span>
            @endforeach
        </div>
    @endif

    <div class="footer">
        Sistema de Gestión y Solicitud de Mototaxis · MOTRIX · Trinidad, Beni
    </div>
</body>
</html>
