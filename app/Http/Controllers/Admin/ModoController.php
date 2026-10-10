<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Modo de vista del panel admin: "real" (solo pagos reales, por defecto) o
 * "prueba" (incluye pagos de prueba). Es una preferencia de sesion, no de
 * base de datos ni de usuario: controla el default de "excluir_prueba" en
 * DashboardController y el tema visual de todo el panel (ver data-modo en
 * admin-dashboard.blade.php). No toca credenciales de Izipay ni el
 * procesamiento real de pagos, solo que datos se muestran.
 */
class ModoController extends Controller
{
    public function actualizar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'modo' => ['required', 'in:real,prueba'],
        ]);

        $request->session()->put('admin_modo_prueba', $datos['modo'] === 'prueba');

        return back();
    }
}
