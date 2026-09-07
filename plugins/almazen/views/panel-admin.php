<?php
$auth = new Autenticacion();
$usuario = $auth->usuarioActual();

if (!$usuario) {
    wp_redirect(home_url('/inicio'));
    exit;
}
?>

<div class="az-panel az-admin">
    <div class="az-logo">
        <img src="<?php echo ALMAZEN_URL . 'assets/img/logo-az.png'; ?>" alt="Alma-Zen" />
    </div>

    <h2>Panel de administración</h2>

    <div class="az-categorias">
        <?php
        $categorias = [
            ['slug' => 'alimentos-bebidas', 'nombre' => 'Alimentos y Bebidas'],
            ['slug' => 'higiene-limpieza', 'nombre' => 'Higiene y Limpieza'],
            ['slug' => 'kiosco', 'nombre' => 'Kiosco'],
            ['slug' => 'mascotas', 'nombre' => 'Mascotas'],
        ];

        foreach ($categorias as $cat) {
            echo '<div class="az-card" data-categoria="' . esc_attr($cat['slug']) . '">';
            echo '<img src="' . ALMAZEN_URL . 'assets/img/' . $cat['slug'] . '.png" alt="' . esc_attr($cat['nombre']) . '" class="az-icon">';
            echo '<h3>' . esc_html($cat['nombre']) . '</h3>';
            echo '</div>';
        }
        ?>
    </div>

    <div id="az-contenedor-productos"></div>

    <div class="az-botones az-admin-tools">
        <a href="<?php echo esc_url(home_url('/editar-productos')); ?>" class="az-btn">Gestionar productos</a>
        <a href="<?php echo esc_url(home_url('/lista-productos')); ?>" class="az-btn">Lista completa</a>
    </div>

    <form method="post" class="az-logout-form">
        <button type="submit" name="logout" class="az-btn">Cerrar sesión</button>
    </form>
</div>

<?php
if (isset($_POST['logout'])) {
    $auth->logout();
    wp_redirect(home_url('/inicio'));
    exit;
}
?>