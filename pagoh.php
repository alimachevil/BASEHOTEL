<?php
// Inicia la sesión
session_start();

// Función para calcular los días de estadía
function calcularDiasEstadia($check_in, $check_out) {
    $fecha1 = new DateTime($check_in);
    $fecha2 = new DateTime($check_out);
    return $fecha2->diff($fecha1)->days;
}

// Verifica si las fechas y las habitaciones seleccionadas están en sesión
if (!isset($_SESSION['check_in'], $_SESSION['check_out'], $_SESSION['cuartos_seleccionados'])) {
    header('Location: index.php'); // Redirigir al inicio si no hay datos
    exit();
}

// Variables de sesión
$check_in = $_SESSION['check_in'];
$check_out = $_SESSION['check_out'];
$cuartos_seleccionados = $_SESSION['cuartos_seleccionados']; // IDs de las habitaciones seleccionadas

// Conexión a la base de datos
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'hotel_db';
$conn = new mysqli($host, $user, $pass, $dbname);

// Verificar la conexión
if ($conn->connect_error) {
    die("Error en la conexión a la base de datos: " . $conn->connect_error);
}

// Consultar información de las habitaciones seleccionadas
$habitaciones_info = [];
if (!empty($cuartos_seleccionados)) {
    $ids = implode(',', array_map('intval', $cuartos_seleccionados));
    $query = "SELECT id_cuarto, numero, precio_base FROM cuartos WHERE id_cuarto IN ($ids)";
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $habitaciones_info[] = $row;
        }
    }
}

// Cálculo de días de estadía
$dias_estadia = calcularDiasEstadia($check_in, $check_out);

// Verifica si el formulario de cliente se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar y guardar los datos del cliente
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $tipo_documento = $_POST['tipo_documento'];
    $nro_documento = $_POST['nro_documento'];
    $correo = $_POST['correo'];
    $celular = $_POST['celular'];
    $pais = $_POST['pais'];

    // Calcular el total de la reserva
    $total_pago = 0;
    foreach ($habitaciones_info as $habitacion) {
        $total_pago += $habitacion['precio_base'] * $dias_estadia;
    }

    // Insertar datos del cliente en la tabla `clientes`
    $query_cliente = "
        INSERT INTO clientes (nombre, apellido, tipo_documento, nro_documento, celular, pais, correo)
        VALUES ('$nombre', '$apellido', '$tipo_documento', '$nro_documento', '$celular', '$pais', '$correo')
    ";
    if (!$conn->query($query_cliente)) {
        die("Error al guardar el cliente: " . $conn->error);
    }
    $id_cliente = $conn->insert_id;

    // Insertar la reserva en la tabla `reservas`
    $fecha_reserva = date('Y-m-d H:i:s'); // Fecha actual
    $id_promocion = null; // Asumimos sin promociones
    $id_hotel = 1; // Asumimos un hotel por defecto
    $query_reserva = "
        INSERT INTO reservas (fecha_reserva, fecha_checkin, fecha_checkout, total_pago, id_cliente, id_promocion, id_hotel)
        VALUES ('$fecha_reserva', '$check_in', '$check_out', $total_pago, $id_cliente, " . ($id_promocion ? $id_promocion : 'NULL') . ", $id_hotel)
    ";
    if (!$conn->query($query_reserva)) {
        die("Error al guardar la reserva: " . $conn->error);
    }
    $id_reserva = $conn->insert_id;

    // Insertar la relación entre la reserva y las habitaciones seleccionadas
    foreach ($cuartos_seleccionados as $id_cuarto) {
        $query_reservaporcuartos = "
            INSERT INTO reservaporcuartos (id_reserva, id_cuarto)
            VALUES ($id_reserva, $id_cuarto)
        ";
        if (!$conn->query($query_reservaporcuartos)) {
            die("Error al guardar la relación de reserva y cuartos: " . $conn->error);
        }
    }

    // Guardar el ID de reserva en sesión y redirigir a la página de confirmación
    $_SESSION['id_reserva'] = $id_reserva;
    header('Location: confirmacion_pago.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago de Reserva</title>
</head>
<body>
    <h1>Pago de Reserva</h1>

    <!-- Mostrar los días de estadía -->
    <h2>Días de Estadía: <?php echo htmlspecialchars($dias_estadia); ?></h2>

    <!-- Mostrar las habitaciones seleccionadas -->
    <h2>Habitaciones Seleccionadas:</h2>
    <ul>
        <?php foreach ($habitaciones_info as $habitacion): ?>
            <li>
                Habitación <?php echo htmlspecialchars($habitacion['numero']); ?> - 
                Costo por noche: <?php echo htmlspecialchars($habitacion['precio_base']); ?> USD
            </li>
        <?php endforeach; ?>
    </ul>

    <!-- Formulario para los datos del cliente -->
    <h2>Datos del Cliente:</h2>
    <form action="pagoh.php" method="POST">
        <label for="nombre">Nombre:</label>
        <input type="text" id="nombre" name="nombre" required>
        <br><br>
        
        <label for="apellido">Apellido:</label>
        <input type="text" id="apellido" name="apellido" required>
        <br><br>
        
        <label for="tipo_documento">Tipo de Documento:</label>
        <select id="tipo_documento" name="tipo_documento" required>
            <option value="DNI">DNI</option>
            <option value="Pasaporte">Pasaporte</option>
            <option value="Otro">Otro</option>
        </select>
        <br><br>
        
        <label for="nro_documento">Número de Documento:</label>
        <input type="text" id="nro_documento" name="nro_documento" required>
        <br><br>
        
        <label for="correo">Correo Electrónico:</label>
        <input type="email" id="correo" name="correo" required>
        <br><br>
        
        <label for="celular">Celular:</label>
        <input type="text" id="celular" name="celular" required>
        <br><br>
        
        <label for="pais">País:</label>
        <input type="text" id="pais" name="pais" required>
        <br><br>

        <!-- Resumen de costos -->
        <h2>Resumen:</h2>
        <?php
        $total = 0;
        foreach ($habitaciones_info as $habitacion) {
            $subtotal = $habitacion['precio_base'] * $dias_estadia;
            echo "<p>Habitación {$habitacion['numero']}: $subtotal USD</p>";
            $total += $subtotal;
        }
        ?>
        <h3>Total: <?php echo htmlspecialchars($total); ?> USD</h3>
        <br><br>

        <!-- Botón de pago -->
        <button type="submit">Proceder al Pago</button>
    </form>
</body>
</html>
