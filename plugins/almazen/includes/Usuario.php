<?php
class Usuario {

    /** @var mixed */
    private $wpdb;

    /** @var string */
    private string $tabla_productos;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->tabla_productos = $this->wpdb->prefix . 'alm_productos';
    }

    /**
     * Obtener lista de productos por categoría
     */
    public function obtenerProductosPorCategoria(string $categoria): array {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->tabla_productos} WHERE categoria = %s ORDER BY fecha_actualizacion DESC",
            sanitize_text_field($categoria)
        );
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * Obtener todos los productos
     */
    public function obtenerTodosLosProductos(): array {
        $sql = "SELECT * FROM {$this->tabla_productos} ORDER BY fecha_actualizacion DESC";
        return $this->wpdb->get_results($sql, ARRAY_A);
    }
}  