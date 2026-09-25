<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * 20 productos de DEMOSTRACIÓN para probar catálogo/carrito/stock con varias
 * categorías y marcas reales. Precio y stock son inventados a pedido
 * explícito del cliente ("Si, crea/inventa, seran de prueba") - por eso se
 * crean INACTIVOS: el sitio ya cobra con Izipay en producción, así que no
 * deben quedar comprables hasta que alguien los revise y los active desde
 * el admin con datos reales.
 */
class ProductosDemoSeeder extends Seeder
{
    private const DESCRIPCION_CORTA = 'Producto de prueba — pendiente de contenido real.';

    private const DESCRIPCION = 'Este producto se creó como dato de prueba para validar el catálogo. '
        .'Reemplaza esta descripción, precio y stock con la información real desde el panel admin antes de activarlo.';

    /** [nombre, sku, categoria_slug, marca_slug, precio_soles, stock] */
    private const PRODUCTOS = [
        ['NVIDIA GeForce RTX 4060', 'GPU-001', 'tarjetas-graficas', 'nvidia', 1500.00, 10],
        ['NVIDIA GeForce RTX 4070', 'GPU-002', 'tarjetas-graficas', 'nvidia', 2500.00, 10],
        ['AMD Radeon RX 7600', 'GPU-003', 'tarjetas-graficas', 'amd', 1200.00, 10],
        ['AMD Ryzen 5 7600', 'CPU-001', 'procesadores', 'amd', 900.00, 10],
        ['AMD Ryzen 7 7800X3D', 'CPU-002', 'procesadores', 'amd', 1800.00, 10],
        ['Intel Core i5-13400F', 'CPU-003', 'procesadores', 'intel', 800.00, 10],
        ['Intel Core i7-13700K', 'CPU-004', 'procesadores', 'intel', 1900.00, 10],
        ['Kingston FURY Beast DDR5 32GB (2x16GB)', 'RAM-001', 'memoria-ram', 'kingston', 500.00, 10],
        ['Corsair Vengeance DDR5 16GB (2x8GB)', 'RAM-002', 'memoria-ram', 'corsair', 300.00, 10],
        ['Samsung 990 PRO NVMe 1TB', 'SSD-001', 'almacenamiento-ssd', 'samsung', 600.00, 10],
        ['WD Black SN770 NVMe 1TB', 'SSD-002', 'almacenamiento-ssd', 'western-digital', 450.00, 10],
        ['Kingston NV2 500GB NVMe', 'SSD-003', 'almacenamiento-ssd', 'kingston', 200.00, 10],
        ['ASUS TUF Gaming B650-PLUS', 'MB-001', 'placas-madre', 'asus', 700.00, 10],
        ['MSI PRO B760M-A', 'MB-002', 'placas-madre', 'msi', 500.00, 10],
        ['Gigabyte B550 AORUS ELITE', 'MB-003', 'placas-madre', 'gigabyte', 450.00, 10],
        ['Corsair RM750e 750W 80+ Gold', 'PSU-001', 'fuentes-de-poder', 'corsair', 400.00, 10],
        ['Cooler Master MasterBox NR400', 'CASE-001', 'gabinetes', 'cooler-master', 250.00, 10],
        ['NZXT H510 Flow', 'CASE-002', 'gabinetes', 'nzxt', 350.00, 10],
        ['Logitech G203', 'PERIPH-001', 'perifericos', 'logitech', 100.00, 10],
        ['Redragon K552 Kumara', 'PERIPH-002', 'perifericos', 'redragon', 150.00, 10],
    ];

    public function run(): void
    {
        foreach (self::PRODUCTOS as [$nombre, $sku, $categoriaSlug, $marcaSlug, $precioSoles, $stock]) {
            Producto::firstOrCreate(
                ['sku' => $sku],
                [
                    'categoria_id' => Categoria::where('slug', $categoriaSlug)->value('id'),
                    'marca_id' => Marca::where('slug', $marcaSlug)->value('id'),
                    'slug' => Str::slug($nombre),
                    'nombre' => $nombre,
                    'descripcion_corta' => self::DESCRIPCION_CORTA,
                    'descripcion' => self::DESCRIPCION,
                    'precio_centimos' => (int) bcmul((string) $precioSoles, '100'),
                    'stock' => $stock,
                    'activo' => false,
                ]
            );
        }
    }
}
