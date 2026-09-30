<?php
if (!defined('ABSPATH')) {
    exit;
}

class Autenticacion {

    /** @var mixed */
    private $wpdb;

    /** @var string */
    private $tabla_usuarios;

    // Cierre de sesión automático tras 30 min de inactividad
    private const TIEMPO_INACTIVIDAD = 1800;

    // Rate limiting de login: intentos fallidos por (email + IP), y tope general por IP
    private const MAX_INTENTOS_LOGIN = 5;
    private const MAX_INTENTOS_IP    = 20;
    private const BLOQUEO_MINUTOS    = 15;

    // Límites de entrada
    private const MAX_LARGO_PASSWORD = 1024;
    private const MAX_LARGO_NOMBRE   = 100;

    // Roles válidos
    private const ROL_USUARIO   = 'usuario';
    private const ROL_ADMIN     = 'admin';
    private const ROLES_VALIDOS = [self::ROL_USUARIO, self::ROL_ADMIN];

    // Columnas que se pueden exponer (nunca la contraseña)
    private const COLUMNAS_PUBLICAS = 'id, nombre, email, rol';

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->tabla_usuarios = $this->wpdb->prefix . 'alm_usuarios';

        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            // Rechaza IDs de sesión que el servidor no generó (evita session fixation)
            ini_set('session.use_strict_mode', '1');
            // El ID de sesión solo viaja por cookie, nunca por URL
            ini_set('session.use_only_cookies', '1');

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

    /* ------------------------------------------------------------------
     * Utilidades internas
     * ------------------------------------------------------------------ */

    private function ipCliente(): string {
        // Solo REMOTE_ADDR: los headers X-Forwarded-For los puede falsificar cualquiera
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'desconocida';
    }

    /**
     * Hash de mentira para gastar el mismo tiempo cuando el email no existe
     * (evita que se pueda saber qué emails están registrados midiendo tiempos).
     */
    private function hashFalso(): string {
        static $hash = null;
        if ($hash === null) {
            $hash = wp_hash_password('az-hash-falso-' . wp_generate_password(12, false));
        }
        return $hash;
    }

    private function esAdminActual(): bool {
        return $this->rolActual() === self::ROL_ADMIN;
    }

