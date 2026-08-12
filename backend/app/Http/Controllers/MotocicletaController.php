<?php

namespace App\Http\Controllers;

use App\Http\Requests\MotocicletaRequest;
use App\Models\Motocicleta;
use App\Models\Mototaxista;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class MotocicletaController extends Controller
{
    public function index(Request $request)
    {
        return $this->consultaVisible(
            $request
        )
            ->with([
                'mototaxista.persona',
                'mototaxista.sindicato',
            ])
            ->orderByDesc('id')
            ->get();
    }

    public function store(
        MotocicletaRequest $request
    ) {
        $datos = $request->validated();

        $this->autorizarMototaxista(
            $request,
            (int) $datos['id_mototaxista']
        );

        $motocicleta =
            Motocicleta::create(
                $datos
            );

        return response()->json(
            $motocicleta->load([
                'mototaxista.persona',
                'mototaxista.sindicato',
            ]),
            201
        );
    }

    public function show(
        Request $request,
        $id
    ) {
        $motocicleta =
            $this->consultaVisible(
                $request
            )
                ->with([
                    'mototaxista.persona',
                    'mototaxista.sindicato',
                ])
                ->findOrFail($id);

        return response()->json(
            $motocicleta,
            200
        );
    }

    public function update(
        MotocicletaRequest $request,
        $id
    ) {
        $motocicleta =
            $this->consultaVisible(
                $request
            )->findOrFail($id);

        $datos = $request->validated();

        $this->autorizarMototaxista(
            $request,
            (int) $datos['id_mototaxista']
        );

        $motocicleta->update(
            $datos
        );

        return response()->json(
            $motocicleta->fresh([
                'mototaxista.persona',
                'mototaxista.sindicato',
            ]),
            200
        );
    }

    public function destroy(
        Request $request,
        $id
    ) {
        $motocicleta =
            $this->consultaVisible(
                $request
            )->findOrFail($id);

        $motocicleta->delete();

        return response()->json([
            'mensaje' =>
                'Motocicleta eliminada correctamente.',
        ]);
    }

    private function consultaVisible(
        Request $request
    ): Builder {
        $consulta =
            Motocicleta::query();

        if (
            $this->rol($request)
            === 'secretario'
        ) {
            $sindicatoId =
                $this->sindicatoUsuario(
                    $request
                );

            $consulta->whereHas(
                'mototaxista',
                function (
                    $query
                ) use (
                    $sindicatoId
                ) {
                    $query->where(
                        'id_sindicato',
                        $sindicatoId
                    );
                }
            );
        }

        return $consulta;
    }

    private function autorizarMototaxista(
        Request $request,
        int $mototaxistaId
    ): void {
        if (
            $this->rol($request)
            !== 'secretario'
        ) {
            return;
        }

        $sindicatoId =
            $this->sindicatoUsuario(
                $request
            );

        $pertenece =
            Mototaxista::query()
                ->where(
                    'id',
                    $mototaxistaId
                )
                ->where(
                    'id_sindicato',
                    $sindicatoId
                )
                ->exists();

        if (! $pertenece) {
            abort(
                403,
                'No puedes asignar una motocicleta a un mototaxista de otro sindicato.'
            );
        }
    }

    private function rol(
        Request $request
    ): string {
        return strtolower(
            trim(
                (string) (
                    $request->user()
                        ?->role
                    ?? ''
                )
            )
        );
    }

    private function sindicatoUsuario(
        Request $request
    ): int {
        $sindicatoId = (int) (
            $request->user()
                ?->sindicato_id
            ?? 0
        );

        if ($sindicatoId <= 0) {
            abort(
                403,
                'La cuenta de secretario no está vinculada a un sindicato.'
            );
        }

        return $sindicatoId;
    }
}
