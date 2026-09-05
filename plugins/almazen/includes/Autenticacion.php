<?php
class Autenticacion {

    /** @var mixed */
    private $wpdb;

    /** @var string */
    private $tabla_usuarios;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->tabla_usuarios = $this->wpdb->prefix . 'az_usuarios';

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Registrar un nuevo usuario
     */
    public function registrar(string $nombre, string $email, string $password, string $rol = 'usuario'): string {
        // Evitar duplicados
        $existe = $this->wpdb->get_var(
            $this->wpdb->prepare("SELECT COUNT(*) FROM {$this->tabla_usuarios} WHERE email = %s", sanitize_email($email))
        );

        if ($existe > 0) {
            return "El email ya está registrado.";
        }

        $hash = wp_hash_password($password);

        $insertado = $this->wpdb->insert(
            $this->tabla_usuarios,
            array(
                'nombre'   => sanitize_text_field($nombre),
                'email'    => sanitize_email($email),
                'password' => $hash,
                'rol'      => $rol
            ),
            array('%s','%s','%s','%s')
        );

        return $insertado ? "Usuario registrado correctamente." : "Error al registrar usuario.";
    }

    /**
     * Login de usuario
     */
    public function login(string $email, string $password): string {
        $usuario = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$this->tabla_usuarios} WHERE email = %s", sanitize_email($email)),
            ARRAY_A
        );

        if (!$usuario) {
            return "Usuario no encontrado.";
        }

        if (wp_check_password($password, $usuario['password'])) {
            $_SESSION['usuario_id']  = $usuario['id'];
            $_SESSION['usuario']     = $usuario['nombre'];
            $_SESSION['usuario_rol'] = $usuario['rol'];
            return "Login correcto.";
        }

        return "Contraseña incorrecta.";
    }

    /**
     * Logout de usuario
     */
    public function logout(): void {
        unset($_SESSION['usuario_id'], $_SESSION['usuario'], $_SESSION['usuario_rol']);
        session_destroy();
    }

    /**
     * Obtener usuario actual
     */
    public function usuarioActual(): ?array {
        if (!empty($_SESSION['usuario_id'])) {
            return $this->wpdb->get_row(
                $this->wpdb->prepare("SELECT * FROM {$this->tabla_usuarios} WHERE id = %d", intval($_SESSION['usuario_id'])),
                ARRAY_A
            );
        }
        return null;
    }

    /**
     * Obtener rol actual
     */
    public function rolActual(): ?string {
        return $_SESSION['usuario_rol'] ?? null;
    }

    /**
     * Buscar usuario por email
     */
    public function usuarioPorEmail(string $email): ?array {
        return $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$this->tabla_usuarios} WHERE email = %s", sanitize_email($email)),
            ARRAY_A
        );
    }

    /**
     * Actualizar contraseña
     */
    public function actualizarPassword(string $email, string $nuevaPassword): bool {
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