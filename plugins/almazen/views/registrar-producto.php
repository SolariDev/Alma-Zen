<?php

global $wpdb;
$tabla_productos = $wpdb->prefix . 'alm_productos';

$producto_guardado = false;

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

        if ($resultado === false) {
            echo '<p class="az-mensaje">❌ Error al guardar: ' . esc_html($wpdb->last_error) . '</p>';
        } else {
            echo '<p class="az-mensaje">✅ Producto guardado correctamente.</p>';
            $producto_guardado = true;
        }
    }
?>

<div class="az-panel az-admin">
    <h2>Registrar producto</h2>
    <form method="post" class="az-form">
        <label for="nombre">Nombre del producto</label>
        <input type="text" id="nombre" name="nombre" required>

        <label for="marca">Marca</label>
        <input type="text" id="marca" name="marca">

        <label for="precio">Precio</label>
        <input type="number" id="precio" name="precio" step="1" min="0" required>

        <label for="cantidad">Cantidad</label>
        <input type="number" id="cantidad" name="cantidad" step="0.01" required>

        <label for="unidad">Unidad de medida</label>
        <select id="unidad" name="unidad" required>
            <option value="lt">Litro</option>
            <option value="ml">Mililitros</option>
            <option value="kg">Kilogramos</option>
            <option value="gr">Gramos</option>
            <option value="unidad">Unidad</option>
        </select>

        <label for="empaque">Empaque / Presentación</label>
        <select id="empaque" name="empaque">
            <option value="">Sin empaque</option>
            <option value="pack4">Pack de 4</option>
            <option value="pack6">Pack de 6</option>
            <option value="pack12">Pack de 12</option>
            <option value="bolsa">Bolsa</option>
            <option value="tarrina">Tarrina</option>
            <option value="caja">Caja</option>
        </select>

        <div class="az-admin-tools">
            <button type="submit" name="guardar_producto" class="az-btn">Guardar producto</button>
        </div>
    </form>

    <div class="az-admin-tools">
        <a href="<?php echo esc_url(home_url('/panel-admin')); ?>" class="az-btn admin">Volver al panel</a>
    </div>    
</div>