<?php
$auth = new Autenticacion();
$usuario = $auth->usuarioActual();

if (!$usuario) {
    wp_redirect(home_url('/inicio'));
    exit;
}

global $wpdb;
$tabla = $wpdb->prefix . 'alm_productos';
$productosModel = new Productos();

$producto = null;
$resultados = [];
$mensaje = '';
$accion_post = false;

// Guardar cambios
if (isset($_POST['guardar'])) {
    $accion_post = true;
    $id        = intval($_POST['id']);
    $nombre    = sanitize_text_field($_POST['nombre']);
    $precio    = intval(str_replace('$ ', '', $_POST['precio']));
    $cantidad  = floatval($_POST['cantidad']);
    $unidad    = sanitize_text_field($_POST['unidad']);
    $empaque   = sanitize_text_field($_POST['empaque']);
    $marca     = sanitize_text_field($_POST['marca']);
   
    $wpdb->update(
        $tabla,
        [
            'nombre'              => $nombre,
            'precio'              => $precio,
            'cantidad'            => $cantidad,
            'unidad'              => $unidad,
            'empaque'             => $empaque,
            'marca'               => $marca,
            'fecha_actualizacion' => current_time('mysql')
        ],
        ['id' => $id],
        ['%s', '%d', '%s', '%s', '%s', '%s', '%s'],
        ['%d']
    );

    $mensaje  = '✅ Producto actualizado correctamente.';
    $producto = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla WHERE id = %d", $id), ARRAY_A);
}

// Eliminar
if (isset($_POST['eliminar'])) {
    $accion_post = true;
    $id = intval($_POST['id']);
    $wpdb->delete($tabla, ['id' => $id], ['%d']);
    $mensaje  = '🗑️ Producto eliminado correctamente.';
    $producto = null;
}

// Solo miramos la URL (buscador o selección puntual) si esto NO vino de guardar/eliminar
if (!$accion_post) {
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $producto = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla WHERE id = %d", $id), ARRAY_A);

    } elseif (isset($_GET['q'])) {
        $termino    = sanitize_text_field($_GET['q']);
        $resultados = $productosModel->buscarPorNombre($termino);

        if (count($resultados) === 1) {
            $producto   = $resultados[0];
            $resultados = [];
        }
    }
}
?>

<div class="az-page az-auth">
    <div class="az-container">

        <h2 class="az-title az-text-center az-mb-lg">Editar producto</h2>

        <?php if ($mensaje) : ?>
            <p class="az-mensaje"><?php echo esc_html($mensaje); ?></p>
        <?php endif; ?>

        <form method="get" action="<?php echo esc_url(home_url('/editar-producto')); ?>" class="az-form">
            <div class="az-search">
                <input type="search" name="q" class="az-input" placeholder="Buscar producto..."
                    value="<?php echo isset($_GET['q']) ? esc_attr($_GET['q']) : ''; ?>" required>
                <button type="submit" class="az-btn az-btn-primary az-btn-icon" aria-label="Buscar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="7"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
            </div>
        </form>

        <?php if (!empty($resultados)) : ?>
            <p class="az-subtitle az-text-center az-mb-md">Se encontraron varios productos, elegí el correcto:</p>
            <div class="az-botones az-mb-lg">
                <?php foreach ($resultados as $r) : ?>
                    <a href="<?php echo esc_url(add_query_arg('id', $r['id'], home_url('/editar-producto'))); ?>" class="az-btn az-btn-secondary">
                        <?php echo esc_html($r['nombre'] . ' — ' . $r['marca'] . ' — $' . $r['precio'] . ' — ' . $r['cantidad'] . ' ' . $r['unidad']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php elseif (isset($_GET['q']) && !$producto) : ?>
            <p class="az-mensaje">No se encontró ningún producto con ese nombre.</p>
        <?php endif; ?>

        <?php if ($producto) : ?>
            <form method="post" class="az-form">
                <input type="hidden" name="id" value="<?php echo esc_attr($producto['id']); ?>">

                <label for="nombre" class="az-label">Nombre</label>
                <input type="text" id="nombre" name="nombre" class="az-input az-mb-md" value="<?php echo esc_attr($producto['nombre']); ?>" required>

                <label for="marca" class="az-label">Marca</label>
                <input type="text" id="marca" name="marca" class="az-input az-mb-md" value="<?php echo esc_attr($producto['marca'] ?? ''); ?>">

                <label for="precio" class="az-label">Precio</label>
                <input type="text" id="precio" name="precio" class="az-input az-mb-md" value="<?php echo esc_attr($producto['precio']); ?>" required>

                <label for="cantidad" class="az-label">Cantidad</label>
                <input type="number" id="cantidad" name="cantidad" step="0.01" class="az-input az-mb-md" value="<?php echo esc_attr($producto['cantidad']); ?>" required>

                <label for="unidad" class="az-label">Unidad</label>
                <select id="unidad" name="unidad" class="az-input az-mb-md" required>
                    <option value="lt" <?php selected($producto['unidad'], 'lt'); ?>>Lt.</option>
                    <option value="ml" <?php selected($producto['unidad'], 'ml'); ?>>Ml.</option>
                    <option value="cc" <?php selected($producto['unidad'], 'cc'); ?>>Cc.</option>
                    <option value="kg" <?php selected($producto['unidad'], 'kg'); ?>>Kg.</option>                    
                    <option value="gr" <?php selected($producto['unidad'], 'gr'); ?>>Gr.</option>                    
                    <option value="unidad" <?php selected($producto['unidad'], 'unidad'); ?>>Unidad</option>
                </select>

                <label for="empaque" class="az-label">Empaque / Presentación</label>
                <select id="empaque" name="empaque" class="az-input az-mb-lg">
                    <option value="" <?php selected($producto['empaque'], ''); ?>>Sin empaque</option>
                    <option value="pack" <?php selected($producto['empaque'], 'pack'); ?>>Pack</option>
                    <option value="pack4" <?php selected($producto['empaque'], 'pack4'); ?>>Pack de 4</option>
                    <option value="pack6" <?php selected($producto['empaque'], 'pack6'); ?>>Pack de 6</option>
                    <option value="pack12" <?php selected($producto['empaque'], 'pack12'); ?>>Pack de 12</option>
                    <option value="bolsa" <?php selected($producto['empaque'], 'bolsa'); ?>>Bolsa</option>
                    <option value="caja" <?php selected($producto['empaque'], 'caja'); ?>>Caja</option>
                    <option value="lata" <?php selected($producto['empaque'], 'lata'); ?>>Lata</option>
                    <option value="petaca" <?php selected($producto['empaque'], 'petaca'); ?>>Petaca</option>
                    <option value="tarrina" <?php selected($producto['empaque'], 'tarrina'); ?>>Tarrina</option>
                    
                </select>

                <div class="az-botones">
                    <button type="submit" name="guardar" class="az-btn az-btn-primary">Guardar cambios</button>
                    <button type="submit" name="eliminar" class="az-btn az-btn-danger"
                            onclick="return confirm('¿Seguro que quieres eliminar este producto?');">
                        Eliminar
                    </button>
                </div>
            </form>
        <?php endif; ?>

        <div class="az-botones az-mb-lg">
            <a href="<?php echo esc_url(home_url('/panel-admin')); ?>" class="az-btn az-btn-secondary">Volver al panel</a>
        </div>

    </div>
</div>