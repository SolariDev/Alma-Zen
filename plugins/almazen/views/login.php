<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth = new Autenticacion();
    $mensaje = $auth->login(
        $_POST['az_login_email'] ?? '',
        $_POST['az_login_password'] ?? ''
    );
    echo '<p class="az-mensaje">' . esc_html($mensaje) . '</p>';

    if ($mensaje === "Login correcto.") {

        $rol = $auth->rolActual();

         if ($rol === 'admin') {
            $redirect = esc_url(home_url('/panel-admin'));
        } else {
            $redirect = esc_url(home_url('/panel-usuario'));
        }
        
        echo "<script>
                setTimeout(() => {
                    window.location.href='" . $redirect . "';
                }, 1000);
              </script>";
    }
}
?>

<div class="az-form az-login">
    <div class="az-logo">
        <img src="<?php echo ALMAZEN_URL . 'assets/img/logo-az.png'; ?>" alt="Alma-Zen" />
    </div>
    <h2>Iniciar sesión</h2>
    <form method="post" action="">
        <input type="email" name="az_login_email" placeholder="Email" autocomplete="off" required>
        <input type="password" name="az_login_password" placeholder="Contraseña" autocomplete="new-password" required>
        <button type="submit" class="az-btn">Entrar</button>
    </form>

    <a href="<?php echo esc_url(home_url('/inicio')); ?>" class="az-btn-volver">Volver al inicio</a><br>
    <a href="<?php echo esc_url(home_url('/cambiar-password')); ?>" class="az-btn-volver">¿Olvidaste tu contraseña?</a>
</div>