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

// Variables de sesión
$check_in = $_SESSION['check_in'];
$check_out = $_SESSION['check_out'];
$cuartos_seleccionados = $_SESSION['cuartos_seleccionados']; // Array con habitaciones seleccionadas

// Inicializar variables para las fechas
$fecha_reserva = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['consultar_mesas'])) {
    $fecha_reserva = $_POST['fecha_reserva'];
    $tipo_reserva = $_POST['tipo_reserva'];  // Tipo de reserva: desayuno, almuerzo, cena
    $_SESSION['fecha_reserva_mesas'] = $fecha_reserva;
    
    // Buscar mesas que ya están reservadas para esa fecha y tipo de reserva
    $sql_mesas_reservadas = "SELECT id_mesa FROM reserva_restaurante WHERE fecha_reserva = ? AND tipo_reserva = ?";
    $stmt_mesas_reservadas = $conn->prepare($sql_mesas_reservadas);
    $stmt_mesas_reservadas->bind_param("ss", $fecha_reserva, $tipo_reserva);
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
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Reservar mesas
    if (isset($_POST['reservar_mesas'])) {
        // Verifica si se están seleccionando mesas
        $mesas_seleccionadas = $_POST['mesas'] ?? [];
        $id_reserva = $_SESSION['id_reserva']; // Asegúrate de que el ID de la reserva esté en la sesión
        $tipo_reserva = $_POST['tipo_reserva'];  // Obtener el tipo de reserva desde el formulario

        // Inicializar el costo total de las mesas
        $total_costo_mesas = 0;

        // Si se están seleccionando varias mesas
        if (count($mesas_seleccionadas) > 0) {
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

            // Guardar las reservas en la tabla `reserva_restaurante` (para cada mesa seleccionada)
            foreach ($mesas_seleccionadas as $id_mesa) {
                $sql_insert_reserva_mesa = "INSERT INTO reserva_restaurante (id_mesa, id_reserva, fecha_reserva, tipo_reserva) VALUES (?, ?, ?, ?)";
                $stmt_insert_reserva_mesa = $conn->prepare($sql_insert_reserva_mesa);
                $stmt_insert_reserva_mesa->bind_param("iiss", $id_mesa, $id_reserva, $_SESSION['fecha_reserva_mesas'], $tipo_reserva);
                $stmt_insert_reserva_mesa->execute();
            }

            // Guardar el costo total en la sesión
            $_SESSION['costo_total_mesas'] = $total_costo_mesas;
        }
        // Si solo se está reservando una mesa individual
        else if (isset($_POST['id_mesa'])) {
            $id_mesa = $_POST['id_mesa'];  // El id de la mesa seleccionada
            $fecha_reserva = $_POST['fecha_reserva'];  // La fecha de reserva seleccionada
            $tipo_reserva = $_POST['tipo_reserva'];  // El tipo de reserva (desayuno, almuerzo, cena)

            // Obtener el precio de la mesa
            $sql_precio_mesa = "SELECT precio_reservam FROM mesas WHERE id_mesa = ?";
            $stmt_precio_mesa = $conn->prepare($sql_precio_mesa);
            $stmt_precio_mesa->bind_param("i", $id_mesa);
            $stmt_precio_mesa->execute();
            $result_precio_mesa = $stmt_precio_mesa->get_result();
            $row_precio_mesa = $result_precio_mesa->fetch_assoc();
            $total_costo_mesas = $row_precio_mesa['precio_reservam'];

            // Guardar la reserva de la mesa en la tabla `reserva_restaurante`
            $sql_insert_reserva_mesa = "INSERT INTO reserva_restaurante (id_mesa, id_reserva, fecha_reserva, tipo_reserva) VALUES (?, ?, ?, ?)";
            $stmt_insert_reserva_mesa = $conn->prepare($sql_insert_reserva_mesa);
            $stmt_insert_reserva_mesa->bind_param("iiss", $id_mesa, $id_reserva, $fecha_reserva, $tipo_reserva);
            $stmt_insert_reserva_mesa->execute();

            // Guardar el costo total en la sesión
            $_SESSION['costo_total_mesas'] = $total_costo_mesas;
        }

        // Redirigir a la página de resumen después de la reserva
        header("Location: reserva_restaurante.php");
        exit();
    }

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
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
<body>

    <!-- Header -->
    <header>
        <div class="header-container">
            <div class="header-left">
                <img src="images/logo.png" alt="Logo">
            </div>
            <div class="header-right">
                <nav>
                    <a href="#">OFERTAS</a>
                    <a href="#">DESTINOS</a>
                    <a href="#">LIFE</a>
                    <a href="#">RESTAURANTES</a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Barra de Progreso -->
    <div class="progress-bar-container">
        <div class="progress-bar">
            <div class="step">
                <div class="step-circle active" id="circle-1">1</div>
                <div>Huéspedes & Habitaciones</div>
            </div>
            <div class="step-line active" id="line-1"></div>
            <div class="step">
                <div class="step-circle active" id="circle-2">2</div>
                <div>Selecciona Habitaciones & Tarifa</div>
            </div>
            <div class="step-line active active" id="line-2"></div>
            <div class="step">
                <div class="step-circle active" id="circle-3">3</div>
                <div>Fechas de Reserva Restaurante</div>
            </div>
            <div class="step-line" id="line-3"></div>
            <div class="step">
                <div class="step-circle" id="circle-4">4</div>
                <div>Monto Total</div>
            </div>
        </div>
    </div>

    <!-- Formulario de consulta de disponibilidad -->
    <div class="container">
        <div class="form-section" id="seccion-1">
            <h2 class="text-center">Reserva de Mesas</h2>
            <form action="reserva_restaurante.php" method="POST">
                <div class="col-11">
                    <div class="reserva-container row align-items-center">
                        <div class="col-4 text-start">
                            <label for="fecha_reserva" class="form-label">Fecha de la Reserva:</label><br>
                            <!-- Restringir fechas con min y max -->
                            <input type="date" name="fecha_reserva" id="fecha_reserva" class="forms1" required 
                                value="<?php echo htmlspecialchars($fecha_reserva); ?>" 
                                min="<?php echo htmlspecialchars($check_in); ?>" 
                                max="<?php echo htmlspecialchars($check_out); ?>"><br>
                        </div>
                        <div class="col-4 text-start">
                            <label for="tipo_reserva" class="form-label">Horario:</label>
                            <select class="forms1" id="tipo_reserva" name="tipo_reserva" required>
                                <option value="desayuno">Desayuno</option>
                                <option value="almuerzo">Almuerzo</option>
                                <option value="cena">Cena</option>
                            </select>
                        </div>
                        <div class="col-4 text-start">
                            <br><p class="form-label">Total: <p class="forms1"> S/. <span id="total-costo-mesas">0</span></p></p>
                        </div>
                    </div>
                </div>
                <button type="submit" name="consultar_mesas" class="btn btn-warning1" style="background-color: #D69C4F; color: white; font-weight: bold;">Consultar Disponibilidad</button>
                <button type="button" class="btn btn-warning1" onclick="window.location.href='pagoh.php'" style="background-color: #D69C4F; color: white; font-weight: bold;">Confirmar Reserva</button>            
                
            </form>
        </div>
        
            <!-- Mostrar mesas disponibles -->
            <?php if (!empty($mesas_disponibles)): ?>
                <div class="habitaciones-container">
                    <?php foreach ($mesas_disponibles as $mesa): ?>
                        <div class="habitacion-card">
                            <div class="habitacion-info">
                                <h3><?php echo htmlspecialchars($mesa['tipo']); ?> </h3>
                                <img src="images/mesas.jpg" alt="Imagen de la Mesa"> <!-- Aquí puedes poner una imagen específica para las mesas -->
                                <p><strong>Tipo de Mesa:</strong> <?php echo htmlspecialchars($mesa['tipo']); ?></p>
                                <p><strong>Precio Reserva:</strong> S/. <?php echo htmlspecialchars($mesa['precio_reservam']); ?></p>
                                <p>Disfruta de una experiencia gastronómica única con un ambiente acogedor en una <?php echo htmlspecialchars($mesa['descripcion']); ?>.</p>   
                            </div>
                            <div class="habitacion-options">
                                <form method="POST">
                                    <input type="hidden" name="id_mesa" value="<?php echo htmlspecialchars($mesa['id_mesa']); ?>">
                                    <input type="hidden" name="fecha_reserva" value="<?php echo htmlspecialchars($fecha_reserva); ?>"> <!-- Asegúrate de que esta variable esté correctamente asignada -->
                                    <input type="hidden" name="tipo_reserva" value="<?php echo htmlspecialchars($tipo_reserva); ?>"> <!-- Pasar el tipo de reserva (desayuno, almuerzo, cena) -->
                                    
                                    <!-- Contenedor de pago y precio final -->
                                    <div class="payment-container">
                                        <!-- Monto final con fondo negro -->
                                        <div class="final-price">
                                            <strong>S/ <?php echo htmlspecialchars($mesa['precio_reservam']); ?></strong>
                                        </div>

                                        <!-- Botón de Reservar -->
                                        <button type="submit" name="reservar_mesa" class="btn-warning">Reservar Mesa</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No hay mesas disponibles para la fecha seleccionada.</p>
            <?php endif; ?>

    </div>

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
