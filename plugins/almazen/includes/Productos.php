<?php
class Productos {

    /** @var mixed */
    private $wpdb;

    /** @var string */
    private $tabla;

    /** @var string */
    private $tabla_cambios;

    private const DB_VERSION = '1';

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->tabla = $this->wpdb->prefix . 'alm_productos';
        $this->tabla_cambios = $this->wpdb->prefix . 'alm_productos_cambios';
        $this->asegurarTablaCambios();
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
     * Deja los datos editables de un producto en un formato fijo,
     * para poder compararlos y guardarlos siempre igual.
     */
    private function normalizar(array $d): array {
        return [
            'nombre'   => (string) ($d['nombre'] ?? ''),
            'marca'    => (string) ($d['marca'] ?? ''),
            'empaque'  => (string) ($d['empaque'] ?? ''),
            'cantidad' => (float)  ($d['cantidad'] ?? 0),
            'unidad'   => (string) ($d['unidad'] ?? ''),
            'precio'   => (int)    ($d['precio'] ?? 0),
        ];
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
     * Cambios pendientes de aprobación
     * ------------------------------------------------------------------ */

    /**
     * Un usuario propone cambios sobre un producto.
     * Devuelve: 'ok' | 'sin_cambios' | 'ya_pendiente' | 'error'
     */
    public function solicitarCambio(int $producto_id, array $nuevos, int $usuario_id): string {
        $actual = $this->obtener($producto_id);
        if (!$actual) {
            return 'error';
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
                'datos_anteriores' => wp_json_encode($anteriores),
                'datos_nuevos'     => wp_json_encode($nuevos),
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
        $usuarios = $this->wpdb->prefix . 'alm_usuarios';

        return $this->wpdb->get_results(
            "SELECT c.*, u.nombre AS solicitante, p.nombre AS producto_nombre
               FROM {$this->tabla_cambios} c
               LEFT JOIN {$usuarios} u ON u.id = c.solicitado_por
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
        $cambio = $this->obtenerCambio($cambio_id);
        if (!$cambio || $cambio['estado'] !== 'pendiente') {
            return false;
        }

        // Regla del README: quien pidió el cambio no lo aprueba
        if ((int) $cambio['solicitado_por'] === $admin_id) {
            return false;
        }

        if (!$this->obtener((int) $cambio['producto_id'])) {
            return false;
        }

        $n = $this->normalizar(json_decode($cambio['datos_nuevos'], true) ?: []);

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
            ['%s', '%s', '%s', '%s', '%s', '%d', '%s'],
            ['%d']
        );

        if ($ok === false) {
            return false;
        }

        return $this->resolver($cambio_id, 'aprobado', $admin_id);
    }

    public function rechazarCambio(int $cambio_id, int $admin_id): bool {
        $cambio = $this->obtenerCambio($cambio_id);
        if (!$cambio || $cambio['estado'] !== 'pendiente') {
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