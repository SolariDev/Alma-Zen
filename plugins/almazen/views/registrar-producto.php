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

        <div class="az-header">
            <div>
                <h2 class="az-title">Registrar producto</h2>
                <p class="az-subtitle">Completá los datos del producto</p>
            </div>

            <a href="<?php echo esc_url($url_panel); ?>" class="az-back" aria-label="Volver">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M19 12H5"></path>
                    <path d="m12 19-7-7 7-7"></path>
                </svg>
            </a>
        </div>

        <?php if ($mensaje) : ?>
            <p class="az-mensaje"><?php echo esc_html($mensaje); ?></p>
        <?php endif; ?>

        <form method="post" class="az-form">
            <?php wp_nonce_field('az_registrar_producto', 'az_nonce'); ?>

            <div class="az-field">
                <label for="nombre" class="az-label">Nombre</label>
                <input type="text" id="nombre" name="nombre" class="az-input" autocomplete="off" required>
            </div>

            <div class="az-field">
                <label for="marca" class="az-label">Marca <small>(opcional)</small></label>
                <input type="text" id="marca" name="marca" class="az-input" autocomplete="off">
            </div>

            <div class="az-field">
                <label for="empaque" class="az-label">Presentación / Empaque</label>
                <select id="empaque" name="empaque" class="az-input">
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
            </div>

            <div class="az-row">
                <div class="az-field">
                    <label for="cantidad" class="az-label">Cantidad</label>
                    <input type="number" id="cantidad" name="cantidad" step="0.01" inputmode="decimal" class="az-input" placeholder="0" required>
                </div>

                <div class="az-field">
                    <label for="unidad" class="az-label">Unidad</label>
                    <select id="unidad" name="unidad" class="az-input" required>
                        <option value="lt">Lt.</option>
                        <option value="ml">Ml.</option>
                        <option value="cc">Cc.</option>
                        <option value="kg">Kg.</option>
                        <option value="gr">Gr.</option>
                        <option value="unidad">Unidad</option>
                    </select>
                </div>
            </div>

            <div class="az-field">
                <label for="precio" class="az-label">Precio</label>
                <div class="az-input-group">
                    <span class="az-input-prefix">$</span>
                    <input type="number" id="precio" name="precio" step="1" min="0" inputmode="numeric" class="az-input" placeholder="0" required>
                </div>
            </div>

            <div class="az-form-actions">
                <button type="submit" name="guardar_producto" class="az-btn az-btn-primary">Guardar producto</button>
            </div>
        </form>

    </div>
</div>