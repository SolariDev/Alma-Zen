<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth = new Autenticacion();
    $mensaje = $auth->registrar(
        $_POST['az_admin_nombre'] ?? '',
        $_POST['az_admin_email'] ?? '',
        $_POST['az_admin_password'] ?? '',
        'admin'
    );
    echo '<p class="az-mensaje">' . esc_html($mensaje) . '</p>';

    if ($mensaje === "Usuario registrado correctamente.") {
        echo "<script>
                setTimeout(() => {
                    window.location.href='" . esc_url(home_url('/login')) . "';
                }, 1500);
              </script>";
    }
}
?>

<div class="az-form">
    <div class="az-logo">
        <img src="<?php echo ALMAZEN_URL . 'assets/img/logo-az.png'; ?>" alt="Alma-Zen" />
    </div>
    <h2>Registrar admin</h2>
    <form method="post" action="">
        <input type="text" name="az_admin_nombre" placeholder="Nombre" autocomplete="off" required>
        <input type="email" name="az_admin_email" placeholder="Email" autocomplete="off" required>
        <input type="password" name="az_admin_password" placeholder="Contraseña" autocomplete="new-password" required>
        <button type="submit" class="az-btn">Registrar admin</button>
    </form>

    <a href="<?php echo esc_url(home_url('/inicio')); ?>" class="az-btn-volver">Volver al inicio</a>
</div>