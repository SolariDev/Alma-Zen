<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth = new Autenticacion();
    $mensaje = $auth->registrar(
        $_POST['az_nombre'] ?? '',
        $_POST['az_email'] ?? '',
        $_POST['az_password'] ?? '',
        'usuario'
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

<div class="az-page az-auth">
    <div class="az-container">

        <div class="az-logo">
            <img src="<?php echo ALMAZEN_URL . 'assets/img/logo-az.png'; ?>" alt="Alma-Zen" />
        </div>
        <div class="az-form">
            <h2 class="az-title az-text-center az-mb-lg">Registrar usuario</h2>
            <form method="post" action="">
                <input type="text" name="az_nombre" class="az-input az-mb-md" placeholder="Nombre" autocomplete="off" required>
                <input type="email" name="az_email" class="az-input az-mb-md" placeholder="Email" autocomplete="off" required>
                <input type="password" name="az_password" class="az-input az-mb-lg" placeholder="Contraseña" autocomplete="new-password" required>
                <button type="submit" class="az-btn az-btn-primary">Registrate</button>
            </form>
        </div>

        <a href="<?php echo esc_url(home_url('/inicio')); ?>" class="az-btn az-btn-secondary">Volver al inicio</a>
    </div>    
</div>