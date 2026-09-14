<?php

namespace App\Http\Controllers;

use App\Models\Mototaxista;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HabilitacionSindicalController extends Controller
{
    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'documentacion_en_regla' => ['required', 'boolean'],
            'aportes_al_dia' => ['required', 'boolean'],
            'estado_sindical' => [
                'nullable',
                'string',
                Rule::in(['Habilitado', 'No habilitado', 'Expulsado']),
            ],
            'motivo' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $role = strtolower(trim((string) ($user?->role ?? '')));

        $query = Mototaxista::query()->where('id', $id);

        if ($role === 'secretario') {
            $sindicatoId = (int) ($user?->sindicato_id ?? 0);
            if ($sindicatoId <= 0) {
                return response()->json([
                    'message' => 'La cuenta de secretario no está vinculada a un sindicato.',
                ], 403);
            }

            $query->where('id_sindicato', $sindicatoId);
        }

        $mototaxista = $query->firstOrFail();

        $documentos = (bool) $data['documentacion_en_regla'];
        $aportes = (bool) $data['aportes_al_dia'];
        $requested = $data['estado_sindical'] ?? null;
        $reason = trim((string) ($data['motivo'] ?? ''));

        /* Expulsado nunca se reactiva por una actualización automática. */
        if ($mototaxista->estado_sindical === 'Expulsado' && $requested === null) {
            $status = 'Expulsado';
        } elseif ($requested === 'Expulsado') {
            $status = 'Expulsado';
        } elseif ($requested === 'No habilitado') {
            $status = 'No habilitado';
        } else {
            $status = ($documentos && $aportes)
                ? 'Habilitado'
                : 'No habilitado';
        }

        if ($status === 'Expulsado') {
            $calculatedReason = $reason !== ''
                ? $reason
                : ($mototaxista->motivo_estado_sindical ?: 'Afiliación expulsada por el sindicato');
        } elseif ($status === 'No habilitado') {
            $causes = [];
            if (! $documentos) {
                $causes[] = 'documentación incompleta';
            }
            if (! $aportes) {
                $causes[] = 'aportes pendientes';
            }

            $calculatedReason = $reason !== ''
                ? $reason
                : ($causes !== []
                    ? implode(' y ', $causes)
                    : 'Inhabilitado manualmente por el sindicato');
        } else {
            $calculatedReason = null;
        }

        $mototaxista->documentacion_en_regla = $documentos;
        $mototaxista->aportes_al_dia = $aportes;
        $mototaxista->estado_sindical = $status;
        $mototaxista->motivo_estado_sindical = $calculatedReason;
        $mototaxista->estado_sindical_actualizado_en = Carbon::now('UTC');

        if ($status !== 'Habilitado' || $mototaxista->estado !== 'Activo') {
            $mototaxista->disponible = false;
        }

        $mototaxista->save();
        $mototaxista->load(['persona.imagenes', 'sindicato', 'usuarioConductor']);

        return response()->json([
            'message' => 'Habilitación sindical actualizada correctamente.',
            'data' => $mototaxista,
            'habilitado_para_operar' => $mototaxista->habilitadoSindicalmente()
                && $mototaxista->usuarioConductor !== null,
            'motivo_inhabilitacion' => $mototaxista->motivoInhabilitacionSindical(),
        ]);
    }
}
