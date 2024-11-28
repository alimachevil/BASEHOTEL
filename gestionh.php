<?php
// Inicia la sesión
session_start();

// Datos de conexión a la base de datos
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'hotel_db';

// Conexión a la base de datos MySQL
$conn = new mysqli($host, $user, $pass, $dbname);

// Verificar la conexión
if ($conn->connect_error) {
    die("Error en la conexión a la base de datos: " . $conn->connect_error);
}

// Verifica si las fechas y los datos de las habitaciones están en la sesión
if (!isset($_SESSION['check_in'], $_SESSION['check_out'], $_SESSION['habitaciones'], $_SESSION['adultos'], $_SESSION['ninos'])) {
    header('Location: index.php'); // Redirigir al inicio si falta información
    exit();
}

// Variables de sesión
$check_in = $_SESSION['check_in'];
$check_out = $_SESSION['check_out'];
$habitaciones = $_SESSION['habitaciones'];
$adultos = $_SESSION['adultos'];
$ninos = $_SESSION['ninos'];

// Procesar el formulario de selección de habitaciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['cuartos']) || empty($_POST['cuartos'])) {
        echo "<p style='color:red;'>Debe seleccionar al menos una habitación.</p>";
    } else {
        // Guardar habitaciones seleccionadas en sesión
        $_SESSION['cuartos_seleccionados'] = [];
        foreach ($_POST['cuartos'] as $cuarto_id) {
            $_SESSION['cuartos_seleccionados'][] = $cuarto_id;
        }

        // Redirigir a la página de pago
        header('Location: pagoh.php');
        exit();
    }
}

// Consulta para obtener los cuartos disponibles
$cuartos_disponibles = [];
for ($i = 0; $i < $habitaciones; $i++) {
    $capacidad_adultos = $adultos[$i];
    $capacidad_ninos = $ninos[$i];

    $query = "
        SELECT c.*
        FROM cuartos c
        WHERE c.estado = 'Disponible'
        AND c.capacidad_adultos >= $capacidad_adultos
        AND c.capacidad_niños >= $capacidad_ninos
        AND c.id_cuarto NOT IN (
            SELECT rc.id_cuarto
            FROM reservaporcuartos rc
            JOIN reservas r ON rc.id_reserva = r.id_reserva
            WHERE r.fecha_checkin < '$check_out' AND r.fecha_checkout > '$check_in'
        )
    ";
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $cuartos_disponibles[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Habitaciones</title>
</head>
<body>
    <h1>Habitaciones Disponibles</h1>

    <?php if (!empty($cuartos_disponibles)): ?>
        <form action="gestionh.php" method="POST">
            <?php foreach ($cuartos_disponibles as $cuarto): ?>
                <div>
                    <h2>Habitación #<?php echo htmlspecialchars($cuarto['numero']); ?></h2>
                    <p>Piso: <?php echo htmlspecialchars($cuarto['piso']); ?></p>
                    <p>Capacidad Adultos: <?php echo htmlspecialchars($cuarto['capacidad_adultos']); ?></p>
                    <p>Capacidad Niños: <?php echo htmlspecialchars($cuarto['capacidad_niños']); ?></p>
                    <p>Precio Base: <?php echo htmlspecialchars($cuarto['precio_base']); ?> USD</p>
                    <p>Descripción: <?php echo htmlspecialchars($cuarto['descripcion'] ?? 'No disponible'); ?></p>
                    <label>
                        <input type="checkbox" name="cuartos[]" value="<?php echo htmlspecialchars($cuarto['id_cuarto']); ?>">
                        Seleccionar esta habitación
                    </label>
                </div>
                <hr>
            <?php endforeach; ?>
            <button type="submit">Reservar Habitaciones Seleccionadas</button>
        </form>
    <?php else: ?>
        <p>No hay habitaciones disponibles para las fechas y capacidades seleccionadas.</p>
    <?php endif; ?>

    <?php
    // Cerrar la conexión a la base de datos
    $conn->close();
    ?>
</body>
</html>
