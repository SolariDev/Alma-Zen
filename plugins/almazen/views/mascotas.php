<div class="az-subcategorias">
    <?php
    $subcategorias = [
        ['slug' => 'perros', 'nombre' => 'Alimento para perros'],
        ['slug' => 'gatos', 'nombre' => 'Alimento para gatos'],
        ['slug' => 'raciones', 'nombre' => 'Raciones'],
    ];

    foreach ($subcategorias as $sub) {
        echo '<a href="' . esc_url(home_url('/lista-productos?subcat=' . $sub['slug'])) . '" class="az-card">';
        echo '<h3>' . esc_html($sub['nombre']) . '</h3>';
        echo '</a>';
    }
    ?>
</div>

<div id="az-contenedor-productos"></div>