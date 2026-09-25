<?php
$auth = new Autenticacion();
$usuario = $auth->usuarioActual();

if (!$usuario || ($usuario['rol'] ?? '') !== 'admin') {
    wp_redirect(home_url('/inicio'));
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['id']) ? intval($_POST['id']) : 0);
$usuarioEditar = $auth->obtenerUsuario($id);

if (!$usuarioEditar) {
    wp_redirect(home_url('/usuarios'));
    exit;
}

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar'])) {
    $mensaje = $auth->actualizarUsuario(
        $id,
        $_POST['az_email'] ?? '',
        $_POST['az_password'] ?? ''
    );

    if ($mensaje === "Usuario actualizado correctamente.") {
        $usuarioEditar = $auth->obtenerUsuario($id);
        echo "<script>
                setTimeout(() => {
                    window.location.href='" . esc_url(home_url('/usuarios')) . "';
                }, 1500);
              </script>";
    }
}
?>

<div class="az-page az-auth">
    <div class="az-container">

        <h2 class="az-title az-text-center az-mb-lg">Editar usuario</h2>

        <?php if ($mensaje) : ?>
            <p class="az-mensaje"><?php echo esc_html($mensaje); ?></p>
        <?php endif; ?>

        <div class="az-form">
            <p class="az-subtitle az-text-center az-mb-lg">
                <strong><?php echo esc_html($usuarioEditar['nombre']); ?></strong>
                — <?php echo $usuarioEditar['rol'] === 'admin' ? 'Administrador' : 'Usuario'; ?>
            </p>

            <form method="post" action="">
                <input type="hidden" name="id" value="<?php echo (int) $usuarioEditar['id']; ?>">

                <label for="az_email" class="az-label">Email</label>
                <input type="email" id="az_email" name="az_email" class="az-input az-mb-md"
                       value="<?php echo esc_attr($usuarioEditar['email']); ?>" required>

                <label for="az_password" class="az-label">Nueva contraseña (dejar vacío para no cambiarla)</label>
                <input type="password" id="az_password" name="az_password" class="az-input az-mb-sm"
                       autocomplete="new-password">

                <label class="az-label az-mb-lg">
                    <input type="checkbox"
                           onclick="document.getElementById('az_password').type = this.checked ? 'text' : 'password';">
                    Mostrar contraseña
                </label>

                <button type="submit" name="guardar" class="az-btn az-btn-primary">Guardar cambios</button>
            </form>
        </div>

        <a href="<?php echo esc_url(home_url('/usuarios')); ?>" class="az-btn az-btn-secondary">Volver a usuarios</a>

    </div>
</div>