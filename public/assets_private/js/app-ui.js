/* ============================================================================
   MadBarzz · Capa de experiencia (app-ui.js)
   ----------------------------------------------------------------------------
   Mejoras de UX puramente del lado del cliente. NO altera peticiones, rutas
   ni datos: solo enriquece el feedback visual reutilizando librerías que la
   plantilla ya incluye (Toastr, SweetAlert2, Bootstrap, DataTables).
   ============================================================================ */
(function () {
    'use strict';

    /* ----------------------------------------------------------------
       1) NOTIFICACIONES TOAST  (a partir de los mensajes flash de Laravel)
       El layout inyecta window.MB_FLASH con success/error/warning/info y la
       lista de errores de validación. Aquí los mostramos como toasts.
    ---------------------------------------------------------------- */
    function initToasts() {
        if (typeof toastr === 'undefined') return;
        toastr.options = {
            closeButton: true,
            progressBar: true,
            newestOnTop: true,
            positionClass: 'toast-top-right',
            timeOut: 4500,
            extendedTimeOut: 1500,
            preventDuplicates: true
        };
        var f = window.MB_FLASH || {};
        if (f.success) toastr.success(f.success, '¡Listo!');
        if (f.error)   toastr.error(f.error, 'Ups…');
        if (f.warning) toastr.warning(f.warning, 'Atención');
        if (f.info)    toastr.info(f.info);
        if (f.status)  toastr.success(f.status);
        if (Array.isArray(f.errors) && f.errors.length) {
            toastr.error(
                f.errors.map(function (e) { return '• ' + e; }).join('<br>'),
                'Revisa el formulario',
                { timeOut: 8000, escapeHtml: false }
            );
        }
    }

    /* ----------------------------------------------------------------
       2) CONFIRMACIONES ELEGANTES (SweetAlert2)
       Sustituye el confirm() nativo de los formularios de eliminación.
       Detecta: formularios con method DELETE o atributo [data-confirm].
    ---------------------------------------------------------------- */
    function initConfirmations() {
        var hasSwal = typeof Swal !== 'undefined';

        document.querySelectorAll('form').forEach(function (form) {
            var isDelete = form.querySelector('input[name="_method"][value="DELETE"]') ||
                           (form.getAttribute('method') || '').toUpperCase() === 'DELETE' ||
                           form.hasAttribute('data-confirm');
            if (!isDelete) return;

            // Neutralizamos el confirm() nativo inline para no duplicar diálogos.
            form.onsubmit = null;
            form.removeAttribute('onsubmit');

            form.addEventListener('submit', function (e) {
                if (form.dataset.mbConfirmed === '1') return; // ya confirmado
                e.preventDefault();

                var msg = form.getAttribute('data-confirm') ||
                          '¿Seguro que deseas eliminar este registro? Esta acción no se puede deshacer.';
                // (el SweetAlert usa data-confirm-title / data-confirm-button si existen)

                if (!hasSwal) {
                    if (window.confirm(msg)) { form.dataset.mbConfirmed = '1'; form.submit(); }
                    return;
                }

                // Textos/color por formulario (opcionales): permiten que acciones que no
                // eliminan nada (ej. desbloquear un login) no hablen de "eliminar" ni se
                // pinten en rojo. Sin data-confirm-color el botón sigue en rojo (borrado).
                var positivo = form.getAttribute('data-confirm-color');
                Swal.fire({
                    title: form.getAttribute('data-confirm-title') || '¿Eliminar registro?',
                    text: msg,
                    icon: form.getAttribute('data-confirm-icon') || 'warning',
                    showCancelButton: true,
                    confirmButtonText: form.getAttribute('data-confirm-button') || 'Sí, eliminar',
                    cancelButtonText: 'Cancelar',
                    reverseButtons: true,
                    focusCancel: true,
                    customClass: { confirmButton: positivo ? 'btn btn-success' : 'btn btn-danger', cancelButton: 'btn btn-secondary' },
                    buttonsStyling: true,
                    confirmButtonColor: positivo || '#f5365c',
                    cancelButtonColor: '#6c757d'
                }).then(function (r) {
                    if (r.isConfirmed) { form.dataset.mbConfirmed = '1'; form.submit(); }
                });
            });
        });
    }

    /* ----------------------------------------------------------------
       3) ESTADO DE CARGA EN BOTONES (loaders)
       Al enviar un formulario (no GET) el botón muestra un spinner.
    ---------------------------------------------------------------- */
    function initButtonLoaders() {
        document.querySelectorAll('form').forEach(function (form) {
            if ((form.getAttribute('method') || 'post').toUpperCase() === 'GET') return;
            form.addEventListener('submit', function () {
                var btn = form.querySelector('button[type="submit"], button:not([type])');
                if (!btn || btn.dataset.mbLoading === '1') return;
                btn.dataset.mbLoading = '1';
                btn.dataset.mbHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Procesando…';
                // Seguridad: si la navegación se cancela, restaurar a los 8s.
                setTimeout(function () {
                    if (btn.dataset.mbLoading === '1') {
                        btn.disabled = false;
                        btn.innerHTML = btn.dataset.mbHtml;
                        btn.dataset.mbLoading = '0';
                    }
                }, 8000);
            });
        });
    }

    /* ----------------------------------------------------------------
       4) TOOLTIPS (Bootstrap 5)
    ---------------------------------------------------------------- */
    function initTooltips() {
        if (typeof bootstrap === 'undefined' || !bootstrap.Tooltip) return;
        document.querySelectorAll('[title]:not([data-bs-toggle]), [data-bs-toggle="tooltip"]').forEach(function (el) {
            if (!el.getAttribute('title')) return;
            new bootstrap.Tooltip(el, { container: 'body' });
        });
    }

    /* ----------------------------------------------------------------
       5) DATATABLES (opt-in)  ->  agrega class="mb-datatable" a una <table>
       Solo se activa donde tú lo pidas explícitamente, para no chocar con los
       buscadores del lado del servidor que ya existen.
    ---------------------------------------------------------------- */
    function initDataTables() {
        if (typeof jQuery === 'undefined' || !jQuery.fn || !jQuery.fn.DataTable) return;
        jQuery('table.mb-datatable').each(function () {
            jQuery(this).DataTable({
                language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
                pageLength: 10,
                lengthChange: false,
                order: []
            });
        });
    }

    /* ----------------------------------------------------------------
       6) PREVISUALIZACIÓN DE IMAGEN  ->  <input type="file" data-preview="#sel">
    ---------------------------------------------------------------- */
    function initImagePreview() {
        document.querySelectorAll('input[type="file"][data-preview]').forEach(function (input) {
            input.addEventListener('change', function () {
                var target = document.querySelector(input.getAttribute('data-preview'));
                if (target && input.files && input.files[0]) {
                    target.src = URL.createObjectURL(input.files[0]);
                    target.style.display = 'inline-block';
                }
            });
        });
    }

    /* ----------------------------------------------------------------
       7) SCROLLBAR DOBLE — siempre arriba, sigue al bajar
       Dos rieles por tabla:
         · staticRail  → en flujo normal, visible cuando la tabla está en pantalla
         · fixedRail   → position:fixed, aparece cuando el staticRail sale de vista
       Solo uno se muestra a la vez; ambos sincronizan scrollLeft con la tabla.
    ---------------------------------------------------------------- */
    function initTopScrollbar() {
        var headerEl = document.querySelector('.header');

        function findScrollParent(el) {
            var cur = el.parentElement;
            while (cur && cur !== document.body) {
                var ov = window.getComputedStyle(cur).overflowY;
                if (ov === 'auto' || ov === 'scroll') return cur;
                cur = cur.parentElement;
            }
            return window;
        }

        document.querySelectorAll('.table-responsive').forEach(function (tableWrap) {
            var table = tableWrap.querySelector('table');
            if (!table) return;

            // Riel estático: siempre arriba de la tabla, en flujo normal
            var staticRail  = document.createElement('div');
            staticRail.className = 'mb-top-scroll-static';
            var staticInner = document.createElement('div');
            staticInner.className = 'mb-top-scroll-inner';
            staticRail.appendChild(staticInner);
            tableWrap.parentNode.insertBefore(staticRail, tableWrap);

            // Riel fijo: aparece pegado al header cuando el estático sale de pantalla
            var fixedRail  = document.createElement('div');
            fixedRail.className = 'mb-top-scroll-fixed';
            var fixedInner = document.createElement('div');
            fixedInner.className = 'mb-top-scroll-inner';
            fixedRail.appendChild(fixedInner);
            document.body.appendChild(fixedRail);

            var scrollParent = findScrollParent(tableWrap);

            function update() {
                var headerH    = headerEl ? headerEl.offsetHeight : 60;
                var tableW     = table.offsetWidth;
                var wrapRect   = tableWrap.getBoundingClientRect();
                var staticRect = staticRail.getBoundingClientRect();

                staticInner.style.width = tableW + 'px';
                fixedInner.style.width  = tableW + 'px';

                fixedRail.style.top   = headerH + 'px';
                fixedRail.style.left  = wrapRect.left  + 'px';
                fixedRail.style.width = wrapRect.width + 'px';

                if (!fixedRail._busy)  fixedRail.scrollLeft  = tableWrap.scrollLeft;
                if (!staticRail._busy) staticRail.scrollLeft = tableWrap.scrollLeft;

                // El riel fijo aparece cuando el estático sale por arriba y la tabla aún es visible
                var show = staticRect.bottom <= headerH && wrapRect.bottom > headerH + 20;
                fixedRail.style.display = show ? 'block' : 'none';
            }

            var busy = false;

            staticRail.addEventListener('scroll', function () {
                if (busy) return; busy = true;
                staticRail._busy = true;
                tableWrap.scrollLeft = staticRail.scrollLeft;
                fixedRail.scrollLeft = staticRail.scrollLeft;
                staticRail._busy = false;
                busy = false;
            });

            fixedRail.addEventListener('scroll', function () {
                if (busy) return; busy = true;
                fixedRail._busy = true;
                tableWrap.scrollLeft  = fixedRail.scrollLeft;
                staticRail.scrollLeft = fixedRail.scrollLeft;
                fixedRail._busy = false;
                busy = false;
            });

            tableWrap.addEventListener('scroll', function () {
                if (busy) return; busy = true;
                staticRail.scrollLeft = tableWrap.scrollLeft;
                fixedRail.scrollLeft  = tableWrap.scrollLeft;
                busy = false;
            });

            var scrollEl = scrollParent === window ? window : scrollParent;
            scrollEl.addEventListener('scroll', update, { passive: true });
            window.addEventListener('resize', update);

            setTimeout(update, 100);
        });
    }

    /* ----------------------------------------------------------------
       8) STICKY THEAD — fondo sólido + sombra al hacer scroll vertical
       Solo aplica a .table-responsive con overflow-y auto/scroll (tablas
       acotadas en altura, como las del dashboard). Lee el color real del
       card para que funcione en tema oscuro y claro.
    ---------------------------------------------------------------- */
    function initStickyHeaderShadow() {
        document.querySelectorAll('.table-responsive').forEach(function (wrap) {
            var thead = wrap.querySelector('thead.mb-sticky-head');
            if (!thead) return;

            wrap.addEventListener('scroll', function () {
                thead.classList.toggle('mb-thead-elevated', wrap.scrollTop > 2);
            });
        });
    }

    /* ----------------------------------------------------------------
       Arranque
    ---------------------------------------------------------------- */
    document.addEventListener('DOMContentLoaded', function () {
        initToasts();
        initConfirmations();
        initButtonLoaders();
        initTooltips();
        initDataTables();
        initImagePreview();
        initTopScrollbar();
        initStickyHeaderShadow();
    });
})();
