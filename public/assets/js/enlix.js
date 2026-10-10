(function () {
  'use strict';

  function isMobile() {
    return window.innerWidth < 992;
  }

  function initMobileMenu() {
    var nav = document.getElementById('enlixNav');
    if (!nav) return;

    // Prevent Bootstrap dropdown toggle from interfering on mobile
    var servicesToggle = document.querySelector('.enlix-flyout-menu > .nav-link[data-bs-toggle="dropdown"]');
    if (servicesToggle) {
      servicesToggle.addEventListener('click', function (e) {
        if (isMobile()) e.stopImmediatePropagation();
      }, true);
    }

    // Accordion: tap a category row to expand/collapse its sub-links
    nav.addEventListener('click', function (e) {
      if (!isMobile()) return;

      var flyoutLink = e.target.closest('.flyout-link');
      if (!flyoutLink) return;

      var flyoutItem = flyoutLink.closest('.flyout-item');
      if (!flyoutItem || !flyoutItem.querySelector('.flyout-sub')) return;

      e.preventDefault();

      var isOpen = flyoutItem.classList.contains('is-open');

      // Close all open items (one open at a time)
      nav.querySelectorAll('.flyout-item.is-open').forEach(function (el) {
        el.classList.remove('is-open');
      });

      if (!isOpen) {
        flyoutItem.classList.add('is-open');
        // Scroll the opened item into view
        setTimeout(function () {
          flyoutItem.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }, 50);
      }
    });

    // Reset accordions when hamburger closes the nav
    document.getElementById('enlixNav').addEventListener('hide.bs.collapse', function () {
      nav.querySelectorAll('.flyout-item.is-open').forEach(function (el) {
        el.classList.remove('is-open');
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMobileMenu);
  } else {
    initMobileMenu();
  }
})();

// Contador del carrito en el header. Global (no dentro del IIFE de arriba)
// porque lo usan los scripts inline de /productos y /carrito.
function actualizarBadgeCarrito(cantidad) {
  var badge = document.querySelector('[data-carrito-badge]');
  if (!badge) return;

  badge.textContent = cantidad;
  badge.classList.toggle('is-empty', cantidad < 1);

  // Reinicia la animación aunque ya estuviera corriendo.
  badge.classList.remove('enlix-bounce');
  void badge.offsetWidth;
  badge.classList.add('enlix-bounce');
}

/**
 * fetch() a JSON, tolerante a que el hosting compartido imprima un
 * warning/deprecation de PHP antes del JSON real (display_errors de
 * php.ini, independiente de APP_DEBUG) - eso rompe JSON.parse aunque el
 * servidor sí haya procesado la petición. Si el parseo directo falla,
 * reintenta desde el primer "{" antes de darse por vencido. Global, la usan
 * los scripts inline de /productos y /carrito.
 */
function fetchJsonEnlix(method, url, body, csrfToken) {
  return fetch(url, {
    method: method,
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': csrfToken,
    },
    body: body ? JSON.stringify(body) : null,
  }).then(function (r) {
    return r.text().then(function (texto) { return parsearJsonTolerante(texto, url); });
  });
}

function parsearJsonTolerante(texto, url) {
  try {
    return JSON.parse(texto);
  } catch (e) {
    var inicio = texto.indexOf('{');
    if (inicio > 0) {
      try {
        return JSON.parse(texto.slice(inicio));
      } catch (e2) {
        // sigue abajo con el error original
      }
    }
    console.error('Respuesta no-JSON de ' + url + ':', texto);
    throw e;
  }
}

/**
 * Modal "Producto agregado al carrito" (ver components/modal-carrito-agregado
 * en Blade, incluido una vez en layouts/app.blade.php). Se abre disparando
 * `window.dispatchEvent(new CustomEvent('cart:added', { detail: {...} }))`
 * DESPUES de que el servidor confirmo el agregado (nunca antes) - hoy lo
 * dispara productos.blade.php, pero cualquier página que incluya el layout
 * público puede reusarlo igual sin tocar este archivo.
 *
 * `detail` esperado: { itemId, nombre, imagenUrl, precioUnitarioCentimos,
 * precioComparacionCentimos (o null), cantidad, stockDisponible, botonOrigen
 * (el <button> que se clickeo, para devolver el foco ahi al cerrar) }.
 */
function initModalCarrito() {
  var overlay = document.querySelector('[data-modal-carrito]');
  if (!overlay) return; // paginas sin el layout público (ej. admin) no lo tienen

  var modal = overlay.querySelector('.enlix-modal-carrito');
  var urlItems = overlay.dataset.urlItems;
  var csrfToken = overlay.dataset.csrfToken;
  var formateador = new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' });

  var elImg = overlay.querySelector('[data-modal-carrito-img]');
  var elNombre = overlay.querySelector('[data-modal-carrito-nombre]');
  var elPrecio = overlay.querySelector('[data-modal-carrito-precio]');
  var elPrecioAnterior = overlay.querySelector('[data-modal-carrito-precio-anterior]');
  var elDescuento = overlay.querySelector('[data-modal-carrito-descuento]');
  var elCantidad = overlay.querySelector('[data-modal-carrito-cantidad]');
  var elMaximo = overlay.querySelector('[data-modal-carrito-maximo]');
  var elError = overlay.querySelector('[data-modal-carrito-error]');
  var btnRestar = overlay.querySelector('[data-modal-carrito-restar]');
  var btnSumar = overlay.querySelector('[data-modal-carrito-sumar]');

  var elementoQueAbrio = null;
  var itemId = null;
  var cantidadActual = 1;
  var stockMaximo = 1;
  var cerrando = null;
  var actualizando = false;

  window.addEventListener('cart:added', function (e) {
    abrir(e.detail || {});
  });

  overlay.addEventListener('click', function (e) {
    if (e.target === overlay) cerrar();
  });

  overlay.querySelectorAll('[data-modal-carrito-cerrar]').forEach(function (el) {
    el.addEventListener('click', cerrar);
  });

  btnRestar.addEventListener('click', function () {
    if (actualizando || cantidadActual <= 1) return;
    actualizarCantidad(cantidadActual - 1);
  });

  btnSumar.addEventListener('click', function () {
    if (actualizando || cantidadActual >= stockMaximo) return;
    actualizarCantidad(cantidadActual + 1);
  });

  document.addEventListener('keydown', function (e) {
    if (overlay.hidden) return;
    if (e.key === 'Escape') { cerrar(); return; }
    if (e.key === 'Tab') atraparFoco(e);
  });

  function abrir(detalle) {
    clearTimeout(cerrando);
    elementoQueAbrio = detalle.botonOrigen || document.activeElement;
    itemId = detalle.itemId;
    cantidadActual = detalle.cantidad || 1;
    stockMaximo = detalle.stockDisponible || 1;

    elImg.src = detalle.imagenUrl || '';
    elImg.alt = detalle.nombre || '';
    elNombre.textContent = detalle.nombre || '';
    elPrecio.textContent = formateador.format((detalle.precioUnitarioCentimos || 0) / 100);

    if (detalle.precioComparacionCentimos && detalle.precioComparacionCentimos > detalle.precioUnitarioCentimos) {
      var descuentoPct = Math.round((1 - detalle.precioUnitarioCentimos / detalle.precioComparacionCentimos) * 100);
      elPrecioAnterior.textContent = formateador.format(detalle.precioComparacionCentimos / 100);
      elPrecioAnterior.hidden = false;
      elDescuento.textContent = '-' + descuentoPct + '%';
      elDescuento.hidden = false;
    } else {
      elPrecioAnterior.hidden = true;
      elDescuento.hidden = true;
    }

    pintarCantidad();
    ocultarError();

    document.body.classList.add('enlix-modal-abierto');
    overlay.hidden = false;
    void overlay.offsetWidth; // fuerza reflow para que la transicion de entrada corra
    overlay.classList.add('is-open');
    modal.focus();
  }

  function cerrar(e) {
    if (e) e.preventDefault();
    if (overlay.hidden) return;

    overlay.classList.remove('is-open');
    document.body.classList.remove('enlix-modal-abierto');

    cerrando = setTimeout(function () {
      overlay.hidden = true;
    }, 200); // debe coincidir con la duracion de transicion en styles.css

    if (elementoQueAbrio && typeof elementoQueAbrio.focus === 'function') {
      elementoQueAbrio.focus();
    }
  }

  function pintarCantidad() {
    elCantidad.textContent = cantidadActual;
    elMaximo.textContent = 'Máximo ' + stockMaximo + ' unidad' + (stockMaximo === 1 ? '' : 'es');
    btnRestar.disabled = cantidadActual <= 1;
    btnSumar.disabled = cantidadActual >= stockMaximo;
  }

  function actualizarCantidad(nuevaCantidad) {
    ocultarError();
    actualizando = true;
    btnRestar.disabled = true;
    btnSumar.disabled = true;

    fetchJsonEnlix('PATCH', urlItems + '/' + itemId, { cantidad: nuevaCantidad }, csrfToken)
      .then(function (data) {
        if (!data.ok) {
          mostrarError(data.mensaje || 'No se pudo actualizar la cantidad.');
          return;
        }

        cantidadActual = nuevaCantidad;
        var item = data.items.find(function (i) { return i.id == itemId; });
        if (item) stockMaximo = item.stock_disponible;

        actualizarBadgeCarrito(data.cantidad_total);
      })
      .catch(function () {
        mostrarError('No se pudo confirmar el cambio. Intenta de nuevo.');
      })
      .then(function () {
        // corre siempre, haya o no fallado: nunca deja los botones trabados
        actualizando = false;
        pintarCantidad();
      });
  }

  function mostrarError(mensaje) {
    elError.textContent = mensaje;
    elError.hidden = false;
  }

  function ocultarError() {
    elError.hidden = true;
  }

  function atraparFoco(e) {
    var focusables = Array.prototype.slice
      .call(modal.querySelectorAll('a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])'))
      .filter(function (el) { return el.offsetParent !== null; });

    if (focusables.length === 0) return;

    var primero = focusables[0];
    var ultimo = focusables[focusables.length - 1];
    var activo = document.activeElement;

    if (e.shiftKey && (activo === primero || activo === modal)) {
      e.preventDefault();
      ultimo.focus();
    } else if (!e.shiftKey && activo === ultimo) {
      e.preventDefault();
      primero.focus();
    }
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initModalCarrito);
} else {
  initModalCarrito();
}