    /**
     * Usuario sin la contraseña, sin chequeos de sesión ni de rol (uso interno).
     */
    private function buscarUsuario(int $id): ?array {
        $fila = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT " . self::COLUMNAS_PUBLICAS . " FROM {$this->tabla_usuarios} WHERE id = %d",
                $id
            ),
            ARRAY_A
        );
        return $fila ?: null;
    }

    /* ------------------------------------------------------------------
     * Registro, login y sesión
     * ------------------------------------------------------------------ */

    public function registrar(string $nombre, string $email, string $password, string $rol = 'usuario'): string {
        $nombre = sanitize_text_field($nombre);
        $email  = sanitize_email($email);

        if ($nombre === '' || mb_strlen($nombre) > self::MAX_LARGO_NOMBRE) {
            return "El nombre es obligatorio y no puede superar los " . self::MAX_LARGO_NOMBRE . " caracteres.";
        }

        if (!is_email($email)) {
            return "El email no es válido.";
        }

        if (strlen($password) < 8) {
            return "La contraseña debe tener al menos 8 caracteres.";
        }

        if (strlen($password) > self::MAX_LARGO_PASSWORD) {
            return "La contraseña es demasiado larga.";
        }

        if (!in_array($rol, self::ROLES_VALIDOS, true)) {
            return "Rol inválido.";
        }

        // Toda creación de cuentas requiere un administrador autenticado.
        if (!$this->esAdminActual()) {
            return "No autorizado para crear usuarios.";
        }

        $existe = (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tabla_usuarios} WHERE email = %s",
                $email
            )
        );

        if ($existe > 0) {
            return "El email ya está registrado.";
        }

        $hash = wp_hash_password($password);

        $insertado = $this->wpdb->insert(
            $this->tabla_usuarios,
            [
                'nombre'   => $nombre,
                'email'    => $email,
                'password' => $hash,
                'rol'      => $rol
            ],
            ['%s', '%s', '%s', '%s']
        );

        if ($insertado === false) {
            error_log('Alma-Zen registrar() error: ' . $this->wpdb->last_error);
            return "Hubo un error al registrar el usuario. Probá nuevamente.";
        }

        return "Usuario registrado correctamente.";
    }

    public function login(string $email, string $password): string {
        $email = sanitize_email($email);
        $ip    = $this->ipCliente();

        // Un contador por (email + IP) y otro general por IP:
        // así nadie puede bloquear a un usuario ajeno desde otra IP.
        $key_email = 'az_login_intentos_' . md5(strtolower($email) . '|' . $ip);
        $key_ip    = 'az_login_ip_' . md5($ip);

        $intentos_email = (int) get_transient($key_email);
        $intentos_ip    = (int) get_transient($key_ip);

        if ($intentos_email >= self::MAX_INTENTOS_LOGIN || $intentos_ip >= self::MAX_INTENTOS_IP) {
            return "Hablá con el Administrador, o esperá " . self::BLOQUEO_MINUTOS . " minutos y probá de nuevo.";
        }

        $usuario = null;
        if (is_email($email)) {
            $usuario = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT * FROM {$this->tabla_usuarios} WHERE email = %s",
                    $email
                ),
                ARRAY_A
            );
        }

        // Siempre se verifica un hash (real o falso) para que el tiempo de respuesta sea parecido
        $hash    = $usuario ? $usuario['password'] : $this->hashFalso();
        $largoOk = strlen($password) <= self::MAX_LARGO_PASSWORD;
        $passOk  = $largoOk && wp_check_password($password, $hash);

        if (!$usuario || !$passOk) {
            $bloqueo = self::BLOQUEO_MINUTOS * MINUTE_IN_SECONDS;
            set_transient($key_email, $intentos_email + 1, $bloqueo);
            set_transient($key_ip, $intentos_ip + 1, $bloqueo);
            return "Email o contraseña incorrectos.";
        }

        delete_transient($key_email);

        // Si WordPress actualizó su algoritmo de hash, se re-hashea al vuelo
        if (function_exists('wp_password_needs_rehash') && wp_password_needs_rehash($usuario['password'])) {
            $this->wpdb->update(
                $this->tabla_usuarios,
                ['password' => wp_hash_password($password)],
                ['id' => (int) $usuario['id']],
                ['%s'],
                ['%d']
            );
        }

        session_regenerate_id(true); // nueva sesión al autenticar, evita fixation

        $_SESSION['usuario_id']    = (int) $usuario['id'];
        $_SESSION['usuario']       = $usuario['nombre'];
        $_SESSION['usuario_rol']   = $usuario['rol'];
        $_SESSION['ultimo_acceso'] = time();
        $_SESSION['csrf_token']    = bin2hex(random_bytes(32));

        return "Login correcto.";
    }

    public function logout(): void {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    /**
     * Usuario logueado (sin la contraseña). Devuelve null si no hay sesión,
     * si venció por inactividad o si el usuario ya no existe.
     * Refresca el rol desde la base: un cambio de rol se aplica de inmediato.
     */
    public function usuarioActual(): ?array {
        if (empty($_SESSION['usuario_id'])) {
            return null;
        }

        if (!empty($_SESSION['ultimo_acceso']) && (time() - $_SESSION['ultimo_acceso']) > self::TIEMPO_INACTIVIDAD) {
            $this->logout();
            return null;
        }

        $fila = $this->buscarUsuario((int) $_SESSION['usuario_id']);

        if (!$fila) {
            // El usuario fue eliminado: se cierra la sesión que había quedado
            $this->logout();
            return null;
        }

        $_SESSION['ultimo_acceso'] = time();
        $_SESSION['usuario']       = $fila['nombre'];
        $_SESSION['usuario_rol']   = $fila['rol'];

        return $fila;
    }

    /**
     * Rol real del usuario logueado (leído de la base, no de la sesión).
     */
    public function rolActual(): ?string {
        $usuario = $this->usuarioActual();
        return $usuario['rol'] ?? null;
    }

    /**
     * Libera el bloqueo de la sesión. Llamarlo en handlers de solo lectura
     * (como el buscador en vivo) para que las requests no se encolen entre sí.
     * No usar antes de logout().
     */
    public function liberarSesion(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }

    /* ------------------------------------------------------------------
     * CSRF propio (los nonces de WP no distinguen entre usuarios de esta app)
     * ------------------------------------------------------------------ */

    public function tokenCsrf(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public function validarCsrf(string $token): bool {
        return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Datos de un usuario por email, SIN la contraseña.
     */
    public function usuarioPorEmail(string $email): ?array {
        $fila = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT " . self::COLUMNAS_PUBLICAS . " FROM {$this->tabla_usuarios} WHERE email = %s",
                sanitize_email($email)
            ),
            ARRAY_A
        );
        return $fila ?: null;
    }

    /* ------------------------------------------------------------------
     * Gestión de usuarios (panel del administrador)
     * Todas exigen que quien llama sea un admin logueado.
     * ------------------------------------------------------------------ */

    /**
     * Lista de usuarios para el panel del admin. No trae la contraseña.
     */
    public function listarUsuarios(): array {
        if (!$this->esAdminActual()) {
            return [];
        }

        return $this->wpdb->get_results(
            "SELECT " . self::COLUMNAS_PUBLICAS . " FROM {$this->tabla_usuarios} ORDER BY nombre ASC",
            ARRAY_A
        );
    }

    /**
     * Datos de un usuario puntual, sin la contraseña.
     */
    public function obtenerUsuario(int $id): ?array {
        if (!$this->esAdminActual()) {
            return null;
        }

        return $this->buscarUsuario($id);
    }

    public function contarAdmins(): int {
        return (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tabla_usuarios} WHERE rol = %s",
                self::ROL_ADMIN
            )
        );
    }

    /**
     * El admin actualiza el email y, opcionalmente, la contraseña de un usuario.
     * Dejar $nuevaPassword vacío mantiene la contraseña actual.
     */
    public function actualizarUsuario(int $id, string $nuevoEmail, string $nuevaPassword = ''): string {
        if (!$this->esAdminActual()) {
            return "No autorizado.";
        }

        if (!$this->buscarUsuario($id)) {
            return "El usuario no existe.";
        }

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
            if (strlen($nuevaPassword) > self::MAX_LARGO_PASSWORD) {
                return "La contraseña es demasiado larga.";
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
        if (!$this->esAdminActual()) {
            return "No autorizado.";
        }

        // El solicitante tiene que ser quien está logueado, no un id que llegue de afuera
        if ($solicitado_por !== (int) ($_SESSION['usuario_id'] ?? 0)) {
            return "No autorizado.";
        }

        if ($id === $solicitado_por) {
            return "No podés eliminar tu propia cuenta.";
        }

        $usuario = $this->buscarUsuario($id);
        if (!$usuario) {
            return "El usuario no existe.";
        }

        if ($usuario['rol'] === self::ROL_ADMIN && $this->contarAdmins() <= 1) {
            return "No se puede eliminar al único administrador.";
        }

        $eliminado = $this->wpdb->delete($this->tabla_usuarios, ['id' => $id], ['%d']);

        return $eliminado ? "Usuario eliminado correctamente." : "No se pudo eliminar el usuario.";
    }
}