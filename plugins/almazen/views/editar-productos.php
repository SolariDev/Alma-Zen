<?php
// Formulario simple para CRUD de productos (solo admin)
echo '<form method="post" class="az-form-producto">';
echo '<input type="text" name="nombre" placeholder="Nombre del producto" required>';
echo '<input type="number" step="0.01" name="precio" placeholder="Precio" required>';
echo '<input type="text" name="unidad" placeholder="Unidad (ej: kg, litro)" required>';
echo '<input type="text" name="categoria" placeholder="Categoría" required>';
echo '<button type="submit">Guardar producto</button>';
echo '</form>';
