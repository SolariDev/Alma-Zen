<?php
$auth = new Autenticacion();
$usuarioActual = $auth->usuarioActual();

// Solo un admin logueado puede crear cuentas nuevas, sean de usuario o de admin.
if (!$usuarioActual || ($usuarioActual['rol'] ?? '') !== 'admin') {
    wp_redirect(home_url('/inicio'));
    exit;
}

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
?>

<div class="az-page az-auth">
    <div class="az-container">

        <div class="az-logo">
            <img src="<?php echo ALMAZEN_URL . 'assets/img/logo-az.png'; ?>" alt="Alma-Zen" />
        </div>

        <div class="az-form">
            <h2 class="az-title az-text-center az-mb-lg">Agregar usuario</h2>

            <?php if ($mensaje && $mensaje !== "Usuario registrado correctamente.") : ?>
                <p class="az-mensaje"><?php echo esc_html($mensaje); ?></p>
            <?php endif; ?>

            <form method="post" action="">
                <input type="text" name="az_nombre" class="az-input az-mb-md" placeholder="Nombre" autocomplete="off" required>
                <input type="email" name="az_email" class="az-input az-mb-md" placeholder="Email" autocomplete="off" required>
                <input type="password" name="az_password" class="az-input az-mb-lg" placeholder="Contraseña" autocomplete="new-password" required>

                <div class="az-botones">
                    <button type="submit" name="crear_usuario" class="az-btn az-btn-primary">
                        Crear usuario
                    </button>
                    <button type="submit" name="crear_admin" class="az-btn az-btn-secondary"
                            onclick="return confirm('¿Confirmás que esta persona va a tener acceso total como administrador? Va a poder ver y cambiar todo en el sistema.');">
                        Crear administrador
                    </button>
                </div>
            </form>
        </div>

        <a href="<?php echo esc_url(home_url('/usuarios')); ?>" class="az-btn az-btn-secondary">Volver a usuarios</a>
    </div>
</div>