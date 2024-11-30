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

        // Obtener el precio base de la habitación
        $query_precio = "SELECT precio_base FROM cuartos WHERE id_cuarto = $id_cuarto";
        $result_precio = $conn->query($query_precio);
        if ($result_precio && $result_precio->num_rows > 0) {
            $precio_base = $result_precio->fetch_assoc()['precio_base'];

            // Calcular el precio ajustado
            $precio_ajustado = ($tipo_pago === 'web') ? $precio_base * 0.7 : $precio_base;

            // Guardar la información en la sesión
            $_SESSION['cuartos_seleccionados'][] = [
                'id_cuarto' => $id_cuarto,
                'tipo_pago' => $tipo_pago,
                'precio' => $precio_ajustado,
            ];
        }

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
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Lato', 'Roboto', sans-serif !important;
            background-color: #f5f5f5;
        }
        header {
            background-color: #000;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 0;
        }
        .header-container {
            width: 100%;
            max-width: 1140px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 15px;
            height: 69px;
        }
        .header-left img {
            height: 50px;
        }
        .header-right nav {
            display: flex;
            gap: 20px;
        }
        .header-right nav a {
            color: white;
            text-decoration: none;
            font-size: 16px;
            font-weight: bold;
        }
        .header-right nav a:hover {
            text-decoration: underline;
        }

        .progress-bar-container {
            padding: 11px 15px;
            max-width: 1140px;
            margin-left: auto;
            margin-right: auto;
        }
        .progress-bar-header {
            font-size: 16px;
            font-weight: bold;
            color: #000;
            margin-bottom: 10px;
        }
        .progress-bar {
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
            margin-top: 20px;
        }
        .step {
            text-align: center;
            flex: 1;
        }
        .step-circle {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            color: black;
            font-weight: bold;
        }
        .step-circle.active {
            background-color: #D69C4F;
        }
        .step-line {
            flex: 1;
            height: 2px;
            background-color: #ccc;
            margin: auto;
        }
        .step-line.active {
            background-color: #D69C4F;
        }

        .container {
            margin: 20px auto;
            max-width: 1200px;
            padding: 0 40px 40px 40px;
        }
        h2 {
            font-weight: bold;
            margin-bottom: 20px;
        }
        .form-section {
            background-color: transparent;
            padding: 20px;
            margin-bottom: 30px;
            border: 1px solid #ccc;
            border-radius: 8px;
        }
        .form-label {
            font-weight: bold;
        }

        /* Estilo del Select de Monedas */
        .form-select {
            border: none;
            border-bottom: 2px solid #000;
            background-color: transparent;
            box-shadow: none;
            padding-left: 0;
            font-weight: bold;
        }

        .form-select:focus {
            outline: none;
            border-bottom: 2px solid #D69C4F;
        }

        /* Estilo del contenedor de cada habitación */
        .habitacion-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .habitacion-info {
            flex-basis: 70%;
        }

        .habitacion-info img {
            height: px;
            width: auto;
        }

        .habitacion-options {
            flex-basis: 28%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: flex-start;
        }

        .habitacion-options p {
            margin: 5px 0;
        }

        .btn-warning {
            background-color: #D69C4F;
            color: white;
            font-weight: bold;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            margin-top: 10px;
        }

        .btn-warning:hover {
            background-color: #c88942;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="header-container">
            <div class="header-left">
                <img src="images/logo.png" alt="Logo del Hotel">
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
        <h1 class="progress-bar-header">Casa Andina Standard Talara</h1>
        <div class="progress-bar">
            <div class="step">
                <div class="step-circle active" id="circle-1">1</div>
                <div>Huéspedes & Habitaciones</div>
            </div>
            <div class="step-line active" id="line-1"></div>
            <div class="step">
                <div class="step-circle active" id="circle-2">2</div>
                <div>Selecciona Habitaciones y Tarifa</div>
            </div>
            <div class="step-line" id="line-2"></div>
            <div class="step">
                <div class="step-circle" id="circle-3">3</div>
                <div>Fechas de Reserva Restaurante</div>
            </div>
            <div class="step-line" id="line-3"></div>
            <div class="step">
                <div class="step-circle" id="circle-4">4</div>
                <div>Monto Total</div>
            </div>
        </div>
    </div>

    <!-- Contenido de la Página -->
    <div class="container">
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
            <h2>Habitaciones:</h2>
            <?php if (!empty($cuartos_disponibles)): ?>
                <?php foreach ($cuartos_disponibles as $cuarto): ?>
                    <div class="form-section">
                        <div class="habitacion-container">
                            <div class="habitacion-info">
                                <h3>Habitación #<?php echo htmlspecialchars($cuarto['numero']); ?></h3>
                                <img src="images/habitacion1.jpg" alt="Imagen de la Habitación">
                                <p>Piso: <?php echo htmlspecialchars($cuarto['piso']); ?></p>
                                <p>Capacidad Adultos: <?php echo htmlspecialchars($cuarto['capacidad_adultos']); ?></p>
                                <p>Capacidad Niños: <?php echo htmlspecialchars($cuarto['capacidad_niños']); ?></p>
                                <p>Precio Base: <?php echo htmlspecialchars($cuarto['precio_base']); ?> USD</p>
                            </div>
                            <div class="habitacion-options">
                                <form method="POST">
                                    <input type="hidden" name="id_cuarto" value="<?php echo htmlspecialchars($cuarto['id_cuarto']); ?>">
                                    <label class="form-label">Tipo de Pago:</label>
                                    <select name="tipo_pago" class="form-select" required>
                                        <option value="hotel">Pagar en Hotel</option>
                                        <option value="web">Pagar por Web</option>
                                    </select>
                                    <p>Precio: <?php echo htmlspecialchars($cuarto['precio_base']); ?> USD</p>
                                    <button type="submit" name="reservar" class="btn-warning">Reservar</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No hay habitaciones disponibles para las fechas y capacidades seleccionadas.</p>
            <?php endif; ?>
        <?php else: ?>
            <p>Ya has seleccionado todas las habitaciones necesarias.</p>
            <a href="pagoh.php" class="btn btn-warning">Continuar al Pago</a>
        <?php endif; ?>
    </div>

    <?php $conn->close(); ?>
</body>
</html>
