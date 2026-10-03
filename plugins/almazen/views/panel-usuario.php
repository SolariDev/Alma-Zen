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

// Cerrar sesión (solo si el nonce del formulario es válido)
if (isset($_POST['logout'])) {
    if (isset($_POST['az_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['az_nonce'])), 'az_logout')) {
        $auth->logout();
        wp_redirect(home_url('/inicio'));
        exit;
    }
}

$productosModel = new Productos();
$producto = null;
$resultados = [];

// Datos de la búsqueda (se sanitizan una sola vez, acá arriba)
$buscando = isset($_GET['q']) || isset($_GET['id']);
$termino  = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';

/* Buscar producto
 * Si viene ?id=xxx: mostramos directamente ese producto.
 * Si viene ?q=xxx: buscamos todas las coincidencias.
 * Si hay una sola coincidencia: la mostramos directamente.
 * Si hay varias: mostramos las opciones para que el usuario elija.
 */
if (isset($_GET['id'])) {
    $id = absint($_GET['id']);
    $producto = $productosModel->obtener($id);

} elseif (isset($_GET['q'])) {

    if ($termino !== '') {
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

        <!-- Encabezado: flecha (solo al buscar), nombre y rol -->
        <div class="az-header az-header-centro">
            <span class="az-header-espacio"></span>

            <div class="az-text-center">
                <h2 class="az-title"><?php echo esc_html($usuario['nombre']); ?></h2>
                <p class="az-subtitle">Usuario</p>
            </div>

            <?php if ($buscando) : ?>
                <a href="<?php echo esc_url(home_url('/panel-usuario')); ?>" class="az-back" aria-label="Volver al panel">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 12H5"></path>
                        <path d="m12 19-7-7 7-7"></path>
                    </svg>
                </a>
            <?php else : ?>
                <span class="az-header-espacio"></span>
            <?php endif; ?>
        </div>

        <!-- Buscador de productos -->
        <form method="get" action="<?php echo esc_url(home_url('/panel-usuario')); ?>" class="az-form">
            <div class="az-search">
                <input type="search" name="q" id="az-busqueda" class="az-input"
                    placeholder="Buscar producto..." value="<?php echo esc_attr($termino); ?>" autocomplete="off" required>
                <button type="submit" class="az-btn az-btn-primary az-btn-icon" aria-label="Buscar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="7"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
            </div>
            <div id="az-resultados-live" class="az-resultados-live" style="display:none;"></div>
        </form>

        <!-- Resultados múltiples -->
        <?php if (!empty($resultados)) : ?>

            <p class="az-subtitle az-text-center az-mb-md">
                Se encontraron varios productos. Elegí el que buscás:
            </p>

            <div class="az-botones az-mb-lg">
                <?php foreach ($resultados as $r) : ?>
                    <a href="<?php echo esc_url(add_query_arg('id', $r['id'], home_url('/panel-usuario'))); ?>" class="az-btn az-btn-secondary">
                        <?php echo esc_html($r['nombre'] . ' — ' . ($r['marca'] ?? '') . ' — $' . $r['precio'] . ' — ' . $r['cantidad'] . ' ' . $r['unidad']); ?>
                    </a>
                <?php endforeach; ?>
            </div>

        <!-- Producto seleccionado -->
        <?php elseif ($producto) : ?>

            <div class="az-form">
                <h3 class="az-ficha-titulo"><?php echo esc_html($producto['nombre']); ?></h3>

                <?php if (!empty($producto['marca'])) : ?>
                    <div class="az-ficha-fila">
                        <span class="az-ficha-etiqueta">Marca</span>
                        <span class="az-ficha-valor"><?php echo esc_html($producto['marca']); ?></span>
                    </div>
                <?php endif; ?>

                <div class="az-ficha-fila">
                    <span class="az-ficha-etiqueta">Precio</span>
                    <span class="az-ficha-valor">$ <?php echo esc_html($producto['precio']); ?></span>
                </div>

                <div class="az-ficha-fila">
                    <span class="az-ficha-etiqueta">Cantidad</span>
                    <span class="az-ficha-valor"><?php echo esc_html($producto['cantidad']); ?></span>
                </div>

                <div class="az-ficha-fila">
                    <span class="az-ficha-etiqueta">Unidad</span>
                    <span class="az-ficha-valor"><?php echo esc_html($producto['unidad']); ?></span>
                </div>

                <?php if (!empty($producto['empaque'])) : ?>
                    <div class="az-ficha-fila">
                        <span class="az-ficha-etiqueta">Empaque</span>
                        <span class="az-ficha-valor"><?php echo esc_html($producto['empaque']); ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($producto['categoria'])) : ?>
                    <div class="az-ficha-fila">
                        <span class="az-ficha-etiqueta">Categoría</span>
                        <span class="az-ficha-valor"><?php echo esc_html($producto['categoria']); ?></span>
                    </div>
                <?php endif; ?>

                <div class="az-ficha-fila">
                    <span class="az-ficha-etiqueta">Última actualización</span>
                    <span class="az-ficha-valor"><?php echo esc_html(date('d/m/Y H:i', strtotime($producto['fecha_actualizacion']))); ?></span>
                </div>
            </div>

        <!-- Sin resultados -->
        <?php elseif ($buscando) : ?>
            <p class="az-mensaje">No se encontró ningún producto.</p>
        <?php endif; ?>

        <?php if (!$buscando) : ?>

            <!-- Acciones de productos -->
            <p class="az-seccion">Productos</p>
            <div class="az-botones az-mb-lg">
                <a href="<?php echo esc_url(home_url('/registrar-producto')); ?>"
                   class="az-btn az-btn-secondary">Registrar</a>
                <a href="<?php echo esc_url(home_url('/editar-producto')); ?>"
                   class="az-btn az-btn-secondary">Editar</a>
            </div>

        <?php endif; ?>

        <!-- Botón cerrar sesión -->
        <form method="post" class="az-text-center">
            <?php wp_nonce_field('az_logout', 'az_nonce'); ?>
            <button type="submit" name="logout" class="az-btn az-btn-secondary az-btn-sm">Cerrar sesión</button>
        </form>

    </div>
</div>         