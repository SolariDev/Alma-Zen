<?php
// Seguridad: evitar acceso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handler: buscar productos por nombre (coincidencia parcial, insensible a mayúsculas)
 * Usado por el buscador en vivo del panel de usuario y admin
 */
add_action('wp_ajax_az_buscar_productos', 'az_buscar_productos');
add_action('wp_ajax_nopriv_az_buscar_productos', 'az_buscar_productos'); // los usuarios no pasan por WP, así que este hook sigue haciendo falta

function az_buscar_productos() {
    check_ajax_referer('az_buscar_productos_nonce', 'nonce');

    // Como nadie pasa por WP, la sesión válida es la de tu propio sistema de auth
    $auth = new Autenticacion();
    if (!$auth->usuarioActual()) {
        wp_send_json_error('No autorizado', 401);
    }

    $termino = isset($_POST['termino']) ? sanitize_text_field(wp_unslash($_POST['termino'])) : '';

    if (mb_strlen(trim($termino)) < 2) {
        wp_send_json_success([]);
    }

    $productosModel = new Productos();
    wp_send_json_success($productosModel->buscarPorNombre($termino));
}