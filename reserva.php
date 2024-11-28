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

// Inicializar variables para las fechas
$checkin_consulta = '';
$checkout_consulta = '';
$checkin_reserva = '';
$checkout_reserva = '';
$total_costo = 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['consultar_cuartos'])) {
        // Sección de consulta de disponibilidad
        $checkin_consulta = $_POST['fecha_checkin_consulta'];
        $checkout_consulta = $_POST['fecha_checkout_consulta'];
        
        // Consulta de cuartos disponibles
        $sql_reservas = "SELECT id_reserva FROM reservas WHERE fecha_checkin <= ? AND fecha_checkout >= ?";
        $stmt_reservas = $conn->prepare($sql_reservas);
        $stmt_reservas->bind_param("ss", $checkout_consulta, $checkin_consulta);
        $stmt_reservas->execute();
        $result_reservas = $stmt_reservas->get_result();
        
        $reservados = [];
        if ($result_reservas->num_rows > 0) {
            while ($row_reserva = $result_reservas->fetch_assoc()) {
                $sql_cuartos_reservados = "SELECT id_cuarto FROM reservaporcuartos WHERE id_reserva = ?";
                $stmt_cuartos = $conn->prepare($sql_cuartos_reservados);
                $stmt_cuartos->bind_param("i", $row_reserva['id_reserva']);
                $stmt_cuartos->execute();
                $result_cuartos = $stmt_cuartos->get_result();
                while ($row_cuarto = $result_cuartos->fetch_assoc()) {
                    $reservados[] = $row_cuarto['id_cuarto'];
                }
            }
        }

        // Cuartos disponibles
        if (count($reservados) > 0) {
            $sql_disponibles = "SELECT * FROM cuartos WHERE id_cuarto NOT IN (" . implode(",", array_fill(0, count($reservados), '?')) . ")";
            $stmt_disponibles = $conn->prepare($sql_disponibles);
            $stmt_disponibles->bind_param(str_repeat('i', count($reservados)), ...$reservados);
        } else {
            $sql_disponibles = "SELECT * FROM cuartos";
            $stmt_disponibles = $conn->prepare($sql_disponibles);
        }

        $stmt_disponibles->execute();
        $result_disponibles = $stmt_disponibles->get_result();
        $cuartos_disponibles = [];
        while ($row_cuarto = $result_disponibles->fetch_assoc()) {
            $cuartos_disponibles[] = $row_cuarto;
        }

    } elseif (isset($_POST['reservar_cuartos'])) {
        // Sección de reserva
        $checkin_reserva = $_POST['fecha_checkin_reserva'];
        $checkout_reserva = $_POST['fecha_checkout_reserva'];
        $cuartos_seleccionados = $_POST['cuartos'] ?? [];
        $id_cliente = $_SESSION['id_cliente'];
        $id_hotel = 8;

        // Calcular el costo total
        foreach ($cuartos_seleccionados as $id_cuarto) {
            $sql_precio = "SELECT precio_base FROM cuartos WHERE id_cuarto = ?";
            $stmt_precio = $conn->prepare($sql_precio);
            $stmt_precio->bind_param("i", $id_cuarto);
            $stmt_precio->execute();
            $result_precio = $stmt_precio->get_result();
            if ($row_precio = $result_precio->fetch_assoc()) {
                $total_costo += $row_precio['precio_base'];
            }
        }

        // Insertar nueva reserva
        $sql_insert_reserva = "INSERT INTO reservas (fecha_reserva, fecha_checkin, fecha_checkout, total_pago, id_cliente, id_promocion, id_hotel) 
                               VALUES (NOW(), ?, ?, NULL, ?, NULL, ?)";
        $stmt_insert = $conn->prepare($sql_insert_reserva);
        $stmt_insert->bind_param("ssii", $checkin_reserva, $checkout_reserva, $id_cliente, $id_hotel);

        if ($stmt_insert->execute()) {
            $id_reserva = $stmt_insert->insert_id; // Obtener el ID de la reserva
            $_SESSION['id_reserva'] = $id_reserva; // Guardar el ID de reserva
            $_SESSION['costo_total'] = $total_costo; // Guardar el costo total

            // Insertar cuartos reservados
            foreach ($cuartos_seleccionados as $id_cuarto) {
                $sql_insert_reserva_cuarto = "INSERT INTO reservaporcuartos (id_reserva, id_cuarto) VALUES (?, ?)";
                $stmt_insert_cuarto = $conn->prepare($sql_insert_reserva_cuarto);
                $stmt_insert_cuarto->bind_param("ii", $id_reserva, $id_cuarto);
                $stmt_insert_cuarto->execute();
            }

            // Redireccionar a la siguiente página
            header("Location: reserva_restaurante.php");
            exit();
        } else {
            echo "Error al guardar la reserva: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserva de Cuartos</title>
</head>
<body>
    <h1>Reserva de Cuartos</h1>

    <!-- Sección 1: Consulta de cuartos disponibles -->
    <form action="reserva.php" method="POST">
        <h2>Consulta de Disponibilidad</h2>
        <label for="fecha_checkin_consulta">Fecha de Check-In:</label>
        <input type="date" name="fecha_checkin_consulta" id="fecha_checkin_consulta" required value="<?php echo htmlspecialchars($checkin_consulta); ?>"><br>

        <label for="fecha_checkout_consulta">Fecha de Check-Out:</label>
        <input type="date" name="fecha_checkout_consulta" id="fecha_checkout_consulta" required value="<?php echo htmlspecialchars($checkout_consulta); ?>"><br>

        <button type="submit" name="consultar_cuartos">Consultar Disponibilidad</button>
    </form>

    <?php if (isset($cuartos_disponibles) && count($cuartos_disponibles) > 0) { ?>
        <!-- Sección 2: Selección y reserva de cuartos -->
        <form action="reserva.php" method="POST">
            <h2>Reservar Cuartos</h2>

            <label for="fecha_checkin_reserva">Fecha de Check-In:</label>
            <input type="date" name="fecha_checkin_reserva" id="fecha_checkin_reserva" required value="<?php echo htmlspecialchars($checkin_reserva); ?>"><br>

            <label for="fecha_checkout_reserva">Fecha de Check-Out:</label>
            <input type="date" name="fecha_checkout_reserva" id="fecha_checkout_reserva" required value="<?php echo htmlspecialchars($checkout_reserva); ?>"><br>

            <?php foreach ($cuartos_disponibles as $cuarto) { ?>
                <input type="checkbox" name="cuartos[]" value="<?php echo $cuarto['id_cuarto']; ?>" class="cuarto" data-precio="<?php echo $cuarto['precio_base']; ?>"> 
                Cuarto: <?php echo $cuarto['numero']; ?> - Tipo: <?php echo $cuarto['tipo']; ?> - Precio: S/. <?php echo $cuarto['precio_base']; ?><br>
            <?php } ?>
            <br>
            <h3>Total: S/. <span id="total-costo">0</span></h3>
            <button type="submit" name="reservar_cuartos">Reservar Cuartos</button>
        </form>
    <?php } elseif (isset($cuartos_disponibles)) { ?>
        <p>No hay cuartos disponibles para las fechas seleccionadas.</p>
    <?php } ?>

    <script>
        const checkboxes = document.querySelectorAll('.cuarto');
        const totalCostoElement = document.getElementById('total-costo');

        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                let total = 0;
                checkboxes.forEach(cb => {
                    if (cb.checked) {
                        total += parseFloat(cb.getAttribute('data-precio'));
                    }
                });
                totalCostoElement.textContent = total.toFixed(2);
            });
        });
    </script>
</body>
</html>
