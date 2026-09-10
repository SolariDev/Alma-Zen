/**
 * Interacción dinámica optimizada para Alma-Zen
 * - Búsqueda dinámica con debounce
 * - Filtros con AJAX
 * - Botón flotante "Volver al panel" al hacer scroll
 */

jQuery(document).ready(function ($) {

    // 🔹 Función genérica para peticiones AJAX
    function cargarVista(data) {
        $.ajax({
            url: az_ajax.url,
            type: 'POST',
            data: data,
            success: function (response) {
                $('#az-contenedor-productos').html(response);
            },
            error: function () {
                $('#az-contenedor-productos').html('<p class="az-mensaje">Error al cargar datos.</p>');
            }
        });
    }

    // 🔹 Búsqueda dinámica con debounce
    let debounceTimer;
    $('#az-busqueda').on('keyup', function () {
        clearTimeout(debounceTimer);
        const termino = $(this).val().trim();

        if (termino.length === 0) {
            $('#az-contenedor-productos').html('');
            return;
        }

        debounceTimer = setTimeout(function () {
            cargarVista({
                action: 'az_buscar_productos',
                termino: termino
            });
        }, 300); // espera 300ms antes de disparar la búsqueda
    });

    // 🔹 Botón flotante "Volver al panel"
    const $btnFlotante = $('.btn-volver-panel'); // usar jQuery para asegurar compatibilidad

    if ($btnFlotante.length) {
        $(window).on('scroll', function () {
            if ($(this).scrollTop() > 100) {
                $btnFlotante.css('display', 'flex'); // aparece flotante
            } else {
                $btnFlotante.css('display', 'none'); // se oculta
            }
        });
    }

});
