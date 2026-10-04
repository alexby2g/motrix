<!doctype html><html lang="es"><head><meta charset="UTF-8"><style>
body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #172431; }
h1 { font-size: 19px; } table { border-collapse: collapse; width: 100%; } th,td { border: 1px solid #ddd; padding: 6px; text-align: left; overflow-wrap: anywhere; } th { background: #e9edf2; } thead { display: table-header-group; } tr { page-break-inside: avoid; }
</style></head><body><h1>MOTRIX · {{ $titulo }}</h1><p>{{ $alcance }} · Emitido {{ $emitido }} · {{ count($filas) }} registros</p>
@if(in_array($tipo, ['actividad', 'viajes', 'mis_viajes']))<p>Período {{ $desde }} a {{ $hasta }}</p>@endif
<p>Registro informativo. No constituye certificación oficial, factura ni comprobante bancario. Las tarifas de viajes son importes registrados en la solicitud; no acreditan el monto efectivamente pagado.</p>
<table><thead><tr>@foreach($cabeceras as $c)<th>{{ $c }}</th>@endforeach</tr></thead><tbody>
@forelse($filas as $fila)<tr>@foreach($fila as $valor)<td>{{ $valor ?? '—' }}</td>@endforeach</tr>@empty<tr><td colspan="{{ count($cabeceras) }}">No hay registros para este alcance y período.</td></tr>@endforelse
</tbody></table></body></html>
