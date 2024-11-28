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
$fecha_reserva = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['consultar_mesas'])) {
    $fecha_reserva = $_POST['fecha_reserva'];
    $_SESSION['fecha_reserva_mesas'] = $fecha_reserva;
    
    // Buscar mesas que ya están reservadas para esa fecha
    $sql_mesas_reservadas = "SELECT id_mesa FROM reserva_restaurante WHERE fecha_reserva = ?";
    $stmt_mesas_reservadas = $conn->prepare($sql_mesas_reservadas);
    $stmt_mesas_reservadas->bind_param("s", $fecha_reserva);
    $stmt_mesas_reservadas->execute();
    $result_mesas_reservadas = $stmt_mesas_reservadas->get_result();

    $mesas_reservadas = [];
    if ($result_mesas_reservadas->num_rows > 0) {
        while ($row_mesa_reservada = $result_mesas_reservadas->fetch_assoc()) {
            $mesas_reservadas[] = $row_mesa_reservada['id_mesa'];
        }
    }

    // Mostrar mesas disponibles
    if (count($mesas_reservadas) > 0) {
        $sql_mesas_disponibles = "SELECT * FROM mesas WHERE id_mesa NOT IN (" . implode(",", array_fill(0, count($mesas_reservadas), '?')) . ")";
        $stmt_mesas_disponibles = $conn->prepare($sql_mesas_disponibles);
        $stmt_mesas_disponibles->bind_param(str_repeat('i', count($mesas_reservadas)), ...$mesas_reservadas);
    } else {
        $sql_mesas_disponibles = "SELECT * FROM mesas";
        $stmt_mesas_disponibles = $conn->prepare($sql_mesas_disponibles);
    }

    $stmt_mesas_disponibles->execute();
    $result_mesas_disponibles = $stmt_mesas_disponibles->get_result();

    // Mostrar mesas disponibles
    $mesas_disponibles = [];
    while ($row_mesa = $result_mesas_disponibles->fetch_assoc()) {
        $mesas_disponibles[] = $row_mesa;
    }
}

// Calcular el costo total según las mesas seleccionadas
$total_costo_mesas = 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reservar_mesas'])) {
    $mesas_seleccionadas = $_POST['mesas'] ?? [];
    $id_reserva = $_SESSION['id_reserva']; // Asegúrate de que el ID de la reserva esté en la sesión

    // Calcular el total del costo de las mesas seleccionadas
    foreach ($mesas_seleccionadas as $id_mesa) {
        $sql_precio_mesa = "SELECT precio_reservam FROM mesas WHERE id_mesa = ?";
        $stmt_precio_mesa = $conn->prepare($sql_precio_mesa);
        $stmt_precio_mesa->bind_param("i", $id_mesa);
        $stmt_precio_mesa->execute();
        $result_precio_mesa = $stmt_precio_mesa->get_result();
        if ($row_precio_mesa = $result_precio_mesa->fetch_assoc()) {
            $total_costo_mesas += $row_precio_mesa['precio_reservam'];
        }
    }

    // Guardar la reserva en la tabla `reserva_restaurante`
    foreach ($mesas_seleccionadas as $id_mesa) {
        $sql_insert_reserva_mesa = "INSERT INTO reserva_restaurante (id_mesa, id_reserva, fecha_reserva) VALUES (?, ?, ?)";
        $stmt_insert_reserva_mesa = $conn->prepare($sql_insert_reserva_mesa);
        $stmt_insert_reserva_mesa->bind_param("iis", $id_mesa, $id_reserva, $_SESSION['fecha_reserva_mesas']);
        $stmt_insert_reserva_mesa->execute();
    }

    // Guardar el costo parcial en la sesión
    $_SESSION['costo_total_mesas'] = $total_costo_mesas;

    // Botón para resetear el formulario y reservar más mesas para otro día
    if (isset($_POST['añadir_reserva'])) {
        header("Location: reserva_restaurante.php"); // Redirige de nuevo a la página de reserva para seleccionar más mesas
        exit();
    }

    // Botón para continuar a la siguiente sección
    if (isset($_POST['continuar'])) {
        header("Location: siguiente_seccion.php"); // Cambia esto a la página deseada
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserva de Mesas</title>
</head>
<body>
    <h1>Reserva de Mesas</h1>

    <!-- Sección de consulta de disponibilidad de mesas -->
    <form action="reserva_restaurante.php" method="POST">
        <label for="fecha_reserva">Fecha de la Reserva:</label>
        <input type="date" name="fecha_reserva" id="fecha_reserva" required value="<?php echo htmlspecialchars($fecha_reserva); ?>"><br>
        <button type="submit" name="consultar_mesas">Consultar Disponibilidad</button>
        <button onclick="window.location.href='resumen_reserva.php'">Continuar</button>
    </form>

    <?php if (isset($mesas_disponibles) && count($mesas_disponibles) > 0) { ?>
        <!-- Sección para mostrar mesas disponibles y reservar -->
        <form action="reserva_restaurante.php" method="POST">
            <h2>Mesas Disponibles</h2>
            <?php foreach ($mesas_disponibles as $mesa) { ?>
                <input type="checkbox" name="mesas[]" value="<?php echo $mesa['id_mesa']; ?>" class="mesa" data-precio="<?php echo $mesa['precio_reservam']; ?>">
                Mesa: <?php echo $mesa['tipo']; ?> - Descripción: <?php echo $mesa['descripcion']; ?> - Precio Reserva: S/. <?php echo $mesa['precio_reservam']; ?><br>
            <?php } ?>
            <br>
            <h3>Total: S/. <span id="total-costo-mesas">0</span></h3>
            <button type="submit" name="reservar_mesas">Reservar Mesas</button>
        </form>
    <?php } elseif (isset($mesas_disponibles)) { ?>
        <p>No hay mesas disponibles para la fecha seleccionada.</p>
    <?php } ?>

    <script>
        const checkboxesMesas = document.querySelectorAll('.mesa');
        const totalCostoMesasElement = document.getElementById('total-costo-mesas');

        checkboxesMesas.forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                let totalMesas = 0;
                checkboxesMesas.forEach(cb => {
                    if (cb.checked) {
                        totalMesas += parseFloat(cb.getAttribute('data-precio'));
                    }
                });
                totalCostoMesasElement.textContent = totalMesas.toFixed(2); // Mostrar el total con dos decimales
            });
        });
    </script>
</body>
</html>
