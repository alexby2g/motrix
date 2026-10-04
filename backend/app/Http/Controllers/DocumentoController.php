<?php
namespace App\Http\Controllers;

use App\Models\Mototaxista;
use App\Models\Sindicato;
use App\Models\Solicitud;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class DocumentoController extends Controller
{
    private function opciones(Request $request): array
    {
        return match (strtolower(trim((string) $request->user()?->role))) {
            'admin_general' => ['afiliados', 'sindicatos', 'vehiculos', 'viajes'],
            'admin_registro', 'representante_federacion', 'secretario' => ['afiliados', 'sindicatos', 'vehiculos', 'actividad'],
            'admin_servicios' => ['viajes'],
            default => [],
        };
    }

    public function index(Request $request)
    {
        $opciones = $this->opciones($request);
        abort_if($opciones === [], 403, 'Documentos no disponibles para este rol.');

        $titulos = ['afiliados' => 'Padrón de afiliados', 'sindicatos' => 'Sindicatos', 'vehiculos' => 'Vehículos registrados',
            'actividad' => 'Actividad por mes', 'viajes' => 'Registro de viajes', 'mi_registro' => 'Mi registro',
            'mis_vehiculos' => 'Mis vehículos', 'mis_viajes' => 'Mi historial de viajes'];
        return response()->json(collect($opciones)->map(fn ($tipo) => ['tipo' => $tipo, 'titulo' => $titulos[$tipo]]));
    }

    private function afiliados(Request $request)
    {
        $rol = strtolower(trim((string) $request->user()->role));
        $q = Mototaxista::query();
        if ($rol === 'representante_federacion') {
            $id = (int) $request->user()->federacion_id;
            abort_if($id <= 0, 403, 'La cuenta no tiene federación asignada.');
            $q->whereHas('sindicato', fn ($s) => $s->where('id_federacion', $id));
        } elseif ($rol === 'secretario') {
            $id = (int) $request->user()->sindicato_id;
            abort_if($id <= 0, 403, 'La cuenta no tiene sindicato asignado.');
            $q->where('id_sindicato', $id);
        } elseif ($rol === 'conductor') {
            $id = (int) $request->user()->mototaxista_id;
            abort_if($id <= 0, 403, 'La cuenta no tiene conductor asignado.');
            $q->whereKey($id);
        } else {
            abort_unless(in_array($rol, ['admin_general', 'admin_registro'], true), 403);
        }
        return $q;
    }

    public function descargar(Request $request, string $tipo, string $formato)
    {
        abort_unless(in_array($tipo, $this->opciones($request), true), 403, 'Reporte no autorizado para este rol.');
        abort_unless(in_array($formato, ['pdf', 'excel', 'word'], true), 404);
        $datos = $request->validate(['desde' => ['nullable', 'date_format:Y-m-d'], 'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde']]);
        $desde = $datos['desde'] ?? now('America/La_Paz')->startOfMonth()->toDateString();
        $hasta = $datos['hasta'] ?? now('America/La_Paz')->toDateString();
        abort_if($hasta < $desde, 422, 'El fin debe ser posterior al inicio.');
        $filas = []; $limite = 5000;
        if (in_array($tipo, ['afiliados', 'mi_registro', 'vehiculos', 'mis_vehiculos'], true)) {
            $q = $this->afiliados($request)->with(['persona', 'sindicato', 'motocicletas'])->orderBy('id');
            abort_if((clone $q)->count() > $limite, 422, 'El reporte supera 5000 afiliados.');
            $vehiculos = in_array($tipo, ['vehiculos', 'mis_vehiculos'], true);
            $cabeceras = $vehiculos ? ['Afiliado', 'Sindicato', 'Chaleco', 'Placa', 'Modelo', 'Color'] : ['Nombre', 'CI', 'Sindicato', 'Chaleco', 'Estado', 'Habilitación'];
            foreach ($q->get() as $m) {
                $nombre = trim(($m->persona?->nombre ?? '').' '.($m->persona?->apellidos ?? ''));
                if ($vehiculos) {
                    foreach ($m->motocicletas as $v) $filas[] = [$nombre, $m->sindicato?->nombre, $m->nro_chaleco, $v->placa, $v->modelo, $v->color];
                } else $filas[] = [$nombre, $m->persona?->ci, $m->sindicato?->nombre, $m->nro_chaleco, $m->estado, $m->estado_sindical];
            }
        } elseif ($tipo === 'sindicatos') {
            $q = Sindicato::query()->with('federacionEntidad')->withCount('mototaxistas')->orderBy('nombre');
            $rol = strtolower(trim((string) $request->user()->role));
            if ($rol === 'representante_federacion') {
                $id = (int) $request->user()->federacion_id; abort_if($id <= 0, 403);
                $q->where('id_federacion', $id);
            } elseif ($rol === 'secretario') {
                $id = (int) $request->user()->sindicato_id; abort_if($id <= 0, 403);
                $q->whereKey($id);
            }
            abort_if((clone $q)->count() > $limite, 422, 'El reporte supera 5000 sindicatos.');
            $cabeceras = ['Sindicato', 'Federación', 'Afiliados'];
            foreach ($q->get() as $s) $filas[] = [$s->nombre, $s->federacionEntidad?->nombre, $s->mototaxistas_count];
        } else {
            $q = Solicitud::query()->whereDate('fecha', '>=', $desde)->whereDate('fecha', '<=', $hasta);
            $rol = strtolower(trim((string) $request->user()->role));
            if ($rol === 'pasajero') {
                $id = (int) $request->user()->pasajero_id; abort_if($id <= 0, 403);
                $q->where('id_pasajero', $id);
            } elseif (in_array($rol, ['conductor', 'secretario', 'representante_federacion', 'admin_registro'], true)) {
                $q->whereIn('mototaxista_id', $this->afiliados($request)->select('id'));
            }
            if ($tipo === 'actividad') {
                // Datos agregados: nunca se exportan pasajeros ni coordenadas para la organización.
                $cabeceras = ['Mes', 'Estado', 'Cantidad de viajes'];
                $resumen = $q->selectRaw('SUBSTR(fecha, 1, 7) as mes, estado, COUNT(*) as total')->groupBy('mes', 'estado')->orderBy('mes')->get();
                foreach ($resumen as $r) $filas[] = [$r->mes, $r->estado, $r->total];
            } else {
                abort_if((clone $q)->count() > $limite, 422, 'El reporte supera 5000 viajes. Reduce el período.');
                $cabeceras = ['Viaje', 'Fecha', 'Origen', 'Destino', 'Estado', 'Tarifa Bs', 'Método solicitado'];
                foreach ($q->orderBy('id')->get() as $v) $filas[] = [$v->id, $v->fecha?->format('Y-m-d'), $v->origen, $v->destino, $v->estado, $v->precio, $v->metodo_pago];
            }
        }
        abort_if(count($filas) > $limite, 422, 'El reporte supera 5000 filas.');
        $titulo = collect($this->index($request)->getData(true))->firstWhere('tipo', $tipo)['titulo'];
        $emitido = now('America/La_Paz')->format('d/m/Y H:i');
        $usuario = $request->user();
        $alcance = match (strtolower(trim((string) $usuario->role))) {
            'representante_federacion' => 'Federación ID '.$usuario->federacion_id,
            'secretario' => 'Sindicato ID '.$usuario->sindicato_id,
            'conductor', 'pasajero' => 'Registro personal del usuario '.$usuario->id,
            default => 'Administración '.$usuario->role,
        };
        $nombre = 'MOTRIX_'.$tipo.'_'.now('America/La_Paz')->format('Ymd_His');
        $vista = view('documentos.reporte', compact('titulo', 'cabeceras', 'filas', 'emitido', 'alcance', 'desde', 'hasta', 'tipo'));
        if ($formato === 'pdf') return Pdf::loadHTML($vista->render())->setPaper('a4', count($cabeceras) > 5 ? 'landscape' : 'portrait')->download($nombre.'.pdf');
        if ($formato === 'word') return response($vista->render(), 200, ['Content-Type' => 'application/msword; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="'.$nombre.'.doc"']);
        return response()->streamDownload(function () use ($cabeceras, $filas, $titulo, $alcance, $emitido) {
            $s = new Spreadsheet(); $hoja = $s->getActiveSheet();
            $registros = [[$titulo], [$alcance], ['Emitido '.$emitido], $cabeceras, ...$filas];
            // Texto explícito evita ejecutar fórmulas provenientes de nombres u otros campos.
            foreach ($registros as $i => $fila) foreach ($fila as $j => $valor) {
                $celda = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($j + 1).($i + 1);
                $hoja->setCellValueExplicit($celda, (string) ($valor ?? ''), DataType::TYPE_STRING);
            }
            foreach (range(1, count($cabeceras)) as $c) $hoja->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c))->setAutoSize(true);
            (new Xlsx($s))->save('php://output');
        }, $nombre.'.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
