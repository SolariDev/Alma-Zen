# Alma-Zen

**Alma-Zen** es un sistema de inventario modular para almacenes, desarrollado como plugin de **WordPress**.  
Su objetivo es ofrecer una gestión clara y escalable de productos, con una interfaz pensada tanto para administradores como para usuarios.

## 🚀 Características principales

- **Roles de usuario**:
  - **Administrador**: CRUD completo de productos (crear, editar, eliminar).
  - **Usuario**: acceso de lectura a categorías y precios.

- **Gestión de productos**:
  - Categorización por rubros y subcategorías.
  - Precios configurables con unidad de medida controlada:
    - `unidad`, `kilo`, `litro`, `pack`.
  - Registro automático de fecha de última actualización.

- **Interfaz modular**:
  - Panel de usuario y panel de administración diferenciados.
  - Vistas dinámicas con AJAX para subcategorías y productos.
  - Estilos consistentes y animaciones modernas.

- **Arquitectura escalable**:
  - Separación clara entre frontend (`views/`) y backend (`includes/`).
  - Consultas centralizadas en clases PHP.
  - CSS modular (`estilos.css`, `inventario.css`, `animaciones.css`).

## 📦 Estado del proyecto

Este proyecto se encuentra en **fase de desarrollo inicial**.  
