<?php
// Cargar estilos del padre y del child Alma-Zen
add_action('wp_enqueue_scripts', function() {
    // Estilos del padre Hello Elementor
    wp_enqueue_style(
        'hello-elementor-style',
        get_template_directory_uri() . '/style.css'
    );

    // Estilos del child Alma-Zen
    wp_enqueue_style(
        'alm-child-style',
        get_stylesheet_directory_uri() . '/style.css',
        ['hello-elementor-style'], // se carga después del padre
        filemtime(get_stylesheet_directory() . '/style.css') // cache busting automático
    );
});