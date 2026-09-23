/**
 * Interacción dinámica — Panel de usuario Alma-Zen
 * Búsqueda en vivo con debounce sobre az_buscar_productos (ajax.php)
 */

jQuery(document).ready(function ($) {

    const $input      = $('#az-busqueda');
    const $resultados = $('#az-resultados-live');

    if (!$input.length || !$resultados.length) {
        return; // esta vista no tiene buscador en vivo
    }

    function escapar(texto) {
        return $('<div>').text(texto ?? '').html();
    }

    function renderResultados(productos) {
    if (!productos || productos.length === 0) {
        $resultados.html('<p class="az-mensaje">No se encontraron productos.</p>').show();
        return;
    }

    const urlBase = window.location.origin + window.location.pathname;

    let html = '<div class="az-botones">';
    productos.forEach(function (p) {
        const marca = p.marca ? ' — ' + escapar(p.marca) : '';
        html += '<a href="' + urlBase + '?id=' + encodeURIComponent(p.id) + '" class="az-btn az-btn-secondary">' +
                    escapar(p.nombre) + marca + ' — $' + escapar(p.precio) +
                    ' — ' + escapar(p.cantidad) + ' ' + escapar(p.unidad) +
                '</a>';
    });
    html += '</div>';
    $resultados.html(html).show();
}

    let debounceTimer;
    $input.on('keyup', function () {
        clearTimeout(debounceTimer);
        const termino = $(this).val().trim();

        if (termino.length < 2) {
            $resultados.hide().empty();
            return;
        }

        debounceTimer = setTimeout(function () {
            $.ajax({
                url: az_ajax.url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'az_buscar_productos',
                    termino: termino,
                    nonce: az_ajax.nonce
                },
                success: function (response) {
                    if (response && response.success) {
                        renderResultados(response.data);
                    } else {
                        $resultados.html('<p class="az-mensaje">Error al buscar.</p>').show();
                    }
                },
                error: function () {
                    $resultados.html('<p class="az-mensaje">Error al buscar.</p>').show();
                }
            });
        }, 300);
    });

    // Cierra el listado al tocar/clickear fuera del buscador
    $(document).on('click', function (e) {
        if (!$(e.target).closest('.az-search, #az-resultados-live').length) {
            $resultados.hide();
        }
    });

});