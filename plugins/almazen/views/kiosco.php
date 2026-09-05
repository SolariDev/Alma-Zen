<div class="az-subcategorias">
    <?php
    $subcategorias = [
        ['slug' => 'lenia', 'nombre' => 'Leña'],
        ['slug' => 'tabaco-cigarros', 'nombre' => 'Tabaco y Cigarros'],
        ['slug' => 'hojillas', 'nombre' => 'Hojillas'],
        ['slug' => 'encendedores', 'nombre' => 'Encendedores'],
        ['slug' => 'accesorios', 'nombre' => 'Accesorios'],
    ];

    foreach ($subcategorias as $sub) {
        echo '<a href="' . esc_url(home_url('/lista-productos?subcat=' . $sub['slug'])) . '" class="az-card">';
        echo '<h3>' . esc_html($sub['nombre']) . '</h3>';
        echo '</a>';
    }
    ?>
</div>

<div id="az-contenedor-productos"></div>