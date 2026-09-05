<div class="az-subcategorias">
    <?php
    $subcategorias = [
        ['slug' => 'productos-limpieza', 'nombre' => 'Productos de limpieza'],
        ['slug' => 'insecticidas', 'nombre' => 'Insecticidas'],
        ['slug' => 'desodorantes', 'nombre' => 'Desodorantes'],
        ['slug' => 'higiene-personal', 'nombre' => 'Higiene personal'],
    ];

    foreach ($subcategorias as $sub) {
        echo '<a href="' . esc_url(home_url('/lista-productos?subcat=' . $sub['slug'])) . '" class="az-card">';
        echo '<h3>' . esc_html($sub['nombre']) . '</h3>';
        echo '</a>';
    }
    ?>
</div>

<div id="az-contenedor-productos"></div>