<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\EstadoReclamo;
use App\Http\Requests\ReclamoCrearRequest;
use App\Mail\ReclamoRegistrado;
use App\Mail\ReclamoRegistradoAdmin;
use App\Models\Reclamo;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * Libro de Reclamaciones público (obligatorio por ley en Perú). La
 * constancia se muestra en la MISMA respuesta del POST, nunca en una URL
 * aparte: un reclamo trae datos personales sensibles (documento, domicilio,
 * teléfono) y no hay autenticación pública para protegerlos después si
 * quedaran accesibles por un id adivinable en la URL.
 */
class ReclamoController extends Controller
{
    public function index(): View
    {
        return view('libro-reclamaciones', [
            'titulo' => 'Libro de Reclamaciones - Enlix',
            'metaDescription' => 'Registra tu reclamo o queja sobre un producto o servicio de Enlix. Respondemos en un plazo máximo de 15 días hábiles.',
            'current' => 'libro-reclamaciones',
        ]);
    }

    public function store(ReclamoCrearRequest $request): View
    {
        $datos = $request->validated();

        $reclamo = Reclamo::create([
            'nombre_consumidor' => $datos['nombre_consumidor'],
            'tipo_documento_consumidor' => $datos['tipo_documento_consumidor'],
            'numero_documento_consumidor' => $datos['numero_documento_consumidor'],
            'domicilio_consumidor' => $datos['domicilio_consumidor'],
            'telefono_consumidor' => $datos['telefono_consumidor'],
            'correo_consumidor' => $datos['correo_consumidor'],
            'es_menor_edad' => $request->boolean('es_menor_edad'),
            'nombre_padre_tutor' => $datos['nombre_padre_tutor'] ?? null,
            'documento_padre_tutor' => $datos['documento_padre_tutor'] ?? null,
            'tipo_bien' => $datos['tipo_bien'],
            'descripcion_bien' => $datos['descripcion_bien'],
            'monto_centimos' => isset($datos['monto']) && $datos['monto'] !== ''
                ? (int) bcmul((string) $datos['monto'], '100')
                : null,
            'tipo' => $datos['tipo'],
            'detalle' => $datos['detalle'],
            'pedido_consumidor' => $datos['pedido_consumidor'],
            'estado' => EstadoReclamo::Pendiente,
            'acepto_privacidad_en' => now(),
            'fecha_limite_respuesta' => Reclamo::calcularFechaLimite(),
            'ip_origen' => $request->ip(),
        ]);

        Mail::to($reclamo->correo_consumidor)->queue(new ReclamoRegistrado($reclamo));

        $adminEmail = config('tienda.admin_email');
        if ($adminEmail) {
            Mail::to($adminEmail)->queue(new ReclamoRegistradoAdmin($reclamo));
        }

        return view('libro-reclamaciones-constancia', [
            'titulo' => 'Constancia de registro - Enlix',
            'reclamo' => $reclamo,
        ]);
    }
}
