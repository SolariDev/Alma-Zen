<?php
// Seguridad: evitar acceso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handler: obtener productos por subcategoría
 */
add_action('wp_ajax_az_obtener_productos', 'az_obtener_productos');
add_action('wp_ajax_nopriv_az_obtener_productos', 'az_obtener_productos');

function az_obtener_productos() {
    global $wpdb;
    $subcat = isset($_POST['subcat']) ? sanitize_text_field($_POST['subcat']) : '';

    if (empty($subcat)) {
        echo '<p class="az-mensaje">No se especificó subcategoría.</p>';
        wp_die();
    }

    $productos = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT nombre, precio, fecha_actualizacion 
             FROM {$wpdb->prefix}az_productos 
             WHERE subcategoria = %s 
             ORDER BY nombre ASC",
            $subcat
        )
    );

    if ($productos) {
        foreach ($productos as $p) {
            echo '<div class="az-item">';
            echo '<h3>' . esc_html($p->nombre) . '</h3>';
            echo '<p>Precio: $' . esc_html($p->precio) . '</p>';
            echo '<p class="az-fecha">Última actualización: ' . esc_html($p->fecha_actualizacion) . '</p>';
            echo '</div>';
        }
    } else {
        echo '<p class="az-mensaje">No hay productos en esta subcategoría.</p>';
    }

    wp_die();
}

/**
 * Handler: buscar productos por nombre
 */
add_action('wp_ajax_az_buscar_productos', 'az_buscar_productos');
add_action('wp_ajax_nopriv_az_buscar_productos', 'az_buscar_productos');

function az_buscar_productos() {
    global $wpdb;
    $termino = isset($_POST['termino']) ? sanitize_text_field($_POST['termino']) : '';

    if (empty($termino)) {
        echo '<p class="az-mensaje">Ingrese un término de búsqueda.</p>';
        wp_die();
    }

    $productos = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT nombre, precio, fecha_actualizacion 
             FROM {$wpdb->prefix}az_productos 
             WHERE nombre LIKE %s 
             ORDER BY nombre ASC",
            '%' . $wpdb->esc_like($termino) . '%'
        )
    );

    if ($productos) {
        foreach ($productos as $p) {
            echo '<div class="az-item">';
            echo '<h3>' . esc_html($p->nombre) . '</h3>';
            echo '<p>Precio: $' . esc_html($p->precio) . '</p>';
            echo '<p class="az-fecha">Última actualización: ' . esc_html($p->fecha_actualizacion) . '</p>';
            echo '</div>';
        }
    } else {
        echo '<p class="az-mensaje">No se encontraron productos.</p>';
    }

    wp_die();
}