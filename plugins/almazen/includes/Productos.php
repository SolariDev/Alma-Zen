<?php
class Productos {

    /** @var mixed */
    private $wpdb;

    /** @var string */
    private $tabla;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->tabla = $this->wpdb->prefix . 'az_productos';
    }

    public function obtenerPorSubcategoria(string $subcat): array {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT nombre, precio, fecha_actualizacion FROM {$this->tabla} WHERE subcategoria = %s ORDER BY nombre ASC",
                $subcat
            ),
            ARRAY_A
        );
    }
}