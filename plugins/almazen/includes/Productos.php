<?php
if (!defined('ABSPATH')) {
    exit;
}

class Productos {

    /** @var mixed */
    private $wpdb;

    /** @var string */
    private $tabla;

    /** @var string */
    private $tabla_cambios;

    /** @var string */
    private $tabla_usuarios;

    /** @var string Último mensaje de error, para mostrarlo en las vistas */
    private $ultimoError = '';

    private const DB_VERSION = '1';

    // Valores permitidos para unidad y empaque (se aceptan tal cual llegan de los formularios)
    public const UNIDADES = ['lt', 'ml', 'cc', 'kg', 'gr', 'unidad'];
    public const EMPAQUES = ['', 'pack', 'pack4', 'pack6', 'pack12', 'bolsa', 'caja', 'lata', 'petaca', 'tarrina'];

    // Límites (coinciden con los tamaños de columna de la base)
    private const MAX_TEXTO    = 100;
    private const MAX_CANTIDAD = 9999.99; // DECIMAL(6,2)
    private const MAX_PRECIO   = 9999999;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->tabla = $this->wpdb->prefix . 'alm_productos';
        $this->tabla_cambios = $this->wpdb->prefix . 'alm_productos_cambios';
        $this->tabla_usuarios = $this->wpdb->prefix . 'alm_usuarios';
        $this->asegurarTablaCambios();
    }

    /**
     * Último mensaje de error de validación o de operación (vacío si no hubo).
     */
    public function ultimoError(): string {
        return $this->ultimoError;
    }

    /**
     * Crea la tabla de cambios pendientes si todavía no existe.
     * Solo marca la versión como instalada si la tabla realmente quedó creada,
     * así que si algo falla, lo vuelve a intentar en la siguiente carga.
     */
    private function asegurarTablaCambios(): void {
        if (get_option('az_cambios_db_version') === self::DB_VERSION) {
            return;
        }

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $this->wpdb->get_charset_collate();

        dbDelta("CREATE TABLE {$this->tabla_cambios} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            producto_id BIGINT UNSIGNED NOT NULL,
            solicitado_por BIGINT UNSIGNED NOT NULL,
            datos_anteriores LONGTEXT NOT NULL,
            datos_nuevos LONGTEXT NOT NULL,
            estado VARCHAR(20) NOT NULL DEFAULT 'pendiente',
            fecha_solicitud DATETIME NOT NULL,
            resuelto_por BIGINT UNSIGNED NULL,
            fecha_resolucion DATETIME NULL,
            PRIMARY KEY  (id),
            KEY estado (estado),
            KEY producto_id (producto_id)
        ) $charset;");

        $existe = $this->wpdb->get_var(
            $this->wpdb->prepare(
                'SHOW TABLES LIKE %s',
                $this->wpdb->esc_like($this->tabla_cambios)
            )
        );

        if ($existe === $this->tabla_cambios) {
            update_option('az_cambios_db_version', self::DB_VERSION);
        } else {
            error_log('Alma-Zen: no se pudo crear la tabla ' . $this->tabla_cambios . ' - ' . $this->wpdb->last_error);
        }
    }

    /**
     * Deja los datos editables de un producto en un formato fijo (y saneados),
     * para poder compararlos y guardarlos siempre igual.
     */
    private function normalizar(array $d): array {
        return [
            'nombre'   => sanitize_text_field((string) ($d['nombre'] ?? '')),
            'marca'    => sanitize_text_field((string) ($d['marca'] ?? '')),
            'empaque'  => sanitize_text_field((string) ($d['empaque'] ?? '')),
            'cantidad' => round((float) ($d['cantidad'] ?? 0), 2),
            'unidad'   => sanitize_text_field((string) ($d['unidad'] ?? '')),
            'precio'   => (int) ($d['precio'] ?? 0),
        ];
    }

    /**
     * Valida los datos de un producto. Devuelve true si están bien;
     * si no, deja el motivo en ultimoError().
     */
    public function validar(array $datos): bool {
        $this->ultimoError = '';
        $n = $this->normalizar($datos);

        if ($n['nombre'] === '') {
            $this->ultimoError = 'El nombre es obligatorio.';
        } elseif (!is_numeric($datos['cantidad'] ?? null) || !is_numeric($datos['precio'] ?? null)) {
            $this->ultimoError = 'La cantidad y el precio deben ser números.';
        } elseif ((float) $datos['precio'] != (int) $datos['precio']) {
            $this->ultimoError = 'El precio debe ser un número entero.';
        } elseif (mb_strlen($n['nombre']) > self::MAX_TEXTO || mb_strlen($n['marca']) > self::MAX_TEXTO) {
            $this->ultimoError = 'El nombre y la marca no pueden superar los ' . self::MAX_TEXTO . ' caracteres.';
        } elseif (!in_array($n['empaque'], self::EMPAQUES, true)) {
            $this->ultimoError = 'El empaque elegido no es válido.';
        } elseif (!in_array($n['unidad'], self::UNIDADES, true)) {
            $this->ultimoError = 'La unidad de medida no es válida.';
        } elseif ($n['cantidad'] < 0 || $n['cantidad'] > self::MAX_CANTIDAD) {
            $this->ultimoError = 'La cantidad no puede ser negativa ni superar 9999,99.';
        } elseif ($n['precio'] < 0 || $n['precio'] > self::MAX_PRECIO) {
            $this->ultimoError = 'El precio debe estar entre 0 y 9.999.999.';
        }

        return $this->ultimoError === '';
    }

    private function usuarioExiste(int $id): bool {
        return (int) $this->wpdb->get_var(
            $this->wpdb->prepare("SELECT COUNT(*) FROM {$this->tabla_usuarios} WHERE id = %d", $id)
        ) > 0;
    }

    private function esAdmin(int $id): bool {
        return (int) $this->wpdb->get_var(
            $this->wpdb->prepare("SELECT COUNT(*) FROM {$this->tabla_usuarios} WHERE id = %d AND rol = 'admin'", $id)
        ) > 0;
    }

    /* ------------------------------------------------------------------
     * Consulta de productos
     * ------------------------------------------------------------------ */

    public function obtenerTodos(): array {
        return $this->wpdb->get_results("SELECT * FROM {$this->tabla} ORDER BY nombre ASC", ARRAY_A);
    }

    public function obtener(int $id): ?array {
        $fila = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$this->tabla} WHERE id = %d", $id),
            ARRAY_A
        );
        return $fila ?: null;
    }

    /**
     * Buscar productos por coincidencia parcial, insensible a mayúsculas minúsculas.
     **/
    public function buscarPorNombre(string $nombre, int $limite = 30): array {
        $termino = trim($nombre);

        if ($termino === '') {
            return [];
        }

        $limite = max(1, min(100, $limite));
        $like = '%' . $this->wpdb->esc_like($termino) . '%';

        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->tabla} WHERE LOWER(nombre) LIKE LOWER(%s)
                  OR LOWER(marca) LIKE LOWER(%s)
                  OR LOWER(empaque) LIKE LOWER(%s)
                  OR LOWER(unidad) LIKE LOWER(%s)
                ORDER BY nombre ASC LIMIT %d",
                $like, $like, $like, $like,
                $limite
            ),
            ARRAY_A
        );
    }

    /* ------------------------------------------------------------------
     * Alta y edición directa
     * ------------------------------------------------------------------ */

    /**
     * Registra un producto nuevo (cualquier usuario del sistema puede hacerlo,
     * sin aprobación). Devuelve true si se guardó; si no, ver ultimoError().
     */
    public function crear(array $datos, int $usuario_id): bool {
        if (!$this->usuarioExiste($usuario_id)) {
            $this->ultimoError = 'Usuario no autorizado.';
            return false;
        }

        if (!$this->validar($datos)) {
            return false;
        }

        $n = $this->normalizar($datos);

        $ok = $this->wpdb->insert(
            $this->tabla,
            [
                'nombre'              => $n['nombre'],
                'marca'               => $n['marca'],
                'precio'              => $n['precio'],
                'cantidad'            => $n['cantidad'],
                'unidad'              => $n['unidad'],
                'empaque'             => $n['empaque'],
                'fecha_actualizacion' => current_time('mysql'),
            ],
            ['%s', '%s', '%d', '%f', '%s', '%s', '%s']
        );

        if ($ok === false) {
            error_log('Alma-Zen Productos::crear() error: ' . $this->wpdb->last_error);
            $this->ultimoError = 'No se pudo guardar el producto. Probá nuevamente.';
            return false;
        }

        return true;
    }

    /**
     * Edición directa de un producto. Solo para administradores
     * (los usuarios comunes proponen cambios con solicitarCambio()).
     */
    public function actualizar(int $id, array $datos, int $admin_id): bool {
        if (!$this->esAdmin($admin_id)) {
            $this->ultimoError = 'No autorizado.';
            return false;
        }

        if (!$this->obtener($id)) {
            $this->ultimoError = 'El producto no existe.';
            return false;
        }

        if (!$this->validar($datos)) {
            return false;
        }

        $n = $this->normalizar($datos);

        $ok = $this->wpdb->update(
            $this->tabla,
            [
                'nombre'              => $n['nombre'],
                'marca'               => $n['marca'],
                'empaque'             => $n['empaque'],
                'cantidad'            => $n['cantidad'],
                'unidad'              => $n['unidad'],
                'precio'              => $n['precio'],
                'fecha_actualizacion' => current_time('mysql'),
            ],
            ['id' => $id],
            ['%s', '%s', '%s', '%f', '%s', '%d', '%s'],
            ['%d']
        );

        if ($ok === false) {
            error_log('Alma-Zen Productos::actualizar() error: ' . $this->wpdb->last_error);
            $this->ultimoError = 'No se pudo actualizar el producto.';
            return false;
        }

        return true;
    }

    /**
     * Elimina un producto. Solo administradores.
     * Los cambios pendientes de ese producto quedan descartados.
     */
    public function eliminar(int $id, int $admin_id): bool {
        $this->ultimoError = '';

        if (!$this->esAdmin($admin_id)) {
            $this->ultimoError = 'No autorizado.';
            return false;
        }

        if (!$this->obtener($id)) {
            $this->ultimoError = 'El producto no existe.';
            return false;
        }

        $this->wpdb->query('START TRANSACTION');

        $descartados = $this->wpdb->update(
            $this->tabla_cambios,
            [
                'estado'           => 'rechazado',
                'resuelto_por'     => $admin_id,
                'fecha_resolucion' => current_time('mysql'),
            ],
            ['producto_id' => $id, 'estado' => 'pendiente'],
            ['%s', '%d', '%s'],
            ['%d', '%s']
        );

        $eliminado = $this->wpdb->delete($this->tabla, ['id' => $id], ['%d']);

        if ($descartados === false || !$eliminado) {
            $this->wpdb->query('ROLLBACK');
            error_log('Alma-Zen Productos::eliminar() error: ' . $this->wpdb->last_error);
            $this->ultimoError = 'No se pudo eliminar el producto.';
            return false;
        }

        $this->wpdb->query('COMMIT');
        return true;
    }

    /* ------------------------------------------------------------------
     * Cambios pendientes de aprobación
     * ------------------------------------------------------------------ */

    /**
     * Un usuario propone cambios sobre un producto.
     * Devuelve: 'ok' | 'sin_cambios' | 'ya_pendiente' | 'invalido' | 'error'
     * Con 'invalido', el motivo queda en ultimoError().
     */
    public function solicitarCambio(int $producto_id, array $nuevos, int $usuario_id): string {
        $this->ultimoError = '';

        if (!$this->usuarioExiste($usuario_id)) {
            return 'error';
        }

        $actual = $this->obtener($producto_id);
        if (!$actual) {
            return 'error';
        }

        if (!$this->validar($nuevos)) {
            return 'invalido';
        }

        $anteriores = $this->normalizar($actual);
        $nuevos     = $this->normalizar($nuevos);

        if ($anteriores === $nuevos) {
            return 'sin_cambios';
        }

        $pendiente = (int) $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_cambios} WHERE producto_id = %d AND estado = 'pendiente'",
            $producto_id
        ));
        if ($pendiente > 0) {
            return 'ya_pendiente';
        }

        $ok = $this->wpdb->insert(
            $this->tabla_cambios,
            [
                'producto_id'      => $producto_id,
                'solicitado_por'   => $usuario_id,
                'datos_anteriores' => wp_json_encode($anteriores, JSON_UNESCAPED_UNICODE),
                'datos_nuevos'     => wp_json_encode($nuevos, JSON_UNESCAPED_UNICODE),
                'estado'           => 'pendiente',
                'fecha_solicitud'  => current_time('mysql'),
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s']
        );

        return $ok === false ? 'error' : 'ok';
    }

    public function contarPendientes(): int {
        return (int) $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->tabla_cambios} WHERE estado = 'pendiente'"
        );
    }

    public function cambiosPendientes(): array {
        return $this->wpdb->get_results(
            "SELECT c.*, u.nombre AS solicitante, p.nombre AS producto_nombre
               FROM {$this->tabla_cambios} c
               LEFT JOIN {$this->tabla_usuarios} u ON u.id = c.solicitado_por
               LEFT JOIN {$this->tabla} p ON p.id = c.producto_id
              WHERE c.estado = 'pendiente'
              ORDER BY c.fecha_solicitud ASC",
            ARRAY_A
        );
    }

    private function obtenerCambio(int $id): ?array {
        $fila = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$this->tabla_cambios} WHERE id = %d", $id),
            ARRAY_A
        );
        return $fila ?: null;
    }

    public function aprobarCambio(int $cambio_id, int $admin_id): bool {
        $this->ultimoError = '';

        if (!$this->esAdmin($admin_id)) {
            $this->ultimoError = 'No autorizado.';
            return false;
        }

        $cambio = $this->obtenerCambio($cambio_id);
        if (!$cambio || $cambio['estado'] !== 'pendiente') {
            $this->ultimoError = 'Ese cambio ya no está pendiente.';
            return false;
        }

        // Regla del README: quien pidió el cambio no lo aprueba
        if ((int) $cambio['solicitado_por'] === $admin_id) {
            $this->ultimoError = 'No podés aprobar un cambio que pediste vos.';
            return false;
        }

        $producto = $this->obtener((int) $cambio['producto_id']);
        if (!$producto) {
            $this->ultimoError = 'El producto ya no existe.';
            return false;
        }

        $anteriores = json_decode($cambio['datos_anteriores'], true);
        $nuevos     = json_decode($cambio['datos_nuevos'], true);
        if (!is_array($anteriores) || !is_array($nuevos)) {
            $this->ultimoError = 'Los datos del cambio están dañados.';
            return false;
        }

        // Si el producto cambió después de que se pidió esta modificación,
        // aplicarla pisaría esos cambios sin que nadie lo note.
        if ($this->normalizar($producto) !== $this->normalizar($anteriores)) {
            $this->ultimoError = 'El producto cambió desde que se pidió esta modificación. Descartala y pedí una nueva.';
            return false;
        }

        if (!$this->validar($nuevos)) {
            return false;
        }

        $n = $this->normalizar($nuevos);

        // Todo o nada: se "reclama" el cambio y se aplica dentro de una transacción,
        // así dos aprobaciones simultáneas no pueden aplicarlo dos veces.
        $this->wpdb->query('START TRANSACTION');

        if (!$this->resolver($cambio_id, 'aprobado', $admin_id)) {
            $this->wpdb->query('ROLLBACK');
            $this->ultimoError = 'Ese cambio ya fue resuelto.';
            return false;
        }

        $ok = $this->wpdb->update(
            $this->tabla,
            [
                'nombre'              => $n['nombre'],
                'marca'               => $n['marca'],
                'empaque'             => $n['empaque'],
                'cantidad'            => $n['cantidad'],
                'unidad'              => $n['unidad'],
                'precio'              => $n['precio'],
                'fecha_actualizacion' => current_time('mysql'),
            ],
            ['id' => (int) $cambio['producto_id']],
            ['%s', '%s', '%s', '%f', '%s', '%d', '%s'],
            ['%d']
        );

        if ($ok === false) {
            $this->wpdb->query('ROLLBACK');
            error_log('Alma-Zen aprobarCambio() error: ' . $this->wpdb->last_error);
            $this->ultimoError = 'No se pudo aplicar el cambio al producto.';
            return false;
        }

        $this->wpdb->query('COMMIT');
        return true;
    }

    public function rechazarCambio(int $cambio_id, int $admin_id): bool {
        $this->ultimoError = '';

        if (!$this->esAdmin($admin_id)) {
            $this->ultimoError = 'No autorizado.';
            return false;
        }

        $cambio = $this->obtenerCambio($cambio_id);
        if (!$cambio || $cambio['estado'] !== 'pendiente') {
            $this->ultimoError = 'Ese cambio ya no está pendiente.';
            return false;
        }

        return $this->resolver($cambio_id, 'rechazado', $admin_id);
    }

    private function resolver(int $cambio_id, string $estado, int $admin_id): bool {
        $r = $this->wpdb->update(
            $this->tabla_cambios,
            [
                'estado'           => $estado,
                'resuelto_por'     => $admin_id,
                'fecha_resolucion' => current_time('mysql'),
            ],
            ['id' => $cambio_id, 'estado' => 'pendiente'],
            ['%s', '%d', '%s'],
            ['%d', '%s']
        );

        return $r !== false && $r > 0;
    }
}