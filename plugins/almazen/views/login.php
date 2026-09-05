<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth = new Autenticacion();
    $resultado = $auth->login($_POST['email'], $_POST['password']);
    echo '<p class="az-mensaje">' . esc_html($resultado) . '</p>';

    if ($resultado === "Login correcto.") {
        // Redirigir al panel
        wp_redirect(home_url('/panel'));
        exit;
    }
}
?>

<div class="az-form">
    <h2>Iniciar sesión</h2>
    <form method="post" action="">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Contraseña" required>
        <button type="submit">Entrar</button>
    </form>

    <div class="az-links">
        <a href="<?php echo esc_url(home_url('/inicio')); ?>" class="az-btn-volver">Volver al inicio</a>
        <a href="<?php echo esc_url(home_url('/recuperar-password')); ?>" class="az-btn-recuperar">¿Olvidaste tu contraseña?</a>
    </div>
</div>
