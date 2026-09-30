<?php
if (!defined('ABSPATH')) {
    exit;
}

$auth = new Autenticacion();
$usuario = $auth->usuarioActual();

if (!$usuario) {
    wp_redirect(home_url('/inicio'));
    exit;
}

$es_admin  = ($usuario['rol'] ?? '') === 'admin';
$url_panel = home_url($es_admin ? '/panel-admin' : '/panel-usuario');

$productosModel = new Productos();

$mensaje = '';

// Procesar formulario
if ( isset($_POST['guardar_producto']) ) {

    if (!wp_verify_nonce($_POST['az_nonce'] ?? '', 'az_registrar_producto')) {
        $mensaje = '⚠️ Inactividad prolongada. Probá de nuevo.';
    } else {
        $datos = [
            'nombre'   => sanitize_text_field($_POST['nombre'] ?? ''),
            'marca'    => sanitize_text_field($_POST['marca'] ?? ''),
            'precio'   => intval($_POST['precio'] ?? 0),
            'cantidad' => floatval($_POST['cantidad'] ?? 0),
            'unidad'   => sanitize_text_field($_POST['unidad'] ?? ''),
            'empaque'  => sanitize_text_field($_POST['empaque'] ?? ''),
        ];

        $resultado = $productosModel->crear(
            $datos,
            (int) $usuario['id']
        );

        $mensaje = $resultado
            ? '✅ Producto guardado correctamente.'
            : '❌ No se pudo guardar el producto.';
    }
}
?>

<div class="az-page az-auth">
    <div class="az-container">

        <h2 class="az-title az-text-center az-mb-lg">Registrar producto</h2>

        <?php if ($mensaje) : ?>
            <p class="az-mensaje"><?php echo esc_html($mensaje); ?></p>
        <?php endif; ?>

        <form method="post" class="az-form">
            <?php wp_nonce_field('az_registrar_producto', 'az_nonce'); ?>
            
            <label for="nombre" class="az-label">Nombre</label>
            <input type="text" id="nombre" name="nombre" class="az-input az-mb-md" required>

            <label for="marca" class="az-label">Marca</label>
            <input type="text" id="marca" name="marca" class="az-input az-mb-md">

            <label for="empaque" class="az-label">Presentación / Empaque</label>
            <select id="empaque" name="empaque" class="az-input az-mb-lg">
                <option value="">Sin empaque</option>
                <option value="pack">Pack</option>
                <option value="pack4">Pack de 4</option>
                <option value="pack6">Pack de 6</option>
                <option value="pack12">Pack de 12</option>
                <option value="bolsa">Bolsa</option>
                <option value="caja">Caja</option>
                <option value="lata">Lata</option>
                <option value="petaca">Petaca</option>
                <option value="tarrina">Tarrina</option>
            </select>
            
            <label for="cantidad" class="az-label">Cantidad</label>
            <input type="number" id="cantidad" name="cantidad" step="0.01" class="az-input az-mb-md" required>

            <label for="unidad" class="az-label">Unidad de medida</label>
            <select id="unidad" name="unidad" class="az-input az-mb-md" required>
                <option value="lt">Lt.</option>
                <option value="ml">Ml.</option>
                <option value="cc">Cc.</option>
                <option value="kg">Kg.</option>
                <option value="gr">Gr.</option>
                <option value="unidad">Unidad</option>
            </select>

            <label for="precio" class="az-label">Precio</label>
            <input type="number" id="precio" name="precio" step="1" min="0" class="az-input az-mb-md" required>

            <button type="submit" name="guardar_producto" class="az-btn az-btn-primary">Guardar</button>
        </form>

        <div class="az-botones">
            <a href="<?php echo esc_url($url_panel); ?>" class="az-btn az-btn-secondary">Volver al panel</a>
        </div>

    </div>
</div>