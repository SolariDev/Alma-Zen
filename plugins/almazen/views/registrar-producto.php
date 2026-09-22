<?php
global $wpdb;
$tabla_productos = $wpdb->prefix . 'alm_productos';

$producto_guardado = false;

$mensaje = '';

// Procesar formulario
if ( isset($_POST['guardar_producto']) ) {

    $nombre   = sanitize_text_field($_POST['nombre']);
    $marca    = sanitize_text_field($_POST['marca']);
    $precio   = intval($_POST['precio']);
    $cantidad = floatval($_POST['cantidad']);
    $unidad   = sanitize_text_field($_POST['unidad']);
    $empaque  = sanitize_text_field($_POST['empaque']);
        
        $resultado = $wpdb->insert(
            $tabla_productos,
            [
                'nombre'              => $nombre,
                'marca'               => $marca,
                'precio'              => $precio,
                'cantidad'            => $cantidad,
                'unidad'              => $unidad,
                'empaque'             => $empaque,
                'fecha_actualizacion' => current_time('mysql')
            ],
            ['%s', '%s', '%d', '%s','%s','%s','%s']
        );

        $mensaje = ($resultado === false)
        ? '❌ Error al guardar: ' . esc_html($wpdb->last_error)
        : '✅ Producto guardado correctamente.';
    }
?>

<div class="az-page az-auth">
    <div class="az-container">

        <h2 class="az-title az-text-center az-mb-lg">Registrar producto</h2>

        <?php if ($mensaje) : ?>
            <p class="az-mensaje"><?php echo $mensaje; ?></p>
        <?php endif; ?>

        <form method="post" class="az-form">
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
            <a href="<?php echo esc_url(home_url('/panel-admin')); ?>" class="az-btn az-btn-secondary">Volver al panel</a>
        </div>

    </div>
</div>