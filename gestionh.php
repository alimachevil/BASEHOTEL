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

// Inicializar habitaciones seleccionadas
if (!isset($_SESSION['cuartos_seleccionados'])) {
    $_SESSION['cuartos_seleccionados'] = [];
}
$cuartos_seleccionados = $_SESSION['cuartos_seleccionados'];

// Redirigir al pago si ya se alcanzó el límite
if (count($cuartos_seleccionados) >= $habitaciones) {
    header('Location: pagoh.php');
    exit();
}

// Procesar la selección de habitación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reservar'])) {
    if (count($cuartos_seleccionados) < $habitaciones) {
        $id_cuarto = $_POST['id_cuarto'];
        $tipo_pago = $_POST['tipo_pago'];

        // Guardar la habitación seleccionada
        $_SESSION['cuartos_seleccionados'][] = [
            'id_cuarto' => $id_cuarto,
            'tipo_pago' => $tipo_pago,
        ];

        // Redirigir para evitar reenvío del formulario
        header('Location: gestionh.php');
        exit();
    }
}

// Consulta para obtener los cuartos disponibles
$cuartos_disponibles = [];
for ($i = 0; $i < $habitaciones; $i++) {
    $capacidad_adultos = (int) $adultos[$i];
    $capacidad_ninos = (int) $ninos[$i];

    // Manejar habitaciones ya seleccionadas dinámicamente
    $not_in_clause = '';
    if (!empty($cuartos_seleccionados)) {
        $ids_seleccionados = implode(',', array_map('intval', array_column($cuartos_seleccionados, 'id_cuarto')));
        $not_in_clause = "AND c.id_cuarto NOT IN ($ids_seleccionados)";
    }

    $query = "
        SELECT c.*
        FROM cuartos c
        WHERE c.estado = 'Disponible'
        AND c.capacidad_adultos >= $capacidad_adultos
        AND c.capacidad_niños >= $capacidad_ninos
        $not_in_clause
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
    <h1>Gestión de Habitaciones</h1>

    <!-- Mostrar habitaciones seleccionadas -->
    <h2>Habitaciones Seleccionadas:</h2>
    <ul>
        <?php foreach ($cuartos_seleccionados as $seleccion): ?>
            <li>
                Habitación ID: <?php echo htmlspecialchars($seleccion['id_cuarto']); ?>, 
                Pago: <?php echo $seleccion['tipo_pago'] === 'hotel' ? 'En Hotel' : 'Por Web'; ?>
            </li>
        <?php endforeach; ?>
    </ul>

    <!-- Mostrar habitaciones disponibles -->
    <?php if (count($cuartos_seleccionados) < $habitaciones): ?>
        <h2>Habitaciones Disponibles:</h2>
        <?php if (!empty($cuartos_disponibles)): ?>
            <?php foreach ($cuartos_disponibles as $cuarto): ?>
                <div>
                    <h3>Habitación #<?php echo htmlspecialchars($cuarto['numero']); ?></h3>
                    <p>Piso: <?php echo htmlspecialchars($cuarto['piso']); ?></p>
                    <p>Capacidad Adultos: <?php echo htmlspecialchars($cuarto['capacidad_adultos']); ?></p>
                    <p>Capacidad Niños: <?php echo htmlspecialchars($cuarto['capacidad_niños']); ?></p>
                    <p>Precio Base: <?php echo htmlspecialchars($cuarto['precio_base']); ?> USD</p>
                    <p>Descripción: <?php echo htmlspecialchars($cuarto['descripcion'] ?? 'No disponible'); ?></p>
                    <form method="POST">
                        <input type="hidden" name="id_cuarto" value="<?php echo htmlspecialchars($cuarto['id_cuarto']); ?>">
                        <label>
                            <input type="radio" name="tipo_pago" value="hotel" required>
                            Pagar en Hotel (<?php echo htmlspecialchars($cuarto['precio_base']); ?> USD)
                        </label>
                        <label>
                            <input type="radio" name="tipo_pago" value="web" required>
                            Pagar por Web (<?php echo htmlspecialchars($cuarto['precio_base'] * 0.7); ?> USD)
                        </label>
                        <button type="submit" name="reservar">Reservar</button>
                    </form>
                </div>
                <hr>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No hay habitaciones disponibles para las fechas y capacidades seleccionadas.</p>
        <?php endif; ?>
    <?php else: ?>
        <p>Ya has seleccionado todas las habitaciones necesarias.</p>
        <a href="pagoh.php">Continuar al Pago</a>
    <?php endif; ?>

    <?php $conn->close(); ?>
</body>
</html>
