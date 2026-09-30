<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handler: buscar productos por nombre (coincidencia parcial, insensible a mayúsculas)
 * Usado por el buscador en vivo del panel de usuario y admin
 */
add_action('wp_ajax_az_buscar_productos', 'az_buscar_productos');

function az_buscar_productos() {
    check_ajax_referer('az_buscar_productos_nonce', 'nonce');

    $auth = new Autenticacion();
    if (!$auth->usuarioActual()) {
        wp_send_json_error('No autorizado', 401);
    }

    // Handler de solo lectura: se libera el bloqueo de la sesión
    $auth->liberarSesion();

    $termino = (isset($_POST['termino']) && is_string($_POST['termino']))
        ? sanitize_text_field(wp_unslash($_POST['termino']))
        : '';
    $termino = mb_substr(trim($termino), 0, 100);

    if (mb_strlen($termino) < 2) {
        wp_send_json_success([]);
    }

    $productosModel = new Productos();
    wp_send_json_success($productosModel->buscarPorNombre($termino));
}