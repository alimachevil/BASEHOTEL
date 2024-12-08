<?php
// Configuración de la conexión a la base de datos
$host = 'srv1006.hstgr.io'; // Cambia si es necesario
$dbname = 'u472469844_est18'; // Asegúrate de que sea el nombre correcto de la base de datos
$username = 'u472469844_est18'; // El usuario de la base de datos
$password_db = ''; // La contraseña de la base de datos

// Crear conexión
$conn = new mysqli($host, $username, $password_db, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtener el ID del cliente desde el parámetro GET
$id_cliente = isset($_GET['id_cliente']) ? intval($_GET['id_cliente']) : 0;

// Eliminar reserva si se recibe un id_reserva
if (isset($_POST['delete_reserva'])) {
    $id_reserva = intval($_POST['id_reserva']);
    $sql_delete = "DELETE FROM reservas WHERE id_reserva = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $id_reserva);
    if ($stmt_delete->execute()) {
        echo "<p>Reserva eliminada con éxito.</p>";
    } else {
        echo "<p>Error al eliminar la reserva: " . $conn->error . "</p>";
    }
    $stmt_delete->close();
}

// Consulta para obtener el nombre, apellido y DNI del cliente
$sql_cliente = "
    SELECT c.nombre, c.apellido, c.dni
    FROM clientes c
    WHERE c.id_cliente = ?
";

$stmt_cliente = $conn->prepare($sql_cliente);
$stmt_cliente->bind_param("i", $id_cliente);
$stmt_cliente->execute();
$result_cliente = $stmt_cliente->get_result();

// Verificar si se encontró el cliente
if ($result_cliente->num_rows > 0) {
    $cliente = $result_cliente->fetch_assoc();
    echo "<h1>Detalles de {$cliente['nombre']} {$cliente['apellido']} (DNI: {$cliente['dni']})</h1>";
    
    // Obtener reservas del cliente
    $sql_reservas = "
        SELECT r.id_reserva, r.fecha_reserva, r.fecha_checkin, r.fecha_checkout, r.total_pago, 
               h.nombre AS nombre_hotel, h.direccion, h.ciudad, h.telefono, h.categoria, h.pisos
        FROM reservas r
        JOIN hoteles h ON r.id_hotel = h.id_hotel
        WHERE r.id_cliente = ?
    ";

    $stmt_reservas = $conn->prepare($sql_reservas);
    $stmt_reservas->bind_param("i", $id_cliente);
    $stmt_reservas->execute();
    $result_reservas = $stmt_reservas->get_result();

    if ($result_reservas->num_rows > 0) {
        echo "<table border='1'>
                <tr>
                    <th>ID Reserva</th>
                    <th>Fecha Reserva</th>
                    <th>Fecha Check-in</th>
                    <th>Fecha Check-out</th>
                    <th>Total Pago</th>
                    <th>Nombre Hotel</th>
                    <th>Dirección</th>
                    <th>Ciudad</th>
                    <th>Teléfono</th>
                    <th>Categoría</th>
                    <th>Pisos</th>
                    <th>Acciones</th>
                </tr>";

        while ($row = $result_reservas->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['id_reserva']}</td>
                    <td>{$row['fecha_reserva']}</td>
                    <td>{$row['fecha_checkin']}</td>
                    <td>{$row['fecha_checkout']}</td>
                    <td>{$row['total_pago']}</td>
                    <td>{$row['nombre_hotel']}</td>
                    <td>{$row['direccion']}</td>
                    <td>{$row['ciudad']}</td>
                    <td>{$row['telefono']}</td>
                    <td>{$row['categoria']}</td>
                    <td>{$row['pisos']}</td>
                    <td>
                        <form action='' method='post' style='display:inline;'>
                            <input type='hidden' name='id_reserva' value='{$row['id_reserva']}'>
                            <input type='submit' name='delete_reserva' value='Eliminar' onclick='return confirm(\"¿Estás seguro de que deseas eliminar esta reserva?\");'>
                        </form>
                    </td>
                  </tr>";
        }
        echo "</table>";
    } else {
        echo "No se encontraron reservas para el cliente con ID: $id_cliente.";
    }

    $stmt_reservas->close();
} else {
    echo "No se encontró ningún cliente con ID: $id_cliente.";
}

$stmt_cliente->close();
$conn->close();
?>
