<?php
class Administrador {

    /** @var mixed */
    private $wpdb;

    /** @var string */
    private $tabla_productos;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->tabla_productos = $this->wpdb->prefix . 'alm_productos';
    }

    /**
     * Crear un nuevo producto
     */
    public function agregarProducto(string $nombre, float $precio, string $unidad, string $categoria) {
        return $this->wpdb->insert(
            $this->tabla_productos,
            array(
                'nombre' => sanitize_text_field($nombre),
                'precio' => floatval($precio),
                'unidad' => sanitize_text_field($unidad),
                'categoria' => sanitize_text_field($categoria),
                'fecha_actualizacion' => current_time('mysql')
            ),
            array('%s','%f','%s','%s','%s')
        );
    }

    /**
     * Editar producto existente
     */
    public function editarProducto(int $id, string $nombre, float $precio, string $unidad, string $categoria) {
        return $this->wpdb->update(
            $this->tabla_productos,
            array(
                'nombre' => sanitize_text_field($nombre),
                'precio' => floatval($precio),
                'unidad' => sanitize_text_field($unidad),
                'categoria' => sanitize_text_field($categoria),
                'fecha_actualizacion' => current_time('mysql')
            ),
            array('id' => intval($id)),
            array('%s','%f','%s','%s','%s'),
            array('%d')
        );
    }

    /**
     * Eliminar producto
     */
    public function eliminarProducto(int $id) {
        return $this->wpdb->delete(
            $this->tabla_productos,
            array('id' => intval($id)),
            array('%d')
        );
    }

    /**
     * Listar productos (opcional por categoría)
     */
    public function listarProductos($categoria = null) {
        if ($categoria) {
            $sql = $this->wpdb->prepare(
                "SELECT * FROM {$this->tabla_productos} WHERE categoria = %s ORDER BY fecha_actualizacion DESC",
                sanitize_text_field($categoria)
            );
        } else {
            $sql = "SELECT * FROM {$this->tabla_productos} ORDER BY fecha_actualizacion DESC";
        }
        return $this->wpdb->get_results($sql);
    }
}