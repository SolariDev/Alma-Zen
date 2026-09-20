<?php
$auth = new Autenticacion();

$mensaje = '';
$paso = 'email'; // email | password | done
$email_valido = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['nueva_password'], $_POST['email'])) {
        // Paso 2: actualizar contraseña
        $ok = $auth->actualizarPassword($_POST['email'], $_POST['nueva_password']);

        if ($ok) {
            $mensaje = 'Contraseña actualizada correctamente.';
            $paso = 'done';
        } else {
            $mensaje = 'Error al actualizar la contraseña.';
            $paso = 'password';
            $email_valido = sanitize_email($_POST['email']);
        }

    } elseif (isset($_POST['email'])) {
        // Paso 1: validar email
        $usuario = $auth->usuarioPorEmail($_POST['email']);

        if ($usuario) {
            $paso = 'password';
            $email_valido = sanitize_email($_POST['email']);
        } else {
            $mensaje = 'El email no está registrado.';
            $paso = 'email';
        }
    }
}
?>

<div class="az-page az-auth">
    <div class="az-container">

        <div class="az-logo">
            <img src="<?php echo ALMAZEN_URL . 'assets/img/logo-az.png'; ?>" alt="Alma-Zen" />
        </div>

        <div class="az-form">
            <h2 class="az-title az-text-center az-mb-lg">Recuperar contraseña</h2>

            <?php if ($mensaje) : ?>
                <p class="az-mensaje"><?php echo esc_html($mensaje); ?></p>
            <?php endif; ?>

            <?php if ($paso === 'email') : ?>
                <form method="post" action="">
                    <input type="email" name="email" class="az-input az-mb-lg" placeholder="Tu email registrado" autocomplete="off" required>
                    <button type="submit" class="az-btn az-btn-primary">Continuar</button>
                </form>

            <?php elseif ($paso === 'password') : ?>
                <form method="post" action="">
                    <input type="hidden" name="email" value="<?php echo esc_attr($email_valido); ?>">
                    <input type="password" name="nueva_password" class="az-input az-mb-lg" placeholder="Nueva contraseña" autocomplete="new-password" required>
                    <button type="submit" class="az-btn az-btn-primary">Actualizar contraseña</button>
                </form>

            <?php elseif ($paso === 'done') : ?>
                <a href="<?php echo esc_url(home_url('/login')); ?>" class="az-btn az-btn-primary">Iniciar sesión</a>
            <?php endif; ?>
        </div>

        <a href="<?php echo esc_url(home_url('/inicio')); ?>" class="az-btn az-btn-secondary az-mb-md">Volver al inicio</a>

    </div>
</div>