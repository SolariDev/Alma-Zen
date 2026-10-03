<?php
if (!defined('ABSPATH')) {
    exit;
}

$auth = new Autenticacion();
$usuarioActual = $auth->usuarioActual();

// Solo un admin logueado puede crear cuentas nuevas, sean de usuario o de admin.
if (!$usuarioActual || ($usuarioActual['rol'] ?? '') !== 'admin') {
    wp_redirect(home_url('/inicio'));
    exit;
}

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!wp_verify_nonce($_POST['az_nonce'] ?? '', 'az_registrar_usuario')) {
        $mensaje = '⚠️ Inactividad prolongada. Probá de nuevo.';
    } else {
    // El rol se decide según cuál de los dos botones se apretó.
    $rol = isset($_POST['crear_admin']) ? 'admin' : 'usuario';

    $mensaje = $auth->registrar(
        $_POST['az_nombre'] ?? '',
        $_POST['az_email'] ?? '',
        $_POST['az_password'] ?? '',
        $rol
    );

    if ($mensaje === "Usuario registrado correctamente.") {
        echo '<p class="az-mensaje">' . esc_html($mensaje) . '</p>';
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
                <h2 class="az-title">Agregar usuario</h2>
                <p class="az-subtitle">Completá los datos de la persona</p>
            </div>

            <a href="<?php echo esc_url(home_url('/usuarios')); ?>" class="az-back" aria-label="Volver a usuarios">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M19 12H5"></path>
                    <path d="m12 19-7-7 7-7"></path>
                </svg>
            </a>
        </div>

        <?php if ($mensaje && $mensaje !== "Usuario registrado correctamente.") : ?>
            <p class="az-mensaje"><?php echo esc_html($mensaje); ?></p>
        <?php endif; ?>

        <form method="post" action="" class="az-form">
            <?php wp_nonce_field('az_registrar_usuario', 'az_nonce'); ?>

            <div class="az-field">
                <label for="az_nombre" class="az-label">Nombre</label>
                <input type="text" id="az_nombre" name="az_nombre" class="az-input" placeholder="Nombre" autocomplete="off" required>
            </div>

            <div class="az-field">
                <label for="az_email" class="az-label">Email</label>
                <input type="email" id="az_email" name="az_email" class="az-input" placeholder="nombre@correo.com" autocomplete="off" required>
            </div>

            <div class="az-field">
                <label for="az_password" class="az-label">Contraseña</label>
                <input type="password" id="az_password" name="az_password" class="az-input" placeholder="Contraseña" autocomplete="new-password" required>
            </div>

            <label class="az-label">
                <input type="checkbox"
                       onclick="document.getElementById('az_password').type = this.checked ? 'text' : 'password';">
                Mostrar contraseña
            </label>

            <div class="az-form-actions">
                <div class="az-botones">
                    <button type="submit" name="crear_usuario" class="az-btn az-btn-primary">
                        Crear usuario
                    </button>
                    <button type="submit" name="crear_admin" class="az-btn az-btn-secondary"
                            onclick="return confirm('¿Confirmás que esta persona va a tener acceso total como administrador? Va a poder ver y cambiar todo en el sistema.');">
                        Crear administrador
                    </button>
                </div>
            </div>
        </form>

    </div>
</div>