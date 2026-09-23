<?php

/**
 * Plugin Name: Alma-Zen
 * Description: Sistema de inventario con roles de administrador y usuario.
 * Version: 0.1
 * Author: Gabriel Solari
 */

// Bloquear acceso directo
if (! defined('ABSPATH')) {
    exit;
}

// Definir constantes
define('ALMAZEN_PATH', plugin_dir_path(__FILE__));
define('ALMAZEN_URL', plugin_dir_url(__FILE__));

// Autocarga de clases
spl_autoload_register(function ($class) {
    $file = ALMAZEN_PATH . 'includes/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Cargar handlers AJAX
require_once ALMAZEN_PATH . 'includes/ajax.php';

// Hooks de activación/desactivación crea las tabalas necesarias en la base de datos.
register_activation_hook(__FILE__, function () {
    $db = new BaseDatos();
    $db->crearTablas();
});

// Cargar estilos y scripts
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('az-estilos', ALMAZEN_URL . 'assets/estilos.css');
    $js_path = ALMAZEN_PATH . 'assets/interaccion.js';
    wp_enqueue_script('az-interaccion', ALMAZEN_URL . 'assets/interaccion.js', array('jquery'), file_exists($js_path) ? filemtime($js_path) : null, true);

    wp_localize_script('az-interaccion', 'az_ajax', [
        'url'       => admin_url('admin-ajax.php'),
        'nonce'     => wp_create_nonce('az_buscar_productos_nonce'),
    ]);
});

// Shortcodes para vistas

add_shortcode('az_inicio', function () {
    ob_start();
    include ALMAZEN_PATH . 'views/inicio.php';
    return ob_get_clean();
});

add_shortcode('az_registrar_usuario', function () {
    ob_start();
    include ALMAZEN_PATH . 'views/registrar-usuario.php';
    return ob_get_clean();
});

add_shortcode('az_registrar_admin', function () {
    ob_start();
    include ALMAZEN_PATH . 'views/registrar-admin.php';
    return ob_get_clean();
});

add_shortcode('az_login', function () {
    ob_start();
    include ALMAZEN_PATH . 'views/login.php';
    return ob_get_clean();
});

add_shortcode('az_recuperar_password', function () {
    ob_start();
    include ALMAZEN_PATH . 'views/recuperar-password.php';
    return ob_get_clean();
});

add_shortcode('az_panel_usuario', function () {
    ob_start();
    include ALMAZEN_PATH . 'views/panel-usuario.php';
    return ob_get_clean();
});

add_shortcode('az_panel_admin', function () {
    ob_start();
    include ALMAZEN_PATH . 'views/panel-admin.php';
    return ob_get_clean();
});

add_shortcode('az_registrar_producto', function () {
    ob_start();
    include ALMAZEN_PATH . 'views/registrar-producto.php';
    return ob_get_clean();
});

add_shortcode('az_editar_producto', function () {
    ob_start();
    include ALMAZEN_PATH . 'views/editar-producto.php';
    return ob_get_clean();
});