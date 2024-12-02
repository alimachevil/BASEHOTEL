<!-- pagina_contenido.php -->
<?php
// Definir el contenido dinámico de la página
$content = '
    <div class="content">
        <h2>Bienvenido al Panel de Control</h2>
        <p>Selecciona una de las opciones del menú a la izquierda para continuar.</p>
    </div>
';

// Definir el CSS específico para esta página
$additionalCSS = '
    <style>
    </style>
';

// Definir el JavaScript específico para esta página
$additionalJS = '
    <script>
    </script>
';

// Incluir la plantilla
include('template.php');
?>