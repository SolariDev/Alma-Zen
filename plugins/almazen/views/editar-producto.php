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

$producto = null;
$resultados = [];
$mensaje = '';
$accion_post = false;

// Guardar cambios
if (isset($_POST['guardar'])) {
    $accion_post = true;
    $id = absint($_POST['id'] ?? 0);

    if (!wp_verify_nonce($_POST['az_nonce'] ?? '', 'az_editar_producto')) {
        $mensaje = '⚠️ La página quedó abierta demasiado tiempo. Volvé a intentar.';
        $producto = $productosModel->obtener($id);
    } else {
    $datos = [
        'nombre'   => sanitize_text_field($_POST['nombre']),
        'precio'   => intval(str_replace('$ ', '', $_POST['precio'])),
        'cantidad' => floatval($_POST['cantidad']),
        'unidad'   => sanitize_text_field($_POST['unidad']),
        'empaque'  => sanitize_text_field($_POST['empaque']),
        'marca'    => sanitize_text_field($_POST['marca']),
    ];

    if ($es_admin) {
        // El admin edita directamente el producto.
        $resultado = $productosModel->actualizar(
            $id,
            $datos,
            (int) $usuario['id']
        );

        $mensaje = $resultado
            ? '✅ Producto actualizado correctamente.'
            : '❌ No se pudo actualizar el producto.';

    } else {
        // El usuario común solo genera una solicitud pendiente.
        $res = $productosModel->solicitarCambio(
            $id,
            $datos,
            (int) $usuario['id']
        );

        $mensajes = [
            'ok'           => '📨 Tu cambio quedó pendiente de aprobación del administrador.',
            'sin_cambios'  => 'No hay cambios para enviar.',
            'ya_pendiente' => 'Este producto ya tiene un cambio pendiente de aprobación.',
            'error'        => '❌ No se pudo enviar el cambio.',
        ];

        $mensaje = $mensajes[$res] ?? $mensajes['error'];
    }

    // Volver a cargar el producto usando el modelo.
    $producto = $productosModel->obtener($id);

    }
} elseif (isset($_POST['eliminar']) && $es_admin) {

        $accion_post = true;
        $id = absint($_POST['id'] ?? 0);

        if (!wp_verify_nonce($_POST['az_nonce'] ?? '', 'az_editar_producto')) {

            $mensaje = '⚠️ Inactividad prolongada. Volvé a intentar.';
            $producto = $productosModel->obtener($id);

        } else {

            $resultado = $productosModel->eliminar(
                $id,
                (int) $usuario['id']
            );

            if ($resultado) {
                $mensaje = '🗑️ Producto eliminado correctamente.';
                $producto = null;
            } else {
                $mensaje = '❌ No se pudo eliminar el producto.';
                $producto = $productosModel->obtener($id);
            }
        }
    }

    // Solo miramos la URL (buscador o selección puntual)
    // si esto NO vino de guardar/eliminar.
    if (!$accion_post) {

        if (isset($_GET['id'])) {

            $id = absint($_GET['id']);
            $producto = $productosModel->obtener($id);

        } elseif (isset($_GET['q'])) {

            $termino = sanitize_text_field(wp_unslash($_GET['q']));
            $resultados = $productosModel->buscarPorNombre($termino);

            if (count($resultados) === 1) {
                $producto = $resultados[0];
                $resultados = [];
            }
        }
    }
?>

<div class="az-page az-auth">
    <div class="az-container">

        <div class="az-header">
            <div>
                <h2 class="az-title">Editar producto</h2>
                <p class="az-subtitle">Buscá el producto que querés modificar</p>
            </div>

            <a href="<?php echo esc_url($url_panel); ?>" class="az-back" aria-label="Volver al panel">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M19 12H5"></path>
                    <path d="m12 19-7-7 7-7"></path>
                </svg>
            </a>
        </div>

        <?php if ($mensaje) : ?>
            <p class="az-mensaje"><?php echo esc_html($mensaje); ?></p>
        <?php endif; ?>

        <form method="get" action="<?php echo esc_url(home_url('/editar-producto')); ?>" class="az-form">
            <div class="az-search">
                <input type="search" name="q" id="az-busqueda" class="az-input" placeholder="Buscar producto..."
                    value="<?php echo isset($_GET['q']) ? esc_attr($_GET['q']) : ''; ?>" autocomplete="off" required>
                <button type="submit" class="az-btn az-btn-primary az-btn-icon" aria-label="Buscar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="7"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
            </div>
            <div id="az-resultados-live"
                 class="az-resultados-live"
                 style="display:none;">
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
                <?php wp_nonce_field('az_editar_producto', 'az_nonce'); ?>
                <input type="hidden" name="id" value="<?php echo esc_attr($producto['id']); ?>">

                <?php if (!$es_admin) : ?>
                    <p class="az-mensaje">
                        Los cambios que envíes quedarán pendientes hasta que los apruebe el administrador.
                    </p>
                <?php endif; ?>

                <div class="az-field">
                    <label for="nombre" class="az-label">Nombre</label>
                    <input type="text" id="nombre" name="nombre" class="az-input" value="<?php echo esc_attr($producto['nombre']); ?>" autocomplete="off" required>
                </div>

                <div class="az-field">
                    <label for="marca" class="az-label">Marca <small>(opcional)</small></label>
                    <input type="text" id="marca" name="marca" class="az-input" value="<?php echo esc_attr($producto['marca'] ?? ''); ?>" autocomplete="off">
                </div>

                <div class="az-field">
                    <label for="empaque" class="az-label">Presentación / Empaque</label>
                    <select id="empaque" name="empaque" class="az-input">
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
                </div>

                <div class="az-row">
                    <div class="az-field">
                        <label for="cantidad" class="az-label">Cantidad</label>
                        <input type="number" id="cantidad" name="cantidad" step="0.01" inputmode="decimal" class="az-input" value="<?php echo esc_attr($producto['cantidad']); ?>" required>
                    </div>

                    <div class="az-field">
                        <label for="unidad" class="az-label">Unidad</label>
                        <select id="unidad" name="unidad" class="az-input" required>
                            <option value="lt" <?php selected($producto['unidad'], 'lt'); ?>>Lt.</option>
                            <option value="ml" <?php selected($producto['unidad'], 'ml'); ?>>Ml.</option>
                            <option value="cc" <?php selected($producto['unidad'], 'cc'); ?>>Cc.</option>
                            <option value="kg" <?php selected($producto['unidad'], 'kg'); ?>>Kg.</option>
                            <option value="gr" <?php selected($producto['unidad'], 'gr'); ?>>Gr.</option>
                            <option value="unidad" <?php selected($producto['unidad'], 'unidad'); ?>>Unidad</option>
                        </select>
                    </div>
                </div>

                <div class="az-field">
                    <label for="precio" class="az-label">Precio</label>
                    <div class="az-input-group">
                        <span class="az-input-prefix">$</span>
                        <input type="text" id="precio" name="precio" inputmode="decimal" class="az-input" value="<?php echo esc_attr($producto['precio']); ?>" required>
                    </div>
                </div>

                <div class="az-form-actions">
                    <div class="az-botones">
                        <button type="submit" name="guardar" class="az-btn az-btn-primary">
                            <?php echo $es_admin ? 'Guardar cambios' : 'Enviar para aprobación'; ?>
                        </button>

                        <?php if ($es_admin) : ?>
                            <button type="submit" name="eliminar" class="az-btn az-btn-danger"
                                    onclick="return confirm('¿Seguro que quieres eliminar este producto?');">
                                Eliminar
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        <?php endif; ?>

    </div>
</div>