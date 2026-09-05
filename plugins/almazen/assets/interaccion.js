/**
 * Interacción dinámica optimizada para Alma-Zen
 * - Manejo de clic en tarjetas de categorías y subcategorías
 * - Búsqueda dinámica con debounce
 * - Filtros con AJAX
 */

jQuery(document).ready(function ($) {

    // Función genérica para peticiones AJAX
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

    // Al hacer clic en una tarjeta de categoría → cargar subcategorías
    $('.az-card').on('click', function () {
        const categoria = $(this).data('categoria');
        cargarVista({
            action: 'az_obtener_subcategorias',
            categoria: categoria
        });
    });

    // Al hacer clic en una tarjeta de subcategoría → cargar productos
    $(document).on('click', '.az-subcard', function () {
        const subcat = $(this).data('subcat');
        cargarVista({
            action: 'az_obtener_productos',
            subcat: subcat
        });
    });

    // Búsqueda dinámica con debounce
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
        }, 300);
    });

});
