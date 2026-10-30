{{--
    Modal "Producto agregado al carrito", incluido UNA vez en layouts/app.blade.php
    para que esté disponible en cualquier página pública (hoy el catálogo y la
    página de detalle de producto).

    No recibe datos por props: se queda vacío/oculto en el HTML y se rellena por
    completo en JS (ver initModalCarrito() en enlix.js) cuando algo dispara el
    evento global `cart:added` en window con el detalle del producto agregado.
    Así un solo componente sirve para cualquier botón "Agregar al carrito" del
    sitio sin acoplarlo a una página en particular.
--}}
<div
    class="enlix-modal-overlay"
    id="enlixModalCarrito"
    data-modal-carrito
    data-url-items="{{ route('carrito.items.store') }}"
    data-csrf-token="{{ csrf_token() }}"
    hidden
>
    <div
        class="enlix-modal-carrito"
        role="dialog"
        aria-modal="true"
        aria-labelledby="enlixModalCarritoTitulo"
        tabindex="-1"
    >
        <div class="enlix-modal-carrito-header">
            <span class="enlix-modal-carrito-check" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            </span>
            <h2 id="enlixModalCarritoTitulo" class="enlix-modal-carrito-titulo">Producto agregado a tu carrito</h2>
            <button type="button" class="enlix-modal-carrito-cerrar" data-modal-carrito-cerrar aria-label="Cerrar">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        <div class="enlix-modal-carrito-body">
            <div class="enlix-modal-carrito-producto">
                <img class="enlix-modal-carrito-img" data-modal-carrito-img src="" alt="">
                <div class="enlix-modal-carrito-info">
                    <p class="enlix-modal-carrito-nombre" data-modal-carrito-nombre></p>
                    <div class="enlix-modal-carrito-precios">
                        <span class="enlix-modal-carrito-precio" data-modal-carrito-precio></span>
                        <span class="enlix-modal-carrito-precio-anterior" data-modal-carrito-precio-anterior hidden></span>
                        <span class="enlix-modal-carrito-descuento" data-modal-carrito-descuento hidden></span>
                    </div>
                </div>
            </div>

            <div class="enlix-modal-carrito-cantidad-fila">
                <span class="enlix-modal-carrito-cantidad-label">Cantidad</span>
                <div class="enlix-modal-carrito-stepper">
                    <button type="button" class="enlix-modal-carrito-btn-cantidad" data-modal-carrito-restar aria-label="Restar una unidad">−</button>
                    <span class="enlix-modal-carrito-cantidad-valor" data-modal-carrito-cantidad aria-live="polite">1</span>
                    <button type="button" class="enlix-modal-carrito-btn-cantidad" data-modal-carrito-sumar aria-label="Agregar una unidad">+</button>
                </div>
                <span class="enlix-modal-carrito-maximo" data-modal-carrito-maximo></span>
            </div>

            <p class="enlix-modal-carrito-error" data-modal-carrito-error hidden></p>
        </div>

        <div class="enlix-modal-carrito-footer">
            <button type="button" class="enlix-modal-carrito-seguir" data-modal-carrito-cerrar>Seguir comprando</button>
            <a href="{{ route('carrito.index') }}" class="btn btn-primary-enlix enlix-modal-carrito-ir">Ir al carrito</a>
        </div>
    </div>
</div>
