<?php
// Inicia la sesión
session_start();

// Función para formatear la fecha
function formatearFecha($fecha) {
    setlocale(LC_TIME, 'es_ES.UTF-8');
    return strftime("%B %d", strtotime($fecha));
}

// Verifica si las fechas están guardadas en la sesión
$check_in = isset($_SESSION['check_in']) ? $_SESSION['check_in'] : null;
$check_out = isset($_SESSION['check_out']) ? $_SESSION['check_out'] : null;

// Formatear fechas
$fecha_estadia = "";
if ($check_in && $check_out) {
    $fecha_estadia = "Desde " . formatearFecha($check_in) . " hasta " . formatearFecha($check_out);
}

// Manejo del formulario de habitaciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Guarda la cantidad de adultos y niños por habitación en la sesión
    $_SESSION['habitaciones'] = count($_POST['adultos']); // Número total de habitaciones
    $_SESSION['adultos'] = $_POST['adultos']; // Array con la cantidad de adultos por habitación
    $_SESSION['ninos'] = $_POST['ninos']; // Array con la cantidad de niños por habitación
    
    // Redirige a gestionh.php después de guardar la información en la sesión
    header('Location: gestionh.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserva de Habitaciones</title>
    <script>
        // JavaScript para añadir habitaciones dinámicamente
        function agregarHabitacion() {
            // Contenedor donde se añadirán las habitaciones
            const contenedor = document.getElementById('habitaciones');
            
            // Índice de la nueva habitación
            const index = contenedor.children.length + 1;
            
            // Crear un div para la nueva habitación
            const nuevaHabitacion = document.createElement('div');
            nuevaHabitacion.className = 'habitacion';
            nuevaHabitacion.innerHTML = `
                <h3>Habitación ${index}</h3>
                <label for="adultos_${index}">Cantidad de Adultos:</label>
                <input type="number" id="adultos_${index}" name="adultos[]" min="1" required>
                <br><br>
                
                <label for="ninos_${index}">Cantidad de Niños:</label>
                <input type="number" id="ninos_${index}" name="ninos[]" min="0" required>
                <br><br>
            `;
            
            // Añadir la nueva habitación al contenedor
            contenedor.appendChild(nuevaHabitacion);
        }
    </script>
</head>
<body>
    <h1>Reserva de Habitaciones</h1>

    <?php if ($fecha_estadia): ?>
        <h2>Estadía: <?php echo htmlspecialchars($fecha_estadia); ?></h2>
    <?php else: ?>
        <p>No se han seleccionado fechas.</p>
    <?php endif; ?>

    <form action="hyh.php" method="POST">
        <div id="habitaciones">
            <!-- Habitacion Inicial -->
            <div class="habitacion">
                <h3>Habitación 1</h3>
                <label for="adultos_1">Cantidad de Adultos:</label>
                <input type="number" id="adultos_1" name="adultos[]" min="1" required>
                <br><br>
                
                <label for="ninos_1">Cantidad de Niños:</label>
                <input type="number" id="ninos_1" name="ninos[]" min="0" required>
                <br><br>
            </div>
        </div>
        
        <!-- Botón para añadir más habitaciones -->
        <button type="button" onclick="agregarHabitacion()">Añadir Habitación</button>
        <br><br>
        
        <!-- Botón para enviar la información -->
        <button type="submit">Actualizar</button>
    </form>
</body>
</html>
