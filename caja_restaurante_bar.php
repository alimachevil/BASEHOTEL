<?php
session_start();

// Verifica si hay datos de pedidos en la sesión
if (!isset($_SESSION['pedidos']) || count($_SESSION['pedidos']) == 0) {
    echo "No hay pedidos en la sesión.";
    exit();
}

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

// Fecha y hora actuales
$fecha_actual = date('Y-m-d H:i:s');

// Manejo del formulario de ID de reserva
$id_reserva = null;
$id_cuenta = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_reserva'])) {
    $id_reserva = (int)$_POST['id_reserva'];

    // Verificar si la reserva ya tiene una cuenta asociada
    $query_cuenta = "SELECT id_cuenta FROM cuenta_cobranza WHERE id_reserva = $id_reserva";
    $result_cuenta = $conn->query($query_cuenta);

    if ($result_cuenta->num_rows > 0) {
        // Recuperar el ID de la cuenta existente
        $id_cuenta = $result_cuenta->fetch_assoc()['id_cuenta'];
    } else {
        // Crear una nueva cuenta
        $query_nueva_cuenta = "INSERT INTO cuenta_cobranza (id_reserva, monto) VALUES ($id_reserva, 0)";
        if ($conn->query($query_nueva_cuenta)) {
            $id_cuenta = $conn->insert_id;
        } else {
            die("Error al crear una nueva cuenta: " . $conn->error);
        }
    }

    // Procesar los pedidos en la sesión
    $total = 0;
    foreach ($_SESSION['pedidos'] as $pedido) {
        $id_item = $pedido['id'];
        $cantidad = $pedido['cantidad'];
        $precio = $pedido['precio'];
        $subtotal = $cantidad * $precio;

        $total += $subtotal;

        if ($pedido['tipo'] === 'bebida') {
            // Insertar en consumo_bar
            $query_bar = "
                INSERT INTO consumo_bar (id_cuenta, id_bebida, cantidad, subtotal, fecha_consumo)
                VALUES ($id_cuenta, $id_item, $cantidad, $subtotal, '$fecha_actual')
            ";
            if (!$conn->query($query_bar)) {
                die("Error al registrar consumo de bebida: " . $conn->error);
            }
        } elseif ($pedido['tipo'] === 'plato') {
            // Insertar en consumo_restaurante
            $query_restaurante = "
                INSERT INTO consumo_restaurante (id_cuenta, id_plato, cantidad, subtotal, fecha_consumo)
                VALUES ($id_cuenta, $id_item, $cantidad, $subtotal, '$fecha_actual')
            ";
            if (!$conn->query($query_restaurante)) {
                die("Error al registrar consumo de plato: " . $conn->error);
            }
        }
    }

    // Actualizar el total en cuenta_cobranza
    $query_actualizar_cuenta = "
        UPDATE cuenta_cobranza SET monto = monto + $total WHERE id_cuenta = $id_cuenta
    ";
    if (!$conn->query($query_actualizar_cuenta)) {
        die("Error al actualizar el monto de la cuenta: " . $conn->error);
    }

    // Limpiar la sesión de pedidos
    unset($_SESSION['pedidos']);

    echo "Pedidos registrados exitosamente.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caja Restaurante-Bar</title>
</head>
<body>
    <h1>Caja Restaurante-Bar</h1>
    <form method="POST">
        <label for="id_reserva">ID de Reserva:</label>
        <input type="number" id="id_reserva" name="id_reserva" required>
        <button type="submit">Procesar Pedido</button>
    </form>

    <?php if (isset($_SESSION['pedidos']) && count($_SESSION['pedidos']) > 0): ?>
        <h2>Resumen del Pedido</h2>
        <table border="1">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Nombre</th>
                    <th>Cantidad</th>
                    <th>Precio</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total = 0;
                foreach ($_SESSION['pedidos'] as $pedido):
                    $id_item = $pedido['id'];
                    $cantidad = $pedido['cantidad'];
                    $precio = $pedido['precio'];
                    $subtotal = $pedido['cantidad'] * $pedido['precio'];
                    $total += $subtotal;
                ?>
                    <tr>
                        <td><?php echo ucfirst($pedido['tipo']); ?></td>
                        <td><?php echo htmlspecialchars($pedido['nombre']); ?></td>
                        <td><?php echo $pedido['cantidad']; ?></td>
                        <td><?php echo number_format($pedido['precio'], 2); ?></td>
                        <td><?php echo number_format($subtotal, 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4">Total</td>
                    <td><?php echo number_format($total, 2); ?></td>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>
</body>
</html>
