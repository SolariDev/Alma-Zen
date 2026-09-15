<?php
class Productos {

    /** @var mixed */
    private $wpdb;

    /** @var string */
    private $tabla;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->tabla = $this->wpdb->prefix . 'alm_productos';
    }

    public function obtenerTodos(): array {
        return $this->wpdb->get_results("SELECT * FROM {$this->tabla} ORDER BY nombre ASC", ARRAY_A);
    }

    public function buscarPorNombre(string  $nombre): array {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->tabla} WHERE nombre = BINARY %s ORDER BY nombre ASC",
                $nombre
            ),
            ARRAY_A
        );
    }
}