<?php

namespace App\Http\Controllers;

use App\Models\PushDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PushDeviceController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'min:20', 'max:512'],
            'platform' => [
                'nullable',
                'string',
                Rule::in(['android', 'ios', 'web']),
            ],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        $device = PushDevice::query()->updateOrCreate(
            ['token' => $data['token']],
            [
                'user_id' => $request->user()->id,
                'platform' => $data['platform'] ?? null,
                'device_name' => $data['device_name'] ?? null,
                'active' => true,
                'last_seen_at' => now(),
            ]
        );

        return response()->json([
            'message' => 'Dispositivo registrado para notificaciones MOTRIX.',
            'device_id' => $device->id,
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:512'],
        ]);

        PushDevice::query()
            ->where('user_id', $request->user()->id)
            ->where('token', $data['token'])
            ->update(['active' => false]);

        return response()->json([
            'message' => 'Notificaciones desactivadas para este dispositivo.',
        ]);
    }
}
