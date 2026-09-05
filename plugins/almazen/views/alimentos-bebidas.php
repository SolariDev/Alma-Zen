<div class="az-subcategorias">
    <?php
    $subcategorias = [
        ['slug' => 'frutas-verduras', 'nombre' => 'Frutas y Verduras'],
        ['slug' => 'lacteos', 'nombre' => 'Lácteos'],
        ['slug' => 'carnes', 'nombre' => 'Carnes'],
        ['slug' => 'fiambres', 'nombre' => 'Fiambres'],
        ['slug' => 'rotiseria', 'nombre' => 'Rotisería'],
        ['slug' => 'arroz', 'nombre' => 'Arroz'],
        ['slug' => 'fideos', 'nombre' => 'Fideos'],
        ['slug' => 'galletas-dulces', 'nombre' => 'Galletas Dulces'],
        ['slug' => 'galletas-saladas', 'nombre' => 'Galletas Saladas'],
        ['slug' => 'bebidas-sin-alcohol', 'nombre' => 'Bebidas sin Alcohol'],
        ['slug' => 'bebidas-con-alcohol', 'nombre' => 'Bebidas con Alcohol'],
    ];

    foreach ($subcategorias as $sub) {
        echo '<a href="' . esc_url(home_url('/lista-productos?subcat=' . $sub['slug'])) . '" class="az-card">';
        echo '<h3>' . esc_html($sub['nombre']) . '</h3>';
        echo '</a>';
    }
    ?>
</div>

<div id="az-contenedor-productos"></div>