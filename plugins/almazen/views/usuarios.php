<?php
$auth = new Autenticacion();
$usuario = $auth->usuarioActual();

if (!$usuario || ($usuario['rol'] ?? '') !== 'admin') {
    wp_redirect(home_url('/inicio'));
    exit;
}

if (isset($_POST['logout'])) {
    $auth->logout();
    wp_redirect(home_url('/inicio'));
    exit;
}

$mensaje = '';

if (isset($_POST['eliminar'])) {
    $id_eliminar = intval($_POST['id']);
    $mensaje = $auth->eliminarUsuario($id_eliminar, (int) $usuario['id']);
}

$usuarios = $auth->listarUsuarios();
$totalAdmins = count(array_filter($usuarios, function ($u) {
    return $u['rol'] === 'admin';
}));
?>

<div class="az-page az-auth">
    <div class="az-container">

        <h2 class="az-title az-text-center az-mb-lg">Usuarios</h2>

        <?php if ($mensaje) : ?>
            <p class="az-mensaje"><?php echo esc_html($mensaje); ?></p>
        <?php endif; ?>

        <div class="az-botones az-mb-lg">
            <a href="<?php echo esc_url(home_url('/registrar-usuario')); ?>" class="az-btn az-btn-primary">
                Agregar usuario
            </a>
        </div>

        <?php foreach ($usuarios as $u) :
            $es_este_admin   = $u['rol'] === 'admin';
            $es_uno_mismo    = (int) $u['id'] === (int) $usuario['id'];
            $puede_eliminar  = !$es_uno_mismo && !($es_este_admin && $totalAdmins <= 1);
        ?>
            <div class="az-form az-mb-md">
                <h3 class="az-title az-mb-sm"><?php echo esc_html($u['nombre']); ?></h3>
                <p class="az-mb-sm"><?php echo esc_html($u['email']); ?></p>
                <p class="az-mb-md">
                    <strong><?php echo $es_este_admin ? 'Administrador' : 'Usuario'; ?></strong>
                    <?php echo $es_uno_mismo ? ' (vos)' : ''; ?>
                </p>

                <div class="az-botones">
                    <a href="<?php echo esc_url(add_query_arg('id', $u['id'], home_url('/editar-usuario'))); ?>"
                       class="az-btn az-btn-secondary">
                        Editar
                    </a>

                    <?php if ($puede_eliminar) : ?>
                        <form method="post">
                            <input type="hidden" name="id" value="<?php echo (int) $u['id']; ?>">
                            <button type="submit" name="eliminar" class="az-btn az-btn-danger"
                                    onclick="return confirm('¿Seguro que querés eliminar a <?php echo esc_js($u['nombre']); ?>?');">
                                Eliminar
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="az-botones az-mb-lg">
            <a href="<?php echo esc_url(home_url('/panel-admin')); ?>" class="az-btn az-btn-secondary">Volver al panel</a>
        </div>

        <form method="post" class="az-text-center">
            <button type="submit" name="logout" class="az-btn az-btn-secondary az-btn-sm">Cerrar sesión</button>
        </form>

    </div>
</div>