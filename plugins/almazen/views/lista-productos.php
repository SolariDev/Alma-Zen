<?php
$productosModel = new Productos();
$subcat = isset($_GET['subcat']) ? sanitize_text_field($_GET['subcat']) : '';

if (empty($subcat)) {
    echo '<p class="az-mensaje">No se especificó subcategoría.</p>';
    return;
}

$productos = $productosModel->obtenerPorSubcategoria($subcat);

if ($productos) {
    echo '<div class="az-lista-productos">';
    foreach ($productos as $producto) {
        echo '<div class="az-item">';
        echo '<h3>' . esc_html($producto['nombre']) . '</h3>';
        echo '<p>Precio: $' . esc_html($producto['precio']) . '</p>';
        echo '<p class="az-fecha">Última actualización: ' . esc_html($producto['fecha_actualizacion']) . '</p>';
        echo '</div>';
    }
    echo '</div>';
} else {
    echo '<p class="az-mensaje">No hay productos en esta subcategoría.</p>';
}
?>
