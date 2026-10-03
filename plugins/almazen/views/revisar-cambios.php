<?php
if (!defined('ABSPATH')) {
    exit;
}

$auth = new Autenticacion();
$usuario = $auth->usuarioActual();

// Solo el admin puede ver y resolver cambios pendientes
if (!$usuario || ($usuario['rol'] ?? '') !== 'admin') {
    wp_redirect(home_url('/inicio'));
    exit;
}

$productosModel = new Productos();
$mensaje = '';

if (isset($_POST['aprobar']) || isset($_POST['rechazar'])) {
    if (!wp_verify_nonce($_POST['az_nonce'] ?? '', 'az_revisar_cambios')) {
        $mensaje = '⚠️ Inactividad prolongada. Probá de nuevo.';
    } else {
        $cambio_id = intval($_POST['cambio_id']);

        if (isset($_POST['aprobar'])) {
            $mensaje = $productosModel->aprobarCambio($cambio_id, (int) $usuario['id'])
                ? '✅ Cambio aprobado y aplicado al producto.'
                : '❌ No se pudo aprobar el cambio.';
        } else {
            $mensaje = $productosModel->rechazarCambio($cambio_id, (int) $usuario['id'])
                ? '🗑️ Cambio descartado.'
                : '❌ No se pudo descartar el cambio.';
        }
    }
}

$cambios = $productosModel->cambiosPendientes();

$campos = [
    'nombre'   => 'Nombre',
    'marca'    => 'Marca',
    'empaque'  => 'Empaque',
    'cantidad' => 'Cantidad',
    'unidad'   => 'Unidad',
    'precio'   => 'Precio',
];
?>

<div class="az-page az-auth">
    <div class="az-container">

        <div class="az-header">
            <div>
                <h2 class="az-title">Cambios pendientes</h2>
                <p class="az-subtitle">Revisá las solicitudes de los usuarios</p>
            </div>

            <a href="<?php echo esc_url(home_url('/panel-admin')); ?>" class="az-back" aria-label="Volver al panel">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M19 12H5"></path>
                    <path d="m12 19-7-7 7-7"></path>
                </svg>
            </a>
        </div>

        <?php if ($mensaje) : ?>
            <p class="az-mensaje"><?php echo esc_html($mensaje); ?></p>
        <?php endif; ?>

        <?php if (empty($cambios)) : ?>
            <p class="az-mensaje">No hay cambios pendientes.</p>
        <?php endif; ?>

        <?php foreach ($cambios as $c) :
            $antes = json_decode($c['datos_anteriores'], true) ?: [];
            $nuevo = json_decode($c['datos_nuevos'], true) ?: [];
        ?>
            <div class="az-form">
                <h3 class="az-ficha-titulo">
                    <?php echo esc_html($c['producto_nombre'] ?? ($antes['nombre'] ?? 'Producto eliminado')); ?>
                </h3>
                <p class="az-subtitle az-mb-sm">
                    Solicitado por <?php echo esc_html($c['solicitante'] ?? 'usuario eliminado'); ?>
                    — <?php echo esc_html(date('d/m/Y H:i', strtotime($c['fecha_solicitud']))); ?>
                </p>

                <?php foreach ($campos as $k => $etiqueta) :
                    if (($antes[$k] ?? '') == ($nuevo[$k] ?? '')) {
                        continue;
                    } ?>
                    <div class="az-cambio">
                        <span class="az-cambio-etiqueta"><?php echo esc_html($etiqueta); ?></span>
                        <span class="az-cambio-valores">
                            <span class="az-cambio-antes"><?php echo esc_html($antes[$k] ?? '—'); ?></span>
                            <span class="az-cambio-flecha" aria-hidden="true">→</span>
                            <span class="az-cambio-nuevo"><?php echo esc_html($nuevo[$k] ?? '—'); ?></span>
                        </span>
                    </div>
                <?php endforeach; ?>

                <form method="post" class="az-row az-form-actions">
                    <?php wp_nonce_field('az_revisar_cambios', 'az_nonce'); ?>
                    <input type="hidden" name="cambio_id" value="<?php echo (int) $c['id']; ?>">
                    <button type="submit" name="aprobar" class="az-btn az-btn-primary">Aprobar</button>
                    <button type="submit" name="rechazar" class="az-btn az-btn-danger">Descartar</button>
                </form>
            </div>
        <?php endforeach; ?>

    </div>
</div>