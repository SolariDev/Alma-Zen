<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth = new Autenticacion();
    $mensaje = $auth->registrar($_POST['nombre'], $_POST['email'], $_POST['password'], 'admin');
    echo '<p class="az-mensaje">' . esc_html($mensaje) . '</p>';
}
?>

<div class="az-form">
    <h2>Registrar admin</h2>
    <form method="post" action="">
        <input type="text" name="nombre" placeholder="Nombre" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Contraseña" required>
        <button type="submit">Registrar admin</button>
    </form>
    <a href="<?php echo esc_url(home_url('/inicio')); ?>" class="az-btn-volver">Volver al inicio</a>
</div>
