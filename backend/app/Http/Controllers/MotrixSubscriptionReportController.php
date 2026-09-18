<?php

namespace App\Http\Controllers;

use App\Models\Sindicato;
use App\Services\MotrixSubscriptionReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MotrixSubscriptionReportController extends Controller
{
    public function __construct(
        private readonly MotrixSubscriptionReportService $reportService
    ) {
    }

    public function catalogo(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->reportService->catalogo($this->rol($request)),
        ]);
    }

    public function preview(Request $request): JsonResponse
    {
        [$datos, $sindicatoId] = $this->resolverParametros($request);
        $this->autorizarTipo($request, $datos['tipo']);

        return response()->json([
            'data' => $this->reportService->generar(
                $datos['tipo'],
                $datos['periodo'],
                $sindicatoId,
                isset($datos['id_mototaxista'])
                    ? (int) $datos['id_mototaxista']
                    : null
            ),
        ]);
    }

    public function exportar(
        Request $request,
        string $tipo,
        string $formato
    ) {
        $request->merge([
            'tipo' => $tipo,
            'formato' => $formato,
        ]);

        [$datos, $sindicatoId] = $this->resolverParametros($request, true);
        $this->autorizarTipo($request, $datos['tipo']);

        $reporte = $this->reportService->generar(
            $datos['tipo'],
            $datos['periodo'],
            $sindicatoId,
            isset($datos['id_mototaxista'])
                ? (int) $datos['id_mototaxista']
                : null
        );

        $this->agregarAuditoria($request, $reporte, $sindicatoId);

        return $datos['formato'] === 'pdf'
            ? $this->pdf($reporte, $datos['tipo'])
            : $this->excel($reporte, $datos['tipo']);
    }

    public function conductorPreview(Request $request): JsonResponse
    {
        $mototaxistaId = $this->mototaxistaUsuario($request);

        return response()->json([
            'data' => $this->reportService->generar(
                MotrixSubscriptionReportService::TIPO_HISTORIAL,
                now()->format('Y-m'),
                null,
                $mototaxistaId
            ),
        ]);
    }

    public function conductorExportar(
        Request $request,
        string $formato
    ) {
        abort_unless(in_array($formato, ['pdf', 'excel'], true), 404);

        $mototaxistaId = $this->mototaxistaUsuario($request);
        $reporte = $this->reportService->generar(
            MotrixSubscriptionReportService::TIPO_HISTORIAL,
            now()->format('Y-m'),
            null,
            $mototaxistaId
        );

        $this->agregarAuditoria($request, $reporte, null);

        return $formato === 'pdf'
            ? $this->pdf($reporte, 'historial')
            : $this->excel($reporte, 'historial');
    }

    private function resolverParametros(
        Request $request,
        bool $incluirFormato = false
    ): array {
        $reglas = [
            'tipo' => [
                'required',
                Rule::in($this->reportService->tipos()),
            ],
            'periodo' => [
                'nullable',
                'regex:/^\\d{4}-\\d{2}$/',
            ],
            'id_sindicato' => [
                'nullable',
                'integer',
                'exists:sindicatos,id',
            ],
            'id_mototaxista' => [
                'nullable',
                'integer',
                'exists:mototaxistas,id',
            ],
        ];

        if ($incluirFormato) {
            $reglas['formato'] = [
                'required',
                Rule::in(['pdf', 'excel']),
            ];
        }

        $datos = $request->validate($reglas);
        $datos['periodo'] = $datos['periodo'] ?? now()->format('Y-m');

        if ($this->rol($request) === 'secretario') {
            $sindicatoId = (int) ($request->user()?->sindicato_id ?? 0);

            abort_if(
                $sindicatoId <= 0,
                403,
                'La cuenta de secretario no está vinculada a un sindicato.'
            );
        } else {
            $sindicatoId = isset($datos['id_sindicato'])
                ? (int) $datos['id_sindicato']
                : null;
        }

        if (
            ($datos['tipo'] ?? null) === MotrixSubscriptionReportService::TIPO_HISTORIAL
            && isset($datos['id_mototaxista'])
        ) {
            $this->reportService->autorizarMototaxista(
                (int) $datos['id_mototaxista'],
                $sindicatoId
            );
        }

        return [$datos, $sindicatoId];
    }

    private function autorizarTipo(Request $request, string $tipo): void
    {
        if ($tipo === MotrixSubscriptionReportService::TIPO_CONSOLIDADO) {
            abort_unless(
                $this->rol($request) === 'admin_general',
                403,
                'El resumen consolidado está reservado al Administrador General.'
            );
        }
    }

    private function agregarAuditoria(
        Request $request,
        array &$reporte,
        ?int $sindicatoId
    ): void {
        $usuario = $request->user();
        $reporte['generado_en'] = now('America/La_Paz')->format('d/m/Y H:i:s');
        $reporte['generado_por'] = (string) (
            $usuario?->name
            ?? $usuario?->email
            ?? ('Usuario #' . ($usuario?->id ?? ''))
        );
        $rol = $this->rol($request);
        $reporte['rol_generador'] = (string) ($usuario?->role ?? '');
        $reporte['id_sindicato'] = $sindicatoId;

        if ($rol === 'conductor') {
            $reporte['alcance'] = 'Historial personal del conductor';
        } elseif ($sindicatoId) {
            $reporte['alcance'] = (string) (
                Sindicato::query()
                    ->where('id', $sindicatoId)
                    ->value('nombre')
                ?? "Sindicato #{$sindicatoId}"
            );
        } else {
            $reporte['alcance'] = 'Todos los sindicatos';
        }
    }

    private function pdf(array $reporte, string $tipo)
    {
        $pdf = Pdf::loadView(
            'reports.motrix-subscriptions',
            ['reporte' => $reporte]
        )->setPaper('a4', 'landscape');

        return $pdf->download($this->nombreArchivo($tipo, 'pdf'));
    }

    private function excel(array $reporte, string $tipo): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('MOTRIX');

        $columnas = $reporte['columnas'] ?? [];
        $filas = $reporte['filas'] ?? [];
        $totalColumnas = max(1, count($columnas));
        $ultimaColumna = Coordinate::stringFromColumnIndex($totalColumnas);

        $sheet->setCellValue('A1', 'MOTRIX');
        $sheet->mergeCells("A1:{$ultimaColumna}1");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(20);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', $reporte['titulo'] ?? 'Reporte MOTRIX');
        $sheet->mergeCells("A2:{$ultimaColumna}2");
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);

        $sheet->setCellValue('A3', $reporte['subtitulo'] ?? '');
        $sheet->mergeCells("A3:{$ultimaColumna}3");
        $sheet->setCellValue('A4', 'Generado: ' . ($reporte['generado_en'] ?? ''));
        $sheet->setCellValue('A5', 'Usuario: ' . ($reporte['generado_por'] ?? ''));
        $sheet->setCellValue('A6', 'Alcance: ' . ($reporte['alcance'] ?? ''));
        $sheet->mergeCells("A4:{$ultimaColumna}4");
        $sheet->mergeCells("A5:{$ultimaColumna}5");
        $sheet->mergeCells("A6:{$ultimaColumna}6");

        $filaEncabezado = 8;
        $indice = 1;
        foreach ($columnas as $etiqueta) {
            $columna = Coordinate::stringFromColumnIndex($indice);
            $sheet->setCellValue("{$columna}{$filaEncabezado}", $etiqueta);
            $indice++;
        }

        $rangoEncabezado = "A{$filaEncabezado}:{$ultimaColumna}{$filaEncabezado}";
        $sheet->getStyle($rangoEncabezado)->getFont()->setBold(true);
        $sheet->getStyle($rangoEncabezado)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE8F5E9');

        $filaActual = $filaEncabezado + 1;
        foreach ($filas as $fila) {
            $columnaActual = 1;
            foreach (array_keys($columnas) as $clave) {
                $columna = Coordinate::stringFromColumnIndex($columnaActual);
                $sheet->setCellValue(
                    "{$columna}{$filaActual}",
                    $fila[$clave] ?? ''
                );
                $columnaActual++;
            }
            $filaActual++;
        }

        $ultimaFilaDatos = max($filaEncabezado, $filaActual - 1);
        $sheet->setAutoFilter("A{$filaEncabezado}:{$ultimaColumna}{$ultimaFilaDatos}");
        $sheet->freezePane('A9');

        for ($col = 1; $col <= $totalColumnas; $col++) {
            $sheet->getColumnDimension(
                Coordinate::stringFromColumnIndex($col)
            )->setAutoSize(true);
        }

        if (! empty($reporte['totales'])) {
            $filaActual += 1;
            $sheet->setCellValue("A{$filaActual}", 'TOTALES');
            $sheet->getStyle("A{$filaActual}")->getFont()->setBold(true);
            foreach ($reporte['totales'] as $clave => $valor) {
                $filaActual++;
                $sheet->setCellValue("A{$filaActual}", str_replace('_', ' ', ucfirst($clave)));
                $sheet->setCellValue("B{$filaActual}", $valor);
            }
        }

        $writer = new Xlsx($spreadsheet);
        $nombre = $this->nombreArchivo($tipo, 'xlsx');

        return response()->streamDownload(
            static function () use ($writer, $spreadsheet) {
                $writer->save('php://output');
                $spreadsheet->disconnectWorksheets();
            },
            $nombre,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]
        );
    }

    private function nombreArchivo(string $tipo, string $extension): string
    {
        return sprintf(
            'MOTRIX_%s_%s.%s',
            strtoupper(preg_replace('/[^a-z0-9]+/i', '_', $tipo) ?: 'REPORTE'),
            now('America/La_Paz')->format('Ymd_His'),
            $extension
        );
    }

    private function rol(Request $request): string
    {
        return strtolower(trim((string) ($request->user()?->role ?? '')));
    }

    private function mototaxistaUsuario(Request $request): int
    {
        $mototaxistaId = (int) ($request->user()?->mototaxista_id ?? 0);

        abort_if(
            $mototaxistaId <= 0,
            403,
            'La cuenta de conductor no está vinculada a un mototaxista.'
        );

        return $mototaxistaId;
    }
}
