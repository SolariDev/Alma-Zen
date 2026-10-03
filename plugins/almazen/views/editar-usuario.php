<?php
if (!defined('ABSPATH')) {
    exit;
}

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
    if (!wp_verify_nonce($_POST['az_nonce'] ?? '', 'az_editar_usuario')) {
        $mensaje = '⚠️ Inactividad prolongada. Probá de nuevo.';
    } else {
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
}
?>

<div class="az-page az-auth">
    <div class="az-container">

        <div class="az-header">
            <div>
                <h2 class="az-title">Editar usuario</h2>
                <p class="az-subtitle">
                    <?php echo esc_html($usuarioEditar['nombre']); ?>
                    — <?php echo $usuarioEditar['rol'] === 'admin' ? 'Administrador' : 'Usuario'; ?>
                </p>
            </div>

            <a href="<?php echo esc_url(home_url('/usuarios')); ?>" class="az-back" aria-label="Volver a usuarios">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M19 12H5"></path>
                    <path d="m12 19-7-7 7-7"></path>
                </svg>
            </a>
        </div>

        <?php if ($mensaje) : ?>
            <p class="az-mensaje"><?php echo esc_html($mensaje); ?></p>
        <?php endif; ?>

        <form method="post" action="" class="az-form">
            <?php wp_nonce_field('az_editar_usuario', 'az_nonce'); ?>
            <input type="hidden" name="id" value="<?php echo (int) $usuarioEditar['id']; ?>">

            <div class="az-field">
                <label for="az_email" class="az-label">Email</label>
                <input type="email" id="az_email" name="az_email" class="az-input"
                       value="<?php echo esc_attr($usuarioEditar['email']); ?>" required>
            </div>

            <div class="az-field">
                <label for="az_password" class="az-label">Nueva contraseña <small>(dejar vacío para no cambiarla)</small></label>
                <input type="password" id="az_password" name="az_password" class="az-input" autocomplete="new-password">
            </div>

            <label class="az-label">
                <input type="checkbox"
                       onclick="document.getElementById('az_password').type = this.checked ? 'text' : 'password';">
                Mostrar contraseña
            </label>

            <div class="az-form-actions">
                <button type="submit" name="guardar" class="az-btn az-btn-primary">Guardar cambios</button>
            </div>
        </form>

    </div>
</div>