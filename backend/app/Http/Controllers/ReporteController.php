<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReporteController extends Controller
{
    public function obtenerDatosDashboard()
    {
        try {
            /*
            |--------------------------------------------------------------------------
            | RECAUDACIÓN DE LOS ÚLTIMOS 7 DÍAS
            |--------------------------------------------------------------------------
            */

            $inicioSemana = Carbon::now('America/La_Paz')
                ->subDays(6)
                ->startOfDay();

            $registrosSemanales = DB::table('pagos')
                ->join(
                    'servicios',
                    'pagos.id_servicio',
                    '=',
                    'servicios.id'
                )
                ->join(
                    'solicitudes',
                    'servicios.id_solicitud',
                    '=',
                    'solicitudes.id'
                )
                ->select(
                    'solicitudes.fecha',

                    DB::raw(
                        "SUM(
                            CASE
                                WHEN pagos.metodo = 'Efectivo'
                                THEN pagos.monto
                                ELSE 0
                            END
                        ) as efectivo"
                    ),

                    DB::raw(
                        "SUM(
                            CASE
                                WHEN pagos.metodo != 'Efectivo'
                                THEN pagos.monto
                                ELSE 0
                            END
                        ) as digital"
                    )
                )
                ->where(
                    'pagos.estado',
                    'Completado'
                )
                ->where(
                    'servicios.estado',
                    'Finalizado'
                )
                ->whereDate(
                    'solicitudes.fecha',
                    '>=',
                    $inicioSemana->format('Y-m-d')
                )
                ->groupBy(
                    'solicitudes.fecha'
                )
                ->orderBy(
                    'solicitudes.fecha'
                )
                ->get()
                ->keyBy(function ($registro) {
                    return Carbon::parse(
                        $registro->fecha
                    )->format('Y-m-d');
                });

            $gananciasSemanales = collect(
                range(0, 6)
            )->map(function ($indice) use (
                $inicioSemana,
                $registrosSemanales
            ) {
                $fecha = $inicioSemana
                    ->copy()
                    ->addDays($indice)
                    ->format('Y-m-d');

                $registro = $registrosSemanales
                    ->get($fecha);

                return [
                    'fecha' => $fecha,

                    'efectivo' => $registro
                        ? (float) $registro->efectivo
                        : 0.0,

                    'digital' => $registro
                        ? (float) $registro->digital
                        : 0.0,
                ];
            })->values();

            /*
            |--------------------------------------------------------------------------
            | INDICADORES GENERALES
            |--------------------------------------------------------------------------
            */

            /*
             * Se cuentan los viajes finalizados desde solicitudes,
             * igual que las calificaciones, para que los indicadores
             * utilicen la misma fuente de datos.
             */
            $totalViajes = DB::table('solicitudes')
                ->where(
                    'estado',
                    'Finalizado'
                )
                ->count();

            $totalDinero = DB::table('pagos')
                ->where(
                    'estado',
                    'Completado'
                )
                ->sum('monto');

            $metodoMasUsado = DB::table('pagos')
                ->select(
                    'metodo',
                    DB::raw(
                        'COUNT(*) as total'
                    )
                )
                ->where(
                    'estado',
                    'Completado'
                )
                ->groupBy('metodo')
                ->orderByDesc('total')
                ->first();

            /*
            |--------------------------------------------------------------------------
            | INDICADORES DE REPUTACIÓN
            |--------------------------------------------------------------------------
            */

            $consultaCalificaciones = DB::table(
                'solicitudes'
            )
                ->where(
                    'estado',
                    'Finalizado'
                )
                ->whereNotNull(
                    'calificacion'
                );

            $totalCalificaciones = (
                clone $consultaCalificaciones
            )->count();

            $promedioGeneral = (float) (
                (clone $consultaCalificaciones)
                    ->avg('calificacion')
                ?? 0
            );

            $viajesSinCalificar = DB::table(
                'solicitudes'
            )
                ->where(
                    'estado',
                    'Finalizado'
                )
                ->whereNull(
                    'calificacion'
                )
                ->count();

            /*
            |--------------------------------------------------------------------------
            | RANKING DE MOTOTAXISTAS
            |--------------------------------------------------------------------------
            */

            $ranking = DB::table(
                'mototaxistas as mototaxistas'
            )
                ->leftJoin(
                    'personas as personas',
                    'mototaxistas.id_persona',
                    '=',
                    'personas.id'
                )
                ->leftJoin(
                    'solicitudes as solicitudes',
                    function ($join) {
                        $join->on(
                            'solicitudes.mototaxista_id',
                            '=',
                            'mototaxistas.id'
                        )
                            ->where(
                                'solicitudes.estado',
                                '=',
                                'Finalizado'
                            )
                            ->whereNotNull(
                                'solicitudes.calificacion'
                            );
                    }
                )
                ->select(
                    'mototaxistas.id',
                    'personas.nombre',

                    DB::raw(
                        'COUNT(
                            solicitudes.calificacion
                        ) as total_calificaciones'
                    ),

                    DB::raw(
                        'COALESCE(
                            AVG(
                                solicitudes.calificacion
                            ),
                            0
                        ) as promedio_calificacion'
                    )
                )
                ->groupBy(
                    'mototaxistas.id',
                    'personas.nombre'
                )
                ->orderByDesc(
                    'promedio_calificacion'
                )
                ->orderByDesc(
                    'total_calificaciones'
                )
                ->orderBy(
                    'personas.nombre'
                )
                ->get();

            $ranking->transform(
                function ($mototaxista) {
                    $ultimoComentario = DB::table(
                        'solicitudes'
                    )
                        ->where(
                            'mototaxista_id',
                            $mototaxista->id
                        )
                        ->where(
                            'estado',
                            'Finalizado'
                        )
                        ->whereNotNull(
                            'comentario_calificacion'
                        )
                        ->where(
                            'comentario_calificacion',
                            '<>',
                            ''
                        )
                        ->orderByDesc(
                            'calificado_en'
                        )
                        ->first([
                            'comentario_calificacion',
                            'calificado_en',
                        ]);

                    $mototaxista->nombre = (
                        $mototaxista->nombre
                        ?: 'Mototaxista #'
                            . $mototaxista->id
                    );

                    $mototaxista
                        ->promedio_calificacion =
                        round(
                            (float) $mototaxista
                                ->promedio_calificacion,
                            2
                        );

                    $mototaxista
                        ->total_calificaciones =
                        (int) $mototaxista
                            ->total_calificaciones;

                    $mototaxista
                        ->ultimo_comentario =
                        $ultimoComentario
                            ? $ultimoComentario
                                ->comentario_calificacion
                            : null;

                    $mototaxista
                        ->ultimo_comentario_en =
                        $ultimoComentario
                            ? $ultimoComentario
                                ->calificado_en
                            : null;

                    return $mototaxista;
                }
            );

            /*
            |--------------------------------------------------------------------------
            | COMENTARIOS RECIENTES
            |--------------------------------------------------------------------------
            */

            $comentariosRecientes = DB::table(
                'solicitudes as solicitudes'
            )
                ->join(
                    'mototaxistas as mototaxistas',
                    'solicitudes.mototaxista_id',
                    '=',
                    'mototaxistas.id'
                )
                ->leftJoin(
                    'personas as personas',
                    'mototaxistas.id_persona',
                    '=',
                    'personas.id'
                )
                ->where(
                    'solicitudes.estado',
                    'Finalizado'
                )
                ->whereNotNull(
                    'solicitudes.calificacion'
                )
                ->whereNotNull(
                    'solicitudes.comentario_calificacion'
                )
                ->where(
                    'solicitudes.comentario_calificacion',
                    '<>',
                    ''
                )
                ->select(
                    'solicitudes.id as solicitud_id',
                    'solicitudes.calificacion',
                    'solicitudes.comentario_calificacion',
                    'solicitudes.calificado_en',

                    'mototaxistas.id as mototaxista_id',
                    'personas.nombre as mototaxista_nombre'
                )
                ->orderByDesc(
                    'solicitudes.calificado_en'
                )
                ->limit(10)
                ->get();

            return response()->json([
                'semanal' =>
                    $gananciasSemanales,

                'kpis' => [
                    'total_viajes' =>
                        (int) $totalViajes,

                    'total_recaudado' =>
                        round(
                            (float) $totalDinero,
                            2
                        ),

                    'metodo_preferido' =>
                        $metodoMasUsado
                            ? $metodoMasUsado->metodo
                            : 'Ninguno',

                    'promedio_general' =>
                        round(
                            $promedioGeneral,
                            2
                        ),

                    'total_calificaciones' =>
                        (int) $totalCalificaciones,

                    'viajes_sin_calificar' =>
                        (int) $viajesSinCalificar,
                ],

                'ranking_mototaxistas' =>
                    $ranking,

                'comentarios_recientes' =>
                    $comentariosRecientes,
            ], 200);
        } catch (\Throwable $error) {
            return response()->json([
                'mensaje' =>
                    'No se pudieron generar los reportes.',

                'error' =>
                    $error->getMessage(),
            ], 500);
        }
    }
}
