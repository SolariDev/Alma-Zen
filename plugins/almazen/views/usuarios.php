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

if (isset($_POST['logout']) && wp_verify_nonce($_POST['az_nonce'] ?? '', 'az_logout')) {
    $auth->logout();
    wp_redirect(home_url('/inicio'));
    exit;
}

$mensaje = '';

if (isset($_POST['eliminar'])) {
    if (!wp_verify_nonce($_POST['az_nonce'] ?? '', 'az_gestion_usuarios')) {
        $mensaje = '⚠️ Inactividad prolongada. Probá de nuevo.';
    } else {
        $id_eliminar = intval($_POST['id']);
        $mensaje = $auth->eliminarUsuario($id_eliminar, (int) $usuario['id']);
    }
}

$usuarios = $auth->listarUsuarios();
$totalAdmins = count(array_filter($usuarios, function ($u) {
    return $u['rol'] === 'admin';
}));
?>

<div class="az-page az-auth">
    <div class="az-container">

        <div class="az-header">
            <div>
                <h2 class="az-title">Usuarios</h2>
                <p class="az-subtitle">Gestioná quién usa la app</p>
            </div>

            <a href="<?php echo esc_url(home_url('/panel-admin')); ?>" class="az-back" aria-label="Volver al panel">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M19 12H5"></path>
                    <path d="m12 19-7-7 7-7"></path>
                </svg>
            </a>
        </div>

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
            <div class="az-form">
                <h3 class="az-ficha-titulo"><?php echo esc_html($u['nombre']); ?></h3>

                <div class="az-ficha-fila">
                    <span class="az-ficha-etiqueta">Email</span>
                    <span class="az-ficha-valor"><?php echo esc_html($u['email']); ?></span>
                </div>

                <div class="az-ficha-fila">
                    <span class="az-ficha-etiqueta">Rol</span>
                    <span class="az-ficha-valor">
                        <?php echo $es_este_admin ? 'Administrador' : 'Usuario'; ?><?php echo $es_uno_mismo ? ' (vos)' : ''; ?>
                    </span>
                </div>

                <div class="<?php echo $puede_eliminar ? 'az-row' : 'az-botones'; ?> az-form-actions">
                    <a href="<?php echo esc_url(add_query_arg('id', $u['id'], home_url('/editar-usuario'))); ?>"
                       class="az-btn az-btn-secondary">
                        Editar
                    </a>

                    <?php if ($puede_eliminar) : ?>
                        <form method="post">
                            <?php wp_nonce_field('az_gestion_usuarios', 'az_nonce'); ?>
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

        <form method="post" class="az-text-center">
            <?php wp_nonce_field('az_logout', 'az_nonce'); ?>
            <button type="submit" name="logout" class="az-btn az-btn-secondary az-btn-sm">Cerrar sesión</button>
        </form>

    </div>
</div>