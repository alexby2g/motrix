<?php

namespace App\Http\Controllers;

use App\Http\Requests\PersonaRequest;
use App\Models\Persona;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PersonaController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(
            $this->consultaVisible($request)
                ->with('imagenes')
                ->orderByDesc('id')
                ->get(),
            200
        );
    }

    public function show(
        Request $request,
        $id
    ) {
        $persona = $this->consultaVisible(
            $request
        )
            ->with([
                'imagenes',
                'mototaxista.sindicato',
                'pasajero',
                'usuarios',
            ])
            ->find($id);

        if (! $persona) {
            return response()->json([
                'mensaje' =>
                    'Persona no encontrada o fuera de tu ámbito.',
            ], 404);
        }

        return response()->json(
            $persona,
            200
        );
    }

    public function buscarPorCi(
        Request $request,
        $ci
    ) {
        $persona = $this->consultaVisible(
            $request
        )
            ->with('imagenes')
            ->where(
                'ci',
                trim((string) $ci)
            )
            ->first();

        if (! $persona) {
            return response()->json([
                'mensaje' =>
                    'No se encontró ninguna persona con ese CI dentro de tu ámbito.',
            ], 404);
        }

        return response()->json(
            $persona,
            200
        );
    }

    public function store(
        PersonaRequest $request
    ) {
        $datos = $request->validated();

        if (
            $this->rol($request)
            === 'secretario'
        ) {
            $datos['sindicato_registro_id'] =
                $this->sindicatoUsuario(
                    $request
                );
        }

        $persona = Persona::create(
            $datos
        );

        return response()->json([
            'mensaje' =>
                'Persona registrada correctamente.',
            'data' =>
                $persona->load('imagenes'),
        ], 201);
    }

    public function update(
        PersonaRequest $request,
        $id
    ) {
        $persona = $this->consultaVisible(
            $request
        )->find($id);

        if (! $persona) {
            return response()->json([
                'mensaje' =>
                    'Persona no encontrada o fuera de tu ámbito.',
            ], 404);
        }

        $datos = $request->validated();

        if (
            $this->rol($request)
            === 'secretario'
        ) {
            $datos['sindicato_registro_id'] =
                $this->sindicatoUsuario(
                    $request
                );
        }

        $persona->update($datos);

        return response()->json([
            'mensaje' =>
                'Persona actualizada correctamente.',
            'data' => $persona
                ->fresh()
                ->load('imagenes'),
        ], 200);
    }

    public function destroy(
        Request $request,
        $id
    ) {
        $persona = $this->consultaVisible(
            $request
        )
            ->with([
                'imagenes',
                'mototaxista',
                'pasajero',
                'usuarios',
            ])
            ->find($id);

        if (! $persona) {
            return response()->json([
                'mensaje' =>
                    'Persona no encontrada o fuera de tu ámbito.',
            ], 404);
        }

        if ($persona->mototaxista) {
            return response()->json([
                'mensaje' =>
                    'No se puede eliminar: esta persona está registrada como mototaxista.',
            ], 409);
        }

        if ($persona->pasajero) {
            return response()->json([
                'mensaje' =>
                    'No se puede eliminar: esta persona está registrada como pasajero.',
            ], 409);
        }

        if (
            $persona->usuarios
                ->isNotEmpty()
        ) {
            return response()->json([
                'mensaje' =>
                    'No se puede eliminar: esta persona tiene una cuenta de usuario asociada.',
            ], 409);
        }

        foreach (
            $persona->imagenes
            as $imagen
        ) {
            if (
                $imagen->ruta
                && Storage::disk('public')
                    ->exists(
                        $imagen->ruta
                    )
            ) {
                Storage::disk('public')
                    ->delete(
                        $imagen->ruta
                    );
            }
        }

        $persona->imagenes()
            ->delete();

        $persona->delete();

        return response()->json([
            'mensaje' =>
                'Persona eliminada correctamente.',
        ], 200);
    }

    private function consultaVisible(
        Request $request
    ): Builder {
        $consulta = Persona::query();

        $rol = $this->rol(
            $request
        );

        if ($rol === 'admin_general') {
            return $consulta;
        }

        if ($rol === 'secretario') {
            $sindicatoId =
                $this->sindicatoUsuario(
                    $request
                );

            return $consulta->where(
                function ($query) use (
                    $sindicatoId
                ) {
                    $query
                        ->where(
                            'sindicato_registro_id',
                            $sindicatoId
                        )
                        ->orWhereHas(
                            'mototaxista',
                            function (
                                $mototaxista
                            ) use (
                                $sindicatoId
                            ) {
                                $mototaxista
                                    ->where(
                                        'id_sindicato',
                                        $sindicatoId
                                    );
                            }
                        );
                }
            );
        }

        if (
            $rol === 'admin_servicios'
        ) {
            /*
             * El administrador de servicios trabaja
             * con clientes/pasajeros, no con el
             * padrón sindical de mototaxistas.
             *
             * También se muestran personas todavía
             * libres para poder registrarlas como
             * pasajero.
             */
            return $consulta->where(
                function ($query) {
                    $query
                        ->whereHas(
                            'pasajero'
                        )
                        ->orWhereDoesntHave(
                            'mototaxista'
                        );
                }
            );
        }

        abort(
            403,
            'No tienes autorización para consultar personas.'
        );
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
