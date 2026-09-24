<?php
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

        <h2 class="az-title az-text-center az-mb-lg">Cambios pendientes</h2>

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
            <div class="az-form az-mb-lg">
                <h3 class="az-title az-mb-sm">
                    <?php echo esc_html($c['producto_nombre'] ?? ($antes['nombre'] ?? 'Producto eliminado')); ?>
                </h3>
                <p class="az-mb-md">
                    Solicitado por <?php echo esc_html($c['solicitante'] ?? 'usuario eliminado'); ?>
                    — <?php echo esc_html(date('d/m/Y H:i', strtotime($c['fecha_solicitud']))); ?>
                </p>

                <?php foreach ($campos as $k => $etiqueta) :
                    if (($antes[$k] ?? '') == ($nuevo[$k] ?? '')) {
                        continue;
                    } ?>
                    <p class="az-mb-sm">
                        <strong><?php echo esc_html($etiqueta); ?>:</strong>
                        <?php echo esc_html($antes[$k] ?? '—'); ?> → <?php echo esc_html($nuevo[$k] ?? '—'); ?>
                    </p>
                <?php endforeach; ?>

                <form method="post" class="az-botones">
                    <input type="hidden" name="cambio_id" value="<?php echo (int) $c['id']; ?>">
                    <button type="submit" name="aprobar" class="az-btn az-btn-primary">Aprobar</button>
                    <button type="submit" name="rechazar" class="az-btn az-btn-danger">Descartar</button>
                </form>
            </div>
        <?php endforeach; ?>

        <div class="az-botones az-mb-lg">
            <a href="<?php echo esc_url(home_url('/panel-admin')); ?>" class="az-btn az-btn-secondary">Volver al panel</a>
        </div>

    </div>
</div>