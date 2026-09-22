<?php
class Autenticacion {

    /** @var mixed */
    private $wpdb;

    /** @var string */
    private $tabla_usuarios;

    // Cierre de sesión automático tras 30 min de inactividad
    private const TIEMPO_INACTIVIDAD = 1800;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->tabla_usuarios = $this->wpdb->prefix . 'alm_usuarios';

        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'domain'   => '',
                'secure'   => is_ssl(),   // true solo si la request es HTTPS; no rompe localhost
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    public function registrar(string $nombre, string $email, string $password, string $rol = 'usuario'): string {

        if (strlen($password) < 8) {
            return "La contraseña debe tener al menos 8 caracteres.";
        }

        $existe = (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tabla_usuarios} WHERE email = %s",
                sanitize_email($email)
            )
        );

        if ($existe > 0) {
            return "El email ya está registrado.";
        }

        $hash = wp_hash_password($password);

        $insertado = $this->wpdb->insert(
            $this->tabla_usuarios,
            [
                'nombre'   => sanitize_text_field($nombre),
                'email'    => sanitize_email($email),
                'password' => $hash,
                'rol'      => sanitize_text_field($rol)
            ],
            ['%s', '%s', '%s', '%s']
        );

        if ($insertado === false) {
            error_log('Alma-Zen registrar() error: ' . $this->wpdb->last_error);
            return "Hubo un error al registrar el usuario. Intentá de nuevo.";
        }

        return "Usuario registrado correctamente.";
    }

    public function login(string $email, string $password): string {
        $usuario = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->tabla_usuarios} WHERE email = %s",
                sanitize_email($email)
            ),
            ARRAY_A
        );

        if (!$usuario || !wp_check_password($password, $usuario['password'])) {
            return "Email o contraseña incorrectos.";
        }

        session_regenerate_id(true); // nueva sesión al autenticar, evita fixation

        $_SESSION['usuario_id']    = $usuario['id'];
        $_SESSION['usuario']       = $usuario['nombre'];
        $_SESSION['usuario_rol']   = $usuario['rol'];
        $_SESSION['ultimo_acceso'] = time();

        return "Login correcto.";
    }

    public function logout(): void {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        session_destroy();
    }

    public function usuarioActual(): ?array {
        if (empty($_SESSION['usuario_id'])) {
            return null;
        }

        if (!empty($_SESSION['ultimo_acceso']) && (time() - $_SESSION['ultimo_acceso']) > self::TIEMPO_INACTIVIDAD) {
            $this->logout();
            return null;
        }
        $_SESSION['ultimo_acceso'] = time();

        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->tabla_usuarios} WHERE id = %d",
                intval($_SESSION['usuario_id'])
            ),
            ARRAY_A
        );
    }

    public function rolActual(): ?string {
        return $_SESSION['usuario_rol'] ?? null;
    }

    public function usuarioPorEmail(string $email): ?array {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->tabla_usuarios} WHERE email = %s",
                sanitize_email($email)
            ),
            ARRAY_A
        );
    }

    public function actualizarPassword(string $email, string $nuevaPassword): bool {
        if (strlen($nuevaPassword) < 8) {
            return false;
        }

        $hash = wp_hash_password($nuevaPassword);
        $actualizado = $this->wpdb->update(
            $this->tabla_usuarios,
            ['password' => $hash],
            ['email' => sanitize_email($email)],
            ['%s'],
            ['%s']
        );
        return (bool) $actualizado;
    }
}