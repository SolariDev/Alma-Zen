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
}