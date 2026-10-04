<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class FederationBoundary
{
    public function handle(Request $request, Closure $next)
    {
        if (strtolower(trim((string) $request->user()?->role)) === 'representante_federacion') {
            $path = $request->path();
            $resource = preg_match('#^api/(personas|mototaxistas|sindicatos|federaciones|motocicletas|imagen|imagenes-personas)(/|$)#', $path);
            if ($resource) {
                $read = $request->isMethod('GET') && preg_match('#^api/(personas(?:/opciones-mototaxista|/buscar/[^/]+|/\d+)?|mototaxistas(?:/\d+)?|sindicatos(?:/\d+)?|federaciones(?:/\d+)?)$#', $path);
                $create = $request->isMethod('POST') && in_array($path, ['api/personas', 'api/mototaxistas'], true);
                abort_unless($read || $create, 403, 'El representante puede consultar y registrar afiliados; esta acción administrativa no está autorizada.');
            }
        }
        return $next($request);
    }
}
