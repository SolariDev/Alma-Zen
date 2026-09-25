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

    /* ------------------------------------------------------------------
     * Gestión de usuarios (panel del administrador)
     * ------------------------------------------------------------------ */

    /**
     * Lista de usuarios para el panel del admin. No trae la contraseña.
     */
    public function listarUsuarios(): array {
        return $this->wpdb->get_results(
            "SELECT id, nombre, email, rol FROM {$this->tabla_usuarios} ORDER BY nombre ASC",
            ARRAY_A
        );
    }

    /**
     * Datos de un usuario puntual, sin la contraseña.
     */
    public function obtenerUsuario(int $id): ?array {
        $fila = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT id, nombre, email, rol FROM {$this->tabla_usuarios} WHERE id = %d",
                $id
            ),
            ARRAY_A
        );
        return $fila ?: null;
    }

    public function contarAdmins(): int {
        return (int) $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->tabla_usuarios} WHERE rol = 'admin'"
        );
    }

    /**
     * El admin actualiza el email y, opcionalmente, la contraseña de un usuario.
     * Dejar $nuevaPassword vacío mantiene la contraseña actual.
     */
    public function actualizarUsuario(int $id, string $nuevoEmail, string $nuevaPassword = ''): string {
        $nuevoEmail = sanitize_email($nuevoEmail);

        if (!is_email($nuevoEmail)) {
            return "El email no es válido.";
        }

        $existe = (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tabla_usuarios} WHERE email = %s AND id != %d",
                $nuevoEmail,
                $id
            )
        );
        if ($existe > 0) {
            return "Ese email ya lo usa otro usuario.";
        }

        $datos   = ['email' => $nuevoEmail];
        $formato = ['%s'];

        if ($nuevaPassword !== '') {
            if (strlen($nuevaPassword) < 8) {
                return "La contraseña debe tener al menos 8 caracteres.";
            }
            $datos['password'] = wp_hash_password($nuevaPassword);
            $formato[] = '%s';
        }

        $actualizado = $this->wpdb->update(
            $this->tabla_usuarios,
            $datos,
            ['id' => $id],
            $formato,
            ['%d']
        );

        if ($actualizado === false) {
            error_log('Alma-Zen actualizarUsuario() error: ' . $this->wpdb->last_error);
            return "Hubo un error al actualizar el usuario.";
        }

        return "Usuario actualizado correctamente.";
    }

    /**
     * Elimina un usuario, cuidando no dejar el sistema sin ningún administrador
     * y que nadie se elimine a sí mismo.
     */
    public function eliminarUsuario(int $id, int $solicitado_por): string {
        if ($id === $solicitado_por) {
            return "No podés eliminar tu propia cuenta.";
        }

        $usuario = $this->obtenerUsuario($id);
        if (!$usuario) {
            return "El usuario no existe.";
        }

        if ($usuario['rol'] === 'admin' && $this->contarAdmins() <= 1) {
            return "No se puede eliminar al único administrador.";
        }

        $eliminado = $this->wpdb->delete($this->tabla_usuarios, ['id' => $id], ['%d']);

        return $eliminado ? "Usuario eliminado correctamente." : "No se pudo eliminar el usuario.";
    }
}