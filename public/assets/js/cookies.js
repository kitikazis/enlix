/* =========================================================
   ENLIX - Aviso de cookies y Google Analytics 4
   - Analytics se carga solo después de que el visitante acepta.
   - La decisión se guarda en localStorage ('enlix_cookies').
   - Mide los clics de contacto: WhatsApp, llamada y correo.
   Markup en resources/views/partials/cookies.blade.php
   ========================================================= */
(function () {
  'use strict';

  var aviso = document.getElementById('cookies-aviso');
  if (!aviso) return;

  var CLAVE = 'enlix_cookies';
  var gaId = aviso.getAttribute('data-ga-id');
  var analyticsCargado = false;

  function leerDecision() {
    try { return localStorage.getItem(CLAVE); } catch (e) { return null; }
  }

  function guardarDecision(valor) {
    try { localStorage.setItem(CLAVE, valor); } catch (e) { /* modo privado: se volverá a preguntar */ }
  }

  function mostrarAviso(visible) {
    aviso.hidden = !visible;
  }

  function cargarAnalytics() {
    if (analyticsCargado || !gaId) return;
    analyticsCargado = true;

    window.dataLayer = window.dataLayer || [];
    window.gtag = function () { window.dataLayer.push(arguments); };
    window.gtag('js', new Date());
    window.gtag('config', gaId);

    var script = document.createElement('script');
    script.async = true;
    script.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(gaId);
    document.head.appendChild(script);
  }

  // Al rechazar después de haber aceptado, se borran las cookies _ga*.
  function borrarCookiesAnalytics() {
    var dominio = location.hostname.replace(/^www\./, '');
    document.cookie.split(';').forEach(function (c) {
      var nombre = c.split('=')[0].trim();
      if (nombre.indexOf('_ga') !== 0) return;
      ['', '; domain=' + dominio, '; domain=.' + dominio].forEach(function (d) {
        document.cookie = nombre + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/' + d;
      });
    });
  }

  aviso.addEventListener('click', function (ev) {
    var boton = ev.target.closest('[data-cookies]');
    if (!boton) return;

    if (boton.getAttribute('data-cookies') === 'aceptar') {
      guardarDecision('aceptadas');
      cargarAnalytics();
    } else {
      guardarDecision('rechazadas');
      borrarCookiesAnalytics();
      if (analyticsCargado) {
        // gtag ya está en memoria: se recarga para que deje de medir.
        location.reload();
        return;
      }
    }
    mostrarAviso(false);
  });

  // Enlace "Preferencias de cookies" del footer.
  document.addEventListener('click', function (ev) {
    if (ev.target.closest('[data-cookies-abrir]')) {
      ev.preventDefault();
      mostrarAviso(true);
    }
  });

  // ----- Eventos de contacto (conversiones) -----
  function ubicacion(enlace) {
    if (enlace.closest('.contacto-barra-movil')) return 'barra_movil';
    if (enlace.closest('.wa-float')) return 'boton_flotante';
    if (enlace.closest('.hero')) return 'portada';
    if (enlace.closest('.svc-sidebar')) return 'menu_servicios';
    if (enlace.closest('.enlix-footer')) return 'footer';
    return 'contenido';
  }

  document.addEventListener('click', function (ev) {
    if (!analyticsCargado) return;
    var enlace = ev.target.closest('a[href]');
    if (!enlace) return;

    var href = enlace.getAttribute('href');
    var evento = null;
    if (href.indexOf('wa.me/') !== -1) evento = 'contacto_whatsapp';
    else if (href.indexOf('tel:') === 0) evento = 'contacto_llamada';
    else if (href.indexOf('mailto:') === 0) evento = 'contacto_correo';
    if (!evento) return;

    window.gtag('event', evento, {
      ubicacion: ubicacion(enlace),
      pagina: location.pathname,
      transport_type: 'beacon'
    });
  });

  // ----- Estado inicial -----
  var decision = leerDecision();
  if (decision === 'aceptadas') {
    cargarAnalytics();
  } else if (decision !== 'rechazadas') {
    mostrarAviso(true);
  }
})();
