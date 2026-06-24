<?php

namespace App\Support;

/**
 * Catálogo de servicios de Enlix.
 *
 * Reemplaza al antiguo helper enlix_grupos_servicios() del sitio en PHP plano.
 * Lee los datos desde config/servicios.php.
 */
class Catalogo
{
    /** Todos los servicios (slug => datos). */
    public static function items(): array
    {
        return config('servicios.items', []);
    }

    /** Un servicio por su slug, o null si no existe. */
    public static function find(string $slug): ?array
    {
        return config("servicios.items.$slug");
    }

    /**
     * Devuelve los servicios agrupados por categoría, en el orden correcto.
     * Estructura: [ group_slug => ['slug','label','num','items'=>[...]] ]
     */
    public static function grupos(): array
    {
        $items  = config('servicios.items', []);
        $orden  = config('servicios.orden', []);
        $labels = config('servicios.labels', []);
        $nums   = config('servicios.nums', []);

        $grupos = [];
        foreach ($orden as $gslug) {
            $grupos[$gslug] = [
                'slug'  => $gslug,
                'label' => $labels[$gslug] ?? $gslug,
                'num'   => $nums[$gslug] ?? '',
                'items' => [],
            ];
        }

        foreach ($items as $slug => $svc) {
            if (isset($grupos[$svc['group_slug']])) {
                $grupos[$svc['group_slug']]['items'][] = [
                    'slug'  => $slug,
                    'short' => $svc['short'],
                    'title' => $svc['title'],
                ];
            }
        }

        return $grupos;
    }

    /** Otros servicios del mismo grupo (excluye el actual). */
    public static function otrosDelGrupo(string $slug): array
    {
        $svc = self::find($slug);
        if (! $svc) {
            return [];
        }

        $otros  = [];
        $grupos = self::grupos();
        foreach ($grupos[$svc['group_slug']]['items'] as $it) {
            if ($it['slug'] !== $slug) {
                $otros[] = $it;
            }
        }

        return $otros;
    }
}
