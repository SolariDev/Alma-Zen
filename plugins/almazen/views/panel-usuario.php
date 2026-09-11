<?php
$auth = new Autenticacion();
$usuario = $auth->usuarioActual();

if (!$usuario) {
    wp_redirect(home_url('/inicio'));
    exit;
}
?>

<div class="az-panel az-admin">
    <div class="az-logo">
        <img src="<?php echo ALMAZEN_URL . 'assets/img/logo-az.png'; ?>" alt="Alma-Zen" />
    </div>

    <h2>Panel de usuario: <?php echo esc_html($usuario['nombre']); ?></h2>
    
    <!-- Buscador de productos -->
    <form method="get" action="<?php echo esc_url(home_url('/panel-usuario')); ?>" class="az-form-buscar">
        <input type="text" name="q" placeholder="Buscar producto..." required>
        <button type="submit" class="az-btn">
            <img src="<?php echo ALMAZEN_URL . 'assets/img/buscar.png'; ?>" alt="Buscar" class="az-icon-buscar">
        </button>
    </form>

    <!-- Contenedor para mostrar resultado -->
    <div id="az-contenedor-productos">
        <?php
        if (isset($_GET['q'])) {
            $termino = sanitize_text_field($_GET['q']);
            $productosModel = new Productos();
            $resultados = $productosModel->buscarPorNombre($termino);

            if (!empty($resultados)) {
                // Ordenar resultados por fecha_actualizacion descendente
                usort($resultados, function($a, $b) {
                    return strtotime($b['fecha_actualizacion']) - strtotime($a['fecha_actualizacion']);
                });

                // Tomar el primero (última actualización)
                $prod = $resultados[0];

                echo '<div class="az-card">';
                echo '<h3>' . esc_html($prod['nombre']) . '</h3>';
                echo '<p>Precio: $' . esc_html($prod['precio']) . '</p>';
                echo '<p>Cantidad: ' . esc_html($prod['cantidad']) . '</p>';
                echo '<p>Unidad: ' . esc_html($prod['unidad']) . '</p>';
                echo '<p>Empaque: ' . esc_html($prod['empaque']) . '</p>';
                echo '<p>Última actualización: ' . date('d/m/Y H:i', strtotime($prod['fecha_actualizacion'])) . '</p>';
                echo '</div>';
            } else {
                echo '<p class="az-mensaje">No se encontró ningún producto.</p>';
            }
        }
        ?>
    </div>

    <!-- Botón de cerrar sesión -->
    <form method="post" class="az-logout-form">
        <button type="submit" name="logout" class="az-btn">Cerrar sesión</button>
    </form>
</div>

<?php
if (isset($_POST['logout'])) {
    $auth->logout();
    wp_redirect(home_url('/inicio'));
    exit;
}
?>