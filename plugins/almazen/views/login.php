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

<div class="az-page az-auth">
    <div class="az-container">

        <div class="az-logo">
            <img src="<?php echo ALMAZEN_URL . 'assets/img/logo-az.png'; ?>" alt="Alma-Zen" />
        </div>

        <div class="az-form">
            <h2 class="az-title az-text-center az-mb-lg">Iniciar sesión</h2>
            <form method="post" action="">
                <input type="email" name="az_login_email" class="az-input az-mb-md" placeholder="Email" autocomplete="off" required>
                <input type="password" name="az_login_password" class="az-input az-mb-lg" placeholder="Contraseña" autocomplete="new-password" required>
                <button type="submit" class="az-btn az-btn-primary az-mb-md">Entrar</button>
            </form>
            <a href="<?php echo esc_url(home_url('/recuperar-password')); ?>" class="az-link">¿Olvidaste tu contraseña?</a>
        </div>

        <a href="<?php echo esc_url(home_url('/inicio')); ?>" class="az-btn az-btn-secondary az-mb-md">Volver al inicio</a>

    </div>
</div>