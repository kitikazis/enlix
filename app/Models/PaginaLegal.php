<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Mews\Purifier\Facades\Purifier;

/**
 * Contenido editable (desde el admin, sin tocar código) de las páginas
 * legales públicas. obtener() crea la fila con un borrador placeholder la
 * primera vez que se pide un slug que todavía no existe en BD, para que la
 * página pública y el editor del admin nunca se encuentren con un 404 antes
 * de que alguien la edite.
 *
 * El contenido real (razón social, RUC, plazos, etc.) lo define el negocio;
 * ver los marcadores entre corchetes en defaults(). Un abogado debe revisar
 * el texto antes de quitar el aviso de borrador del inicio de cada página.
 */
class PaginaLegal extends Model
{
    protected $table = 'paginas_legales';

    public const SLUG_TERMINOS = 'terminos-y-condiciones';

    public const SLUG_PRIVACIDAD = 'politica-de-privacidad';

    private const AVISO_BORRADOR = '<p><strong>Aviso interno (quitar antes de publicar):</strong> '
        .'este texto es un borrador con información pendiente de completar (marcada entre corchetes) '
        .'y debe ser revisado por un abogado antes de considerarse la versión definitiva.</p>';

    /**
     * Tags mas amplios que Producto::descripcion() (agrega h2/h3/h4 y
     * blockquote): el contenido legal necesita estructura para el indice de
     * secciones de la vista publica (ver toc()).
     */
    private const CONFIG_CONTENIDO_HTML = [
        'HTML.Allowed' => 'h2,h3,h4,p,br,strong,b,em,i,u,ul,ol,li,blockquote,a[href|title|target|rel]',
        'HTML.TargetBlank' => true,
        'AutoFormat.RemoveEmpty' => true,
    ];

    protected $fillable = [
        'slug',
        'titulo',
        'contenido',
        'meta_title',
        'meta_description',
    ];

    protected function contenido(): Attribute
    {
        return Attribute::make(
            set: fn (?string $valor) => $valor !== null ? Purifier::clean($valor, self::CONFIG_CONTENIDO_HTML) : null,
        );
    }

    public static function obtener(string $slug): self
    {
        return static::firstOrCreate(['slug' => $slug], self::defaults($slug));
    }

    /** Permite {pagina} en las rutas del admin ligado por slug, no por id. */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Agrega un id="" a cada <h2>/<h3> del contenido (para poder enlazar el
     * indice de secciones) y devuelve [html_con_ids, tabla_de_contenido].
     *
     * @return array{0: string, 1: array<int, array{id: string, texto: string, nivel: int}>}
     */
    public function contenidoConIndice(): array
    {
        $toc = [];
        $usados = [];

        $html = preg_replace_callback(
            '/<h([23])>(.*?)<\/h\1>/s',
            function (array $m) use (&$toc, &$usados) {
                $nivel = (int) $m[1];
                $texto = trim(strip_tags($m[2]));
                $id = Str::slug($texto) ?: 'seccion';

                // Si dos encabezados generan el mismo slug, numera el
                // segundo para no duplicar el id y que el enlace del indice
                // apunte siempre a la seccion correcta.
                $idUnico = $id;
                $i = 2;
                while (isset($usados[$idUnico])) {
                    $idUnico = $id.'-'.$i;
                    $i++;
                }
                $usados[$idUnico] = true;

                $toc[] = ['id' => $idUnico, 'texto' => $texto, 'nivel' => $nivel];

                return '<h'.$nivel.' id="'.$idUnico.'">'.$m[2].'</h'.$nivel.'>';
            },
            $this->contenido
        );

        return [$html, $toc];
    }

    private static function defaults(string $slug): array
    {
        return match ($slug) {
            self::SLUG_TERMINOS => [
                'titulo' => 'Términos y condiciones',
                'meta_title' => 'Términos y condiciones - Enlix',
                'meta_description' => 'Condiciones de venta, precios, medios de pago, envíos, cambios, devoluciones y garantías de Enlix.',
                'contenido' => self::AVISO_BORRADOR.self::contenidoTerminos(),
            ],
            self::SLUG_PRIVACIDAD => [
                'titulo' => 'Política de privacidad',
                'meta_title' => 'Política de privacidad - Enlix',
                'meta_description' => 'Cómo Enlix recoge, usa y protege los datos personales de sus clientes, y cómo ejercer tus derechos ARCO.',
                'contenido' => self::AVISO_BORRADOR.self::contenidoPrivacidad(),
            ],
            default => [
                'titulo' => ucfirst(str_replace('-', ' ', $slug)),
                'meta_title' => null,
                'meta_description' => null,
                'contenido' => self::AVISO_BORRADOR,
            ],
        };
    }

