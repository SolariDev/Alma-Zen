<?php
$auth = new Autenticacion();
$usuario = $auth->usuarioActual();

if (!$usuario) {
    wp_redirect(home_url('/inicio'));
    exit;
}
?>

<div class="az-panel az-admin">
    <!-- Logo institucional -->
    <div class="az-logo">
        <img src="<?php echo ALMAZEN_URL . 'assets/img/logo-az.png'; ?>" alt="Alma-Zen" />
    </div>

    <h2>Panel de administración</h2>

    <!-- 🔹 Tarjeta Editar producto -->
    <div class="az-card">
        <a href="<?php echo esc_url(home_url('/editar-producto')); ?>">
            <img src="<?php echo ALMAZEN_URL . 'assets/img/editar.png'; ?>" alt="Editar producto" class="az-icon">
            <h3>Editar producto</h3>
        </a>
    </div>

    <!-- 🔹 Tarjeta Registrar producto -->
    <div class="az-card">
        <a href="<?php echo esc_url(home_url('/registrar-producto')); ?>">
            <img src="<?php echo ALMAZEN_URL . 'assets/img/registrar-producto.png'; ?>" alt="Registrar producto" class="az-icon">
            <h3>Registrar producto</h3>
        </a>
    </div>

    <!-- 🔹 Botón cerrar sesión -->
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