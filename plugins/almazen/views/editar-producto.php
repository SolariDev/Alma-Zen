<?php
$auth = new Autenticacion();
$usuario = $auth->usuarioActual();

if (!$usuario) {
    wp_redirect(home_url('/inicio'));
    exit;
}

$productosModel = new Productos();
$producto = null;
$mensaje = '';

// Procesar guardado
if (isset($_POST['guardar'])) {
    $id        = intval($_POST['id']);
    $nombre    = sanitize_text_field($_POST['nombre']);
    $precio    = intval($_POST['precio']);
    $cantidad  = floatval($_POST['cantidad']);
    $unidad    = sanitize_text_field($_POST['unidad']);
    $empaque   = sanitize_text_field($_POST['empaque']);
    $marca     = sanitize_text_field($_POST['marca']);
    $proveedor = sanitize_text_field($_POST['proveedor']);
    $categoria = sanitize_text_field($_POST['categoria']);

    global $wpdb;
    $tabla = 'az_alm_productos';
    $wpdb->update(
        $tabla,
        [
            'nombre'              => $nombre,
            'precio'              => $precio,
            'cantidad'            => $cantidad,
            'unidad'              => $unidad,
            'empaque'             => $empaque,
            'marca'               => $marca,
            'proveedor'           => $proveedor,
            'categoria'           => $categoria,
            'fecha_actualizacion' => current_time('mysql')
        ],
        ['id' => $id],
        ['%s','%d','%s','%s','%s','%s','%s','%s','%s'],
        ['%d']
    );

    $mensaje = '✅ Producto actualizado correctamente.';
}

if (isset($_POST['eliminar'])) {
    $id = intval($_POST['id']);
    global $wpdb;
    $tabla = $wpdb->prefix . 'alm_productos';
    $wpdb->delete($tabla, ['id' => $id], ['%d']);
    $mensaje = '🗑️ Producto eliminado correctamente.';
    $producto = null; // ya no mostrar tarjeta
}

// Si se envió búsqueda
if (isset($_GET['q'])) {
    $termino = sanitize_text_field($_GET['q']);
    $resultados = $productosModel->buscarPorNombre($termino);
// el primero en la coincidencia se asigna a $producto
    if (!empty($resultados)) {
        $producto = $resultados[0];
    }
}
?>

<div class="az-panel az-admin">
    <h3 class="az-titulo-editar">Editar producto</h3>
    
    <!-- Mensaje de éxito -->
    <?php if ($mensaje): ?>
        <div class="az-mensaje-exito"><?php echo esc_html($mensaje); ?></div>
    <?php endif; ?>

    <!-- Buscador -->
    <form method="get" action="<?php echo esc_url(home_url('/editar-producto')); ?>" class="az-form-buscar">
        <input type="text" name="q" placeholder="Buscar producto..." required>
        <button type="submit" class="az-btn">
            <img src="<?php echo ALMAZEN_URL . 'assets/img/buscar.png'; ?>" alt="Buscar" class="az-icon-buscar">
        </button>
    </form>

    <div class="az-admin-tools">
        <a href="<?php echo esc_url(home_url('/panel-admin')); ?>" class="az-btn admin">Volver al panel</a>
    </div>

    <a href="<?php echo esc_url(home_url('/panel-admin')); ?>" class="btn-volver-panel" title="Volver al panel"><span class="icon-arrow-left"></span></a>

    <?php if ($producto): ?>
    <form method="post" class="az-form">
        <input type="hidden" name="id" value="<?php echo esc_attr($producto['id']); ?>">

        <label>Nombre</label>
        <input type="text" name="nombre" value="<?php echo esc_attr($producto['nombre']); ?>" required>

        <label>Precio</label>
        <input type="number" name="precio" step="1" min="0" value="<?php echo esc_attr($producto['precio']); ?>" required>

        <label>Cantidad</label>
        <input type="number" name="cantidad" step="0.01" value="<?php echo esc_attr($producto['cantidad']); ?>" required>

        <label>Unidad</label>
        <select name="unidad" required>
            <option value="ml" <?php selected($producto['unidad'], 'ml'); ?>>Mililitros</option>
            <option value="lt" <?php selected($producto['unidad'], 'lt'); ?>>Litros</option>
            <option value="gr" <?php selected($producto['unidad'], 'gr'); ?>>Gramos</option>
            <option value="kg" <?php selected($producto['unidad'], 'kg'); ?>>Kilogramos</option>
            <option value="unidad" <?php selected($producto['unidad'], 'unidad'); ?>>Unidad</option>
        </select>

        <label>Empaque / Presentación</label>
        <select name="empaque">
            <option value="" <?php selected($producto['empaque'], ''); ?>>Sin empaque</option>
            <option value="pack6" <?php selected($producto['empaque'], 'pack6'); ?>>Pack de 6</option>
            <option value="pack12" <?php selected($producto['empaque'], 'pack12'); ?>>Pack de 12</option>
            <option value="bolsa" <?php selected($producto['empaque'], 'bolsa'); ?>>Bolsa</option>
            <option value="tarrina" <?php selected($producto['empaque'], 'tarrina'); ?>>Tarrina</option>
            <option value="caja" <?php selected($producto['empaque'], 'caja'); ?>>Caja</option>
        </select>

        <label>Marca</label>
        <input type="text" name="marca" value="<?php echo esc_attr($producto['marca'] ?? ''); ?>">

        <label>Proveedor</label>
        <input type="text" name="proveedor" value="<?php echo esc_attr($producto['proveedor'] ?? ''); ?>">

        <label>Categoría</label>
        <input type="text" name="categoria" value="<?php echo esc_attr($producto['categoria'] ?? ''); ?>">

        <div class="az-admin-tools">
            <button type="submit" name="guardar" class="az-btn">Guardar cambios</button>

            <button type="submit" name="eliminar" class="az-btn eliminar"
                   onclick="return confirm('¿Seguro que quieres eliminar este producto?');">
                Eliminar
            </button>
        </div>
    </form>
</div>
          
    <?php elseif (isset($_GET['q'])): ?>
        <p class="az-mensaje">No se encontró ningún producto con ese nombre.</p>
    <?php endif; ?>
</div>