    private static function contenidoTerminos(): string
    {
        $libroReclamaciones = route('libro-reclamaciones.index');

        return <<<HTML
        <h2>1. Identificación del proveedor</h2>
        <p>La venta de los productos y servicios ofrecidos en este sitio es realizada por <strong>[RAZÓN SOCIAL]</strong>, identificada con RUC <strong>[RUC]</strong>, con domicilio en <strong>[DOMICILIO FISCAL]</strong>. Puedes contactarnos al correo <a href="mailto:servicios@enlix.pe">servicios@enlix.pe</a> o al teléfono +51 963 885 176.</p>

        <h2>2. Proceso de compra</h2>
        <p>El cliente selecciona los productos deseados, los agrega al carrito y completa el proceso de compra ingresando sus datos de contacto, documento de identidad y método de entrega. El pedido queda confirmado una vez que el pago es aprobado por la pasarela de pago.</p>

        <h2>3. Precios e IGV</h2>
        <p>Los precios mostrados en el sitio están expresados en soles (S/) e incluyen el Impuesto General a las Ventas (IGV). Nos reservamos el derecho de modificar los precios publicados en cualquier momento, sin que esto afecte los pedidos ya confirmados.</p>

        <h2>4. Medios de pago</h2>
        <p>Aceptamos pagos con tarjeta de crédito o débito a través de nuestra pasarela de pago Izipay. [Confirmar si también se aceptan Yape, Plin u otros medios adicionales]. No almacenamos los datos de tu tarjeta: el pago se procesa directamente en los servidores de la pasarela.</p>

        <h2>5. Envíos y plazos de entrega</h2>
        <p>El plazo de entrega estimado es de <strong>[PLAZO DE ENTREGA LIMA]</strong> días hábiles para Lima y <strong>[PLAZO DE ENTREGA PROVINCIA]</strong> días hábiles para provincias, contados desde la confirmación del pago. El costo de envío se indica durante el proceso de compra.</p>

        <h2>6. Cambios y devoluciones</h2>
        <p>El cliente puede solicitar el cambio o la devolución de un producto dentro de los <strong>[PLAZO DE DEVOLUCIÓN]</strong> días calendario siguientes a la recepción, siempre que el producto se encuentre sin uso, con su empaque original y accesorios completos. [Detallar aquí excepciones y el procedimiento exacto para solicitar el cambio o devolución].</p>

        <h2>7. Garantías</h2>
        <p>Los productos cuentan con garantía del fabricante o del proveedor según se indique en la ficha de cada producto. [Detallar el procedimiento general para hacer válida la garantía].</p>

        <h2>8. Comprobantes de pago</h2>
        <p>Por cada compra realizada emitimos una boleta de venta electrónica, remitida al correo registrado por el cliente durante el proceso de compra.</p>

        <h2>9. Libro de Reclamaciones</h2>
        <p>Conforme a la normativa de protección al consumidor vigente en el Perú, contamos con un Libro de Reclamaciones virtual, disponible <a href="{$libroReclamaciones}">aquí</a>, donde puedes registrar tus quejas o reclamos relacionados con los productos o servicios adquiridos.</p>
        HTML;
    }

    private static function contenidoPrivacidad(): string
    {
        return <<<'HTML'
        <h2>1. Titular del banco de datos</h2>
        <p>El titular del banco de datos personales de clientes de este sitio es <strong>[RAZÓN SOCIAL]</strong>, identificada con RUC <strong>[RUC]</strong>, [inscrita / pendiente de inscripción] ante la Autoridad Nacional de Protección de Datos Personales con el registro N.° [NÚMERO DE REGISTRO, si aplica].</p>

        <h2>2. Qué datos se recogen</h2>
        <p>Recogemos los datos que nos proporcionas al realizar una compra o al escribirnos: nombre, apellido, correo electrónico, teléfono, documento de identidad, dirección de entrega (si corresponde) y, en caso de reclamos, la información que registres en el Libro de Reclamaciones.</p>

        <h2>3. Finalidad</h2>
        <p>Usamos tus datos para procesar tu pedido, coordinar la entrega, emitir el comprobante de pago correspondiente, atender consultas o reclamos, y comunicarnos contigo sobre el estado de tu compra.</p>

        <h2>4. Plazo de conservación</h2>
        <p>Conservamos tus datos personales durante <strong>[PLAZO DE CONSERVACIÓN]</strong>, o mientras sea necesario para cumplir con obligaciones legales, contables o tributarias aplicables.</p>

        <h2>5. Terceros con acceso a tus datos</h2>
        <p>Compartimos tus datos únicamente con los terceros necesarios para completar tu compra: nuestra pasarela de pago (Izipay) y <strong>[EMPRESA DE COURIER/ENVÍOS]</strong> para la entrega de productos, además de las entidades que la ley nos obligue a informar.</p>

        <h2>6. Medidas de seguridad</h2>
        <p>Aplicamos medidas técnicas y organizativas razonables para proteger tus datos personales contra accesos no autorizados, pérdida o alteración. [Detallar medidas específicas, si se desea].</p>

        <h2>7. Derechos ARCO</h2>
        <p>Puedes ejercer tus derechos de Acceso, Rectificación, Cancelación y Oposición (derechos ARCO) sobre tus datos personales escribiéndonos a <a href="mailto:[CORREO DERECHOS ARCO]">[CORREO DERECHOS ARCO]</a>, indicando tu nombre completo y el derecho que deseas ejercer.</p>
        HTML;
    }
}
