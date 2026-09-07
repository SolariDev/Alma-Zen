<?php
class Autenticacion {

    /** @var mixed */
    private $wpdb;

    /** @var string */
    private $tabla_usuarios;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        // Nombre exacto de la tabla en tu BD (sin prefijo automático)
        $this->tabla_usuarios = 'az_alm_usuarios';

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Registrar un nuevo usuario
     */
    public function registrar(string $nombre, string $email, string $password, string $rol = 'usuario'): string {
        // Evitar duplicados
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
            array(
                'nombre'   => sanitize_text_field($nombre),
                'email'    => sanitize_email($email),
                'password' => $hash,
                'rol'      => sanitize_text_field($rol)
            ),
            array('%s','%s','%s','%s')
        );

         if ($insertado === false) {
            // Mostrar error SQL para depuración
            return "Error al registrar usuario: " . $this->wpdb->last_error;
        }

        return $insertado ? "Usuario registrado correctamente." : "Error al registrar usuario.";
    }

    /**
     * Login de usuario
     */
    public function login(string $email, string $password): string {
        $usuario = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->tabla_usuarios} WHERE email = %s",
                sanitize_email($email)
            ),
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

    public function logout(): void {
        unset($_SESSION['usuario_id'], $_SESSION['usuario'], $_SESSION['usuario_rol']);
        session_destroy();
    }

    public function usuarioActual(): ?array {
        if (!empty($_SESSION['usuario_id'])) {
            return $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT * FROM {$this->tabla_usuarios} WHERE id = %d",
                    intval($_SESSION['usuario_id'])
                ),
                ARRAY_A
            );
        }
        return null;
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
