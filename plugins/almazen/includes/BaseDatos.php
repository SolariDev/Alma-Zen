<?php
class BaseDatos {

    /** @var mixed $wpdb */
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    public function crearTablas() {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $this->wpdb->get_charset_collate();

        // Tabla usuarios
        $tabla_usuarios = $this->wpdb->prefix . 'alm_usuarios';
        $sql_usuarios = "CREATE TABLE $tabla_usuarios (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            password VARCHAR(255) NOT NULL,
            rol ENUM('admin','usuario') NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // Tabla productos
        $tabla_productos = $this->wpdb->prefix . 'alm_productos';
        $sql_productos = "CREATE TABLE $tabla_productos (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre VARCHAR(100) NOT NULL,
            precio INT UNSIGNED NOT NULL DEFAULT 0,
            cantidad DECIMAL(6,2) NOT NULL, 
            unidad ENUM('ml','lt','gr','kg','unidad') NOT NULL DEFAULT 'unidad',
            empaque ENUM('','pack6','pack12','bolsa','tarrina','otro') DEFAULT '',
            marca VARCHAR(100) DEFAULT '',
            proveedor VARCHAR(100) DEFAULT '',
            categoria VARCHAR(100) DEFAULT '',
            fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX idx_nombre (nombre)
        ) $charset_collate;";

        dbDelta($sql_usuarios);
        dbDelta($sql_productos);
    }
}