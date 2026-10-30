{{-- Compra compartida entre el catálogo y la página de detalle: botones
     "Comprar ahora" y "Agregar al carrito". --}}
@push('scripts')
<script nonce="{{ $cspNonce }}">
  const CSRF_TOKEN = @json(csrf_token());
  const URL_AGREGAR_CARRITO = @json(route('carrito.items.store'));
  const URL_RESUMEN_CARRITO = @json(route('carrito.resumen'));
  const URL_ACTUALIZAR_ITEM = @json(route('carrito.items.update', '__ITEM__'));
  const URL_CHECKOUT = @json(route('checkout.index'));

  // Cantidad del selector de la página de detalle; null donde no hay selector
  // (tarjetas del catálogo y oferta del día).
  function cantidadElegida(btn) {
    var zona = btn.closest('[data-oferta-zona]');
    var valor = zona && zona.querySelector('[data-cantidad-valor]');
    return valor ? parseInt(valor.textContent, 10) : null;
  }

  // "Comprar ahora": agrega el producto al carrito (si no estaba ya) y va
  // directo al checkout SOLO con ese item (?items=), sin arrastrar lo que
  // hubiera antes en el carrito. La oferta tiene dos botones (portada y ficha
  // técnica); cada uno muestra el error en su propia zona.
  document.querySelectorAll('[data-oferta-comprar]').forEach(function (btn) {
    var error = btn.closest('[data-oferta-zona]').querySelector('[data-oferta-error]');
    var textoOriginal = btn.textContent.trim();
    var productoId = parseInt(btn.dataset.productoId, 10);

    function irAlCheckout(itemId) {
      window.location.href = URL_CHECKOUT + '?items=' + encodeURIComponent(itemId);
    }

    function fallar(mensaje) {
      error.textContent = mensaje;
      error.hidden = false;
      btn.disabled = false;
      btn.removeAttribute('aria-busy');
      btn.textContent = textoOriginal;
    }

    function itemDeOferta(data) {
      return (data.items || []).find(function (i) { return i.producto_id === productoId; });
    }

    btn.addEventListener('click', function () {
      if (btn.disabled) return;

      btn.disabled = true;
      btn.setAttribute('aria-busy', 'true');
      btn.textContent = 'Procesando…';
      error.hidden = true;

      fetchJsonEnlix('GET', URL_RESUMEN_CARRITO, null, CSRF_TOKEN).then(function (resumen) {
        var existente = itemDeOferta(resumen);
        var cantidad = cantidadElegida(btn);
        if (existente) {
          if (!cantidad || existente.cantidad === cantidad) {
            irAlCheckout(existente.id);
            return;
          }

          // Ya estaba en el carrito con otra cantidad: se compra la elegida aquí.
          return fetchJsonEnlix('PATCH', URL_ACTUALIZAR_ITEM.replace('__ITEM__', existente.id), { cantidad: cantidad }, CSRF_TOKEN).then(function (data) {
            if (!data.ok) {
              fallar(data.mensaje || 'No se pudo actualizar la cantidad.');
              return;
            }
            actualizarBadgeCarrito(data.cantidad_total);
            irAlCheckout(existente.id);
          });
        }

        return fetchJsonEnlix('POST', URL_AGREGAR_CARRITO, { producto_id: productoId, cantidad: cantidad || 1 }, CSRF_TOKEN).then(function (data) {
          if (!data.ok) {
            fallar(data.mensaje || 'No se pudo agregar al carrito.');
            return;
          }
          actualizarBadgeCarrito(data.cantidad_total);
          var item = itemDeOferta(data);
          if (item) {
            irAlCheckout(item.id);
          } else {
            window.location.href = URL_CHECKOUT;
          }
        });
      }).catch(function () {
        refrescarBadgeCarrito();
        fallar('No se pudo procesar. Revisa tu carrito e inténtalo de nuevo.');
      });
    });
  });

  document.querySelectorAll('.btn-agregar-carrito').forEach(function (btn) {
    const textoOriginal = btn.textContent.trim();

    btn.addEventListener('click', function () {
      if (btn.disabled) return;

      btn.disabled = true;
      ocultarErrorCarrito(btn);

      fetchJsonEnlix('POST', URL_AGREGAR_CARRITO, {
        producto_id: parseInt(btn.dataset.productoId, 10),
        cantidad: cantidadElegida(btn) || 1,
      }, CSRF_TOKEN).then(function (data) {
        if (!data.ok) {
          mostrarErrorCarrito(btn, data.mensaje || 'No se pudo agregar al carrito.');
          btn.disabled = false;
          return;
        }

        actualizarBadgeCarrito(data.cantidad_total);

        // El modal (ver initModalCarrito() en enlix.js) solo se abre si
        // encontramos el item recien agregado en la respuesta del servidor
        // - nunca antes de confirmar el OK.
        var productoId = parseInt(btn.dataset.productoId, 10);
        var item = data.items.find(function (i) { return i.producto_id === productoId; });
        if (item) {
          window.dispatchEvent(new CustomEvent('cart:added', {
            detail: {
              itemId: item.id,
              nombre: item.nombre,
              imagenUrl: btn.dataset.imagenUrl,
              precioUnitarioCentimos: item.precio_unitario_centimos,
              precioComparacionCentimos: btn.dataset.precioComparacionCentimos
                ? parseInt(btn.dataset.precioComparacionCentimos, 10)
                : null,
              cantidad: item.cantidad,
              stockDisponible: item.stock_disponible,
              botonOrigen: btn,
            },
          }));
        }

        // Feedback de "agregado" durante 1.5s, como pide el diseño.
        btn.textContent = '✓ Agregado';
        setTimeout(function () {
          btn.textContent = textoOriginal;
          btn.disabled = false;
        }, 1500);
      }).catch(function () {
        // La petición pudo haber llegado bien al servidor aunque la
        // respuesta no se pudo leer como JSON (ver fetchJsonEnlix en
        // enlix.js) - se refresca el contador real en vez de asumir que no
        // se agregó nada.
        refrescarBadgeCarrito();
        mostrarErrorCarrito(btn, 'No se pudo confirmar el agregado. Revisa tu carrito antes de reintentar.');
        btn.disabled = false;
      });
    });
  });

  function refrescarBadgeCarrito() {
    fetchJsonEnlix('GET', URL_RESUMEN_CARRITO, null, CSRF_TOKEN)
      .then(function (data) { actualizarBadgeCarrito(data.cantidad_total); })
      .catch(function () {});
  }

  function mostrarErrorCarrito(btn, mensaje) {
    const card = btn.closest('.prod-card, [data-oferta-zona]');
    let error = card.querySelector('.prod-card-error');
    if (!error) {
      error = document.createElement('p');
      error.className = 'prod-card-error text-danger mt-2 mb-0';
      card.appendChild(error);
    }
    error.textContent = mensaje;
  }

  function ocultarErrorCarrito(btn) {
    const error = btn.closest('.prod-card, [data-oferta-zona]').querySelector('.prod-card-error');
    if (error) error.remove();
  }
</script>
@endpush
