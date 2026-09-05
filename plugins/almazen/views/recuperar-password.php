<?php
$auth = new Autenticacion();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email']) && !isset($_POST['nueva_password'])) {
        // Paso 1: usuario ingresa email
        $usuario = $auth->usuarioPorEmail($_POST['email']);
        if ($usuario) {
            echo '<form method="post" action="">
                    <input type="hidden" name="email" value="' . esc_attr($_POST['email']) . '">
                    <input type="password" name="nueva_password" placeholder="Nueva contraseña" required>
                    <button type="submit">Actualizar contraseña</button>
                  </form>';
        } else {
            echo '<p class="az-mensaje">El email no está registrado.</p>';
        }
    } elseif (isset($_POST['nueva_password'], $_POST['email'])) {
        // Paso 2: usuario ingresa nueva contraseña
        $ok = $auth->actualizarPassword($_POST['email'], $_POST['nueva_password']);
        if ($ok) {
            echo '<p class="az-mensaje">Contraseña actualizada correctamente. 
                  <a href="' . esc_url(home_url('/login')) . '">Iniciar sesión</a></p>';
        } else {
            echo '<p class="az-mensaje">Error al actualizar la contraseña.</p>';
        }
    }
}
?>

<div class="az-form">
    <h2>Recuperar contraseña</h2>
    <form method="post" action="">
        <input type="email" name="email" placeholder="Tu email registrado" required>
        <button type="submit">Continuar</button>
    </form>
    <a href="<?php echo esc_url(home_url('/inicio')); ?>" class="az-btn-volver">Volver al inicio</a>
</div>