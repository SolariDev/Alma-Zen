<?php
$auth = new Autenticacion();
$usuario = $auth->usuarioActual();

if (!$usuario) {
    wp_redirect(home_url('/inicio'));
    exit;
}

if (isset($_POST['logout'])) {
    $auth->logout();
    wp_redirect(home_url('/inicio'));
    exit;
}
?>

<div class="az-page az-auth">
    <div class="az-container">

        <h2 class="az-title az-text-center az-mb-lg">Gestión de productos</h2>

        <div class="az-botones az-mb-lg">
            <a href="<?php echo esc_url(home_url('/registrar-producto')); ?>" class="az-btn az-btn-secondary">Registrar producto</a>
            <a href="<?php echo esc_url(home_url('/editar-producto')); ?>" class="az-btn az-btn-secondary">Editar producto</a>

            <form method="post" class="az-text-center">
                <button type="submit" name="logout" class="az-btn az-btn-secondary az-btn-sm">Cerrar sesión</button>
            </form>

        </div>
    </div>
</div>

