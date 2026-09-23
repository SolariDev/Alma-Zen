<?php
$auth = new Autenticacion();

$usuario = $auth->usuarioActual();

if (!$usuario) {
    wp_redirect(home_url('/inicio'));
    exit;
}

if (isset($_POST['logout'])) {
    $auth->logout();
    wp_redirect(home_url('/inicio'));
    exit;
}

$productosModel = new Productos();
$producto = null;
$resultados = [];

/*
 * Buscar producto
 *
 * Si viene ?id=xxx:
 * mostramos directamente ese producto.
 *
 * Si viene ?q=xxx:
 * buscamos todas las coincidencias.
 *
 * Si hay una sola coincidencia:
 * la mostramos directamente.
 *
 * Si hay varias:
 * mostramos las opciones para que el usuario elija.
 */

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    global $wpdb;

    $tabla = $wpdb->prefix . 'alm_productos';

    $producto = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d",
            $id
        ),
        ARRAY_A
    );

} elseif (isset($_GET['q'])) {
    $termino = sanitize_text_field($_GET['q']);

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

        <h2 class="az-title az-text-center az-mb-lg">Gestión de productos</h2>

        <!-- Acciones de administrador -->
        <div class="az-botones az-mb-lg">
            <a href="<?php echo esc_url(home_url('/registrar-producto')); ?>"
               class="az-btn az-btn-secondary">Registrar producto</a>
            <a href="<?php echo esc_url(home_url('/editar-producto')); ?>"
               class="az-btn az-btn-secondary">Editar producto</a>
        </div>

        <!-- Buscador de productos -->
        <form method="get" action="<?php echo esc_url(home_url('/panel-admin')); ?>" class="az-form">
            <div class="az-search">
                <input type="search" name="q" id="az-busqueda" class="az-input" placeholder="Buscar producto..."
                    value="<?php echo isset($_GET['q']) ? esc_attr($_GET['q']) : ''; ?>" required>
                <button type="submit" class="az-btn az-btn-primary az-btn-icon" aria-label="Buscar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="7"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
            </div>

            <div id="az-resultados-live" class="az-resultados-live" style="display:none;" ></div>

        </form>

        <!-- Resultados múltiples -->
        <?php if (!empty($resultados)) : ?>
            <p class="az-subtitle az-text-center az-mb-md">Se encontraron varios productos. Elegí el que buscás:</p>

            <div class="az-botones az-mb-lg">
                <?php foreach ($resultados as $r) : ?>
                    <a href="<?php echo esc_url(add_query_arg(
                        'id',$r['id'], home_url('/panel-admin'))); ?>"
                        class="az-btn az-btn-secondary">
                        <?php echo esc_html(
                            $r['nombre']
                            . ' — '
                            . ($r['marca'] ?? '')
                            . ' — $'
                            . $r['precio']
                            . ' — '
                            . $r['cantidad']
                            . ' '
                            . $r['unidad']
                        );
                        ?>
                    </a>
                <?php endforeach; ?>
            </div>

        <!-- Producto seleccionado -->
        <?php elseif ($producto) : ?>
            <div class="az-form">
                <h3 class="az-title az-mb-md"><?php echo esc_html($producto['nombre']); ?></h3>
                <?php if (!empty($producto['marca'])) : ?>
                    <p class="az-mb-sm"><strong>Marca:</strong>
                        <?php echo esc_html($producto['marca']); ?></p>
                <?php endif; ?>
                <p class="az-mb-sm"><strong>Precio:</strong>
                    $ <?php echo esc_html($producto['precio']); ?></p>

                <p class="az-mb-sm"><strong>Cantidad:</strong>
                    <?php echo esc_html($producto['cantidad']); ?></p>

                <p class="az-mb-sm"><strong>Unidad:</strong>
                    <?php echo esc_html($producto['unidad']); ?></p>
                    <?php if (!empty($producto['empaque'])) : ?>
                <p class="az-mb-sm"><strong>Empaque:</strong>
                        <?php echo esc_html($producto['empaque']); ?></p>
                <?php endif; ?>

                <?php if (!empty($producto['categoria'])) : ?>
                    <p class="az-mb-sm"><strong>Categoría:</strong>
                        <?php echo esc_html($producto['categoria']); ?></p>
                <?php endif; ?>

                <p><strong>Última actualización:</strong>
                    <?php echo esc_html(date('d/m/Y H:i',strtotime($producto['fecha_actualizacion'])
                        ));?></p>
            </div>

        <!-- Sin resultados -->
        <?php elseif (isset($_GET['q'])) : ?>
            <p class="az-mensaje">No se encontró ningún producto con ese nombre.</p>
        <?php endif; ?>

        <!-- Volver al panel -->
        <?php if (isset($_GET['q']) || isset($_GET['id'])) : ?>

            <a href="<?php echo esc_url(home_url('/panel-admin')); ?>"
                class="az-btn az-btn-secondary az-mb-md">Volver al panel</a>
        <?php endif; ?>

        <!-- Cerrar sesión -->
        <form method="post" class="az-text-center">
            <button type="submit" name="logout" class="az-btn az-btn-secondary az-btn-sm">
                Cerrar sesión
            </button>
        </form>
    </div>
</div>