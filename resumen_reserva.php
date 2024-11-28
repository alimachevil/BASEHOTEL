<?php
session_start();

// Conexión a la base de datos
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'hotel_db';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtener el ID de la reserva desde la sesión
$id_reserva = $_SESSION['id_reserva'];
$fecha_checkin = '';
$fecha_checkout = '';
$fecha_reserva = '';
$total_cuartos = 0;
$total_mesas = 0;
$total_precio = 0;
$descuento = 0;
$nombre_promocion = '';
$porcentaje_descuento = 0;
$precio_total_con_descuento = 0;

// 1. Consulta para obtener los cuartos seleccionados y sus precios
$sql_cuartos = "
    SELECT c.precio_base 
    FROM reservaporcuartos rpc
    INNER JOIN cuartos c ON rpc.id_cuarto = c.id_cuarto
    WHERE rpc.id_reserva = ?";
$stmt_cuartos = $conn->prepare($sql_cuartos);
$stmt_cuartos->bind_param("i", $id_reserva);
$stmt_cuartos->execute();
$result_cuartos = $stmt_cuartos->get_result();

if ($result_cuartos->num_rows > 0) {
    while ($row_cuartos = $result_cuartos->fetch_assoc()) {
        $total_cuartos += $row_cuartos['precio_base']; // Sumar el precio de cada cuarto
    }
}

// 2. Consulta para obtener las mesas seleccionadas y sus precios
$sql_mesas = "
    SELECT m.precio_reservam 
    FROM reserva_restaurante rr
    INNER JOIN mesas m ON rr.id_mesa = m.id_mesa
    WHERE rr.id_reserva = ?";
$stmt_mesas = $conn->prepare($sql_mesas);
$stmt_mesas->bind_param("i", $id_reserva);
$stmt_mesas->execute();
$result_mesas = $stmt_mesas->get_result();

if ($result_mesas->num_rows > 0) {
    while ($row_mesas = $result_mesas->fetch_assoc()) {
        $total_mesas += $row_mesas['precio_reservam']; // Sumar el precio de cada mesa
    }
}

// Calcular el precio total de cuartos y mesas
$total_precio = $total_cuartos + $total_mesas;

// 3. Consulta para obtener las fechas de la reserva (check-in, check-out, y fecha de reserva)
$sql_reserva = "SELECT fecha_checkin, fecha_checkout, fecha_reserva FROM reservas WHERE id_reserva = ?";
$stmt_reserva = $conn->prepare($sql_reserva);
$stmt_reserva->bind_param("i", $id_reserva);
$stmt_reserva->execute();
$result_reserva = $stmt_reserva->get_result();

if ($result_reserva->num_rows > 0) {
    $row_reserva = $result_reserva->fetch_assoc();
    $fecha_checkin = $row_reserva['fecha_checkin'];
    $fecha_checkout = $row_reserva['fecha_checkout'];
    $fecha_reserva = $row_reserva['fecha_reserva'];
}

// 4. Consultar si hay una promoción que aplique según las fechas de la reserva (check-in o fecha de reserva)
$sql_promocion = "SELECT id_promocion, nombre_promocion, descuento 
                  FROM promociones 
                  WHERE (? BETWEEN fecha_inicio AND fecha_fin) 
                  OR (? BETWEEN fecha_inicio AND fecha_fin)";
$stmt_promocion = $conn->prepare($sql_promocion);
$stmt_promocion->bind_param("ss", $fecha_checkin, $fecha_reserva);
$stmt_promocion->execute();
$result_promocion = $stmt_promocion->get_result();

if ($result_promocion->num_rows > 0) {
    $row_promocion = $result_promocion->fetch_assoc();
    $id_promocion = $row_promocion['id_promocion'];
    $nombre_promocion = $row_promocion['nombre_promocion'];
    $porcentaje_descuento = $row_promocion['descuento'];
    $descuento = ($total_precio * $porcentaje_descuento) / 100;
}

// Calcular el precio total con descuento si hay una promoción aplicable
$precio_total_con_descuento = $total_precio - $descuento;

// Manejar la opción de cancelar y proceder al pago
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['cancelar'])) {
        header("Location: usuario.php");
        exit();
    }

    if (isset($_POST['proceder_pago'])) {
        // Actualizar la reserva con el total final y el id de la promoción (si aplica)
        $sql_update_reserva = "UPDATE reservas SET total_pago = ?, id_promocion = ? WHERE id_reserva = ?";
        $stmt_update_reserva = $conn->prepare($sql_update_reserva);
        $stmt_update_reserva->bind_param("dii", $precio_total_con_descuento, $id_promocion, $id_reserva);
        $stmt_update_reserva->execute();

        // Redirigir a la página de pago
        header("Location: pago.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resumen de la Reserva</title>
</head>
<body>
    <h1>Resumen de la Reserva</h1>

    <h2>Detalles de la Reserva</h2>
    <p><strong>Fecha de Check-in:</strong> <?php echo htmlspecialchars($fecha_checkin); ?></p>
    <p><strong>Fecha de Check-out:</strong> <?php echo htmlspecialchars($fecha_checkout); ?></p>
    <p><strong>Precio por Cuartos:</strong> S/. <?php echo number_format($total_cuartos, 2); ?></p>
    <p><strong>Precio por Mesas (si reservó):</strong> S/. <?php echo number_format($total_mesas, 2); ?></p>
    <p><strong>Precio Total:</strong> S/. <?php echo number_format($total_precio, 2); ?></p>

    <?php if ($porcentaje_descuento > 0) { ?>
        <h3>Promoción Aplicable</h3>
        <p><strong>Promoción:</strong> <?php echo htmlspecialchars($nombre_promocion); ?></p>
        <p><strong>Descuento:</strong> <?php echo $porcentaje_descuento; ?>%</p>
        <p><strong>Descuento Aplicado:</strong> S/. <?php echo number_format($descuento, 2); ?></p>
    <?php } else { ?>
        <p>No hay promociones aplicables para las fechas seleccionadas.</p>
    <?php } ?>

    <h3>Total Final</h3>
    <p><strong>Precio Total con Descuento:</strong> S/. <?php echo number_format($precio_total_con_descuento, 2); ?></p>

    <form action="resumen_reserva.php" method="POST">
        <button type="submit" name="cancelar">Cancelar</button>
        <button type="submit" name="proceder_pago">Proceder al Pago</button>
    </form>
</body>
</html>
