<?php
if (!defined('ABSPATH')) {
    exit;
}

$mensaje  = '';
$redirect = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!wp_verify_nonce($_POST['az_nonce'] ?? '', 'az_login')) {
        $mensaje = '⚠️ Inactividad prolongada. Probá de nuevo.';
    } else {
        $auth = new Autenticacion();
        $mensaje = $auth->login(
            $_POST['az_login_email'] ?? '',
            $_POST['az_login_password'] ?? ''
        );

        if ($mensaje === "Login correcto.") {

            $rol = $auth->rolActual();

            if ($rol === 'admin') {
                $redirect = esc_url(home_url('/panel-admin'));
            } else {
                $redirect = esc_url(home_url('/panel-usuario'));
            }
        }
    }
}
?>

<div class="az-page az-auth">
    <div class="az-container">

        <div class="az-logo">
            <img src="<?php echo ALMAZEN_URL . 'assets/img/logo-az.png'; ?>" alt="Alma-Zen" />
        </div>

        <div class="az-text-center az-mb-lg">
            <h2 class="az-title">Iniciar sesión</h2>
            <p class="az-subtitle">Ingresá con tu cuenta</p>
        </div>

        <?php if ($mensaje) : ?>
            <p class="az-mensaje"><?php echo esc_html($mensaje); ?></p>
        <?php endif; ?>

        <form method="post" action="" class="az-form">
            <?php wp_nonce_field('az_login', 'az_nonce'); ?>

            <div class="az-field">
                <label for="az_login_email" class="az-label">Email</label>
                <input type="email" id="az_login_email" name="az_login_email" class="az-input" placeholder="nombre@correo.com" autocomplete="off" required>
            </div>

            <div class="az-field">
                <label for="az_login_password" class="az-label">Contraseña</label>
                <input type="password" id="az_login_password" name="az_login_password" class="az-input" placeholder="Contraseña" autocomplete="off" required>
            </div>

            <div class="az-form-actions">
                <button type="submit" class="az-btn az-btn-primary">Entrar</button>
            </div>
        </form>

    </div>
</div>

<?php if ($redirect) : ?>
    <script>
        setTimeout(() => {
            window.location.href = '<?php echo $redirect; ?>';
        }, 1000);
    </script>
<?php endif; ?>