<?php

namespace App\Http\Controllers;

use App\Http\Requests\ServicioRequest;
use App\Models\Servicio;

class ServicioController extends Controller
{
    public function index()
    {
        // Trae el servicio + la solicitud (con su pasajero/persona) + el mototaxista (con su persona)
        return Servicio::with([
            'solicitud.pasajero.persona', 
            'mototaxista.persona'
        ])->get();
    }

    public function store(ServicioRequest $request)
    {
        $servicio = Servicio::create($request->validated());

        return response()->json($servicio, 201);
    }

    public function show($id)
    {
        return Servicio::findOrFail($id);
    }

    public function update(ServicioRequest $request, $id)
    {
        $servicio = Servicio::findOrFail($id);

        $servicio->update($request->validated());

        return response()->json($servicio, 200);
    }

    public function destroy($id)
    {
        Servicio::destroy($id);

        return response()->json([
            'mensaje' => 'Servicio eliminado'
        ]);
    }
}