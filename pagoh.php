<?php
// Inicia la sesión
session_start();

// Función para calcular los días de estadía
function calcularDiasEstadia($check_in, $check_out) {
    $fecha1 = new DateTime($check_in);
    $fecha2 = new DateTime($check_out);
    return $fecha2->diff($fecha1)->days;
}

// Verifica si las fechas, habitaciones seleccionadas, adultos y niños están en sesión
if (!isset($_SESSION['check_in'], $_SESSION['check_out'], $_SESSION['cuartos_seleccionados'], $_SESSION['adultos'], $_SESSION['ninos'])) {
    header('Location: index.php'); // Redirigir al inicio si no hay datos
    exit();
}

// Variables de sesión
$check_in = $_SESSION['check_in'];
$check_out = $_SESSION['check_out'];
$cuartos_seleccionados = $_SESSION['cuartos_seleccionados']; // Array con habitaciones seleccionadas
$adultos = $_SESSION['adultos'];  // Adultos por habitación
$ninos = $_SESSION['ninos'];    // Niños por habitación

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

// Extraer los IDs de las habitaciones seleccionadas
$ids = implode(',', array_map(function($cuarto) {
    return intval($cuarto['id_cuarto']);
}, $cuartos_seleccionados));

// Consultar información de las habitaciones seleccionadas (ahora con "descripcion")
$habitaciones_info = [];
if (!empty($ids)) {
    $query = "SELECT id_cuarto, numero, precio_base, descripcion FROM cuartos WHERE id_cuarto IN ($ids)";
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

    // Calculo del total
    $total = 0;
    foreach ($cuartos_seleccionados as $cuarto) {
        $id_cuarto = $cuarto['id_cuarto'];
        $tipo_pago = $cuarto['tipo_pago'];

        // Obtener el número de la habitación, precio base y descripción desde la base de datos
        $precio_base = 0;
        $numero_cuarto = '';
        $descripcion_cuarto = ''; // Nueva variable para la descripción
        foreach ($habitaciones_info as $habitacion) {
            if ($habitacion['id_cuarto'] == $id_cuarto) {
                $precio_base = $habitacion['precio_base'];
                $numero_cuarto = $habitacion['numero']; // Guardar el número de la habitación
                $descripcion_cuarto = $habitacion['descripcion']; // Guardar la descripción
                break;
            }
        }

        // Calcular el subtotal, aplicando el descuento si es pago por web o por blackdays
        if ($tipo_pago === 'web') {
            $precio_ajustado = $precio_base * 0.7;
        } elseif ($tipo_pago === 'blackdays') {
            $precio_ajustado = $precio_base * 0.65;
        } else {
            $precio_ajustado = $precio_base;
        }

        // Sumar al total
        $total += $precio_ajustado * $dias_estadia;
    }

    // Insertar la reserva en la tabla `reservas` con el total de pago
    $query_reserva = "
        INSERT INTO reservas (fecha_reserva, fecha_checkin, fecha_checkout, total_pago, id_cliente, id_promocion, id_hotel)
        VALUES ('$fecha_reserva', '$check_in', '$check_out', $total_pago, $id_cliente, " . ($id_promocion ? $id_promocion : 'NULL') . ", $id_hotel)
    ";

    if (!$conn->query($query_reserva)) {
        die("Error al guardar la reserva: " . $conn->error);
    }
    $id_reserva = $conn->insert_id;

    // Insertar la relación entre la reserva y las habitaciones seleccionadas
    foreach ($cuartos_seleccionados as $cuarto) {
        $id_cuarto = intval($cuarto['id_cuarto']);
        $query_reservaporcuartos = "
            INSERT INTO reservaporcuartos (id_reserva, id_cuarto)
            VALUES ($id_reserva, $id_cuarto)
        ";
        if (!$conn->query($query_reservaporcuartos)) {
            die("Error al guardar la relación de reserva y cuartos: " . $conn->error);
        }
    }

    // Agregar los acompañantes a la base de datos si se envían
    if (isset($_POST['acompanantes'])) {
        $acompanantes = $_POST['acompanantes']; // Datos de los acompañantes

        foreach ($acompanantes as $acompanante) {
            $nombre_acompanante = $acompanante['nombre'];
            $apellido_acompanante = $acompanante['apellido'];
            $tipo_documento_acompanante = $acompanante['tipo_documento'];
            $nro_documento_acompanante = $acompanante['nro_documento'];
            $celular_acompanante = $acompanante['celular'];
            $pais_acompanante = $acompanante['pais'];
            $correo_acompanante = $acompanante['correo'];

            $query_acompanante = "
                INSERT INTO acompañantes (nombre, apellido, tipo_documento, nro_documento, celular, pais, correo, id_reserva)
                VALUES ('$nombre_acompanante', '$apellido_acompanante', '$tipo_documento_acompanante', '$nro_documento_acompanante', '$celular_acompanante', '$pais_acompanante', '$correo_acompanante', $id_reserva)
            ";

            if (!$conn->query($query_acompanante)) {
                die("Error al guardar el acompañante: " . $conn->error);
            }
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


        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .section {
            flex: 1;
            min-width: 300px;
        }
        .section h3 {
            font-size: 18px;
            margin-bottom: 10px;
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        .reservation-details,
        .guest-info,
        .payment-info {
            padding: 10px;
        }
        .reservation-details p,
        .guest-info label,
        .payment-info label {
            font-size: 14px;
            margin: 5px 0;
        }
        .guest-info input,
        .payment-info input {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .payment-info {
            text-align: center;
        }
        .payment-info img {
            width: 50px;
            margin: 5px;
        }
        .total {
            font-size: 16px;
            font-weight: bold;
            color: #ff8000;
            text-align: right;
            margin-top: 10px;
        }
        button {
            background-color: #ff8000;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 5px;
            font-size: 16px;
        }
        button:hover {
            background-color: #e06900;
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
            <div class="step-line active" id="line-2"></div>
            <div class="step">
                <div class="step-circle active" id="circle-3">3</div>
                <div>Fechas de Reserva Restaurante</div>
            </div>
            <div class="step-line active" id="line-3"></div>
            <div class="step">
                <div class="step-circle active" id="circle-4">4</div>
                <div>Monto Total</div>
            </div>
        </div>
    </div>

    <!-- Contenido -->
    <div class="container">
<!-- Detalles de la reserva -->
<div class="section reservation-details">
    <h3>Detalles de su Reserva</h3>

    <?php 
    // Verifica si la sesión tiene los datos necesarios
    if (isset($_SESSION['cuartos_seleccionados'], $_SESSION['adultos'], $_SESSION['ninos'], $habitaciones_info)): 
        $total_general = 0; // Variable para el total general de la reserva

        // Calcula los días de estadía
        $dias_estadia = calcularDiasEstadia($_SESSION['check_in'], $_SESSION['check_out']);

        // Asegúrate de que cuartos_seleccionados esté disponible
        foreach ($_SESSION['cuartos_seleccionados'] as $index => $cuarto): 
            $numero_cuarto = '';
            $precio_base = 0;
            $descripcion_cuarto = '';

            // Obtener el número de adultos y niños por cada cuarto desde la sesión
            $adultos = isset($_SESSION['adultos'][$index]) ? $_SESSION['adultos'][$index] : 0;
            $ninos = isset($_SESSION['ninos'][$index]) ? $_SESSION['ninos'][$index] : 0;

            // Buscar la habitación en las habitaciones_info
            foreach ($habitaciones_info as $habitacion) {
                if ($habitacion['id_cuarto'] == $cuarto['id_cuarto']) {
                    $numero_cuarto = $habitacion['numero'];
                    $precio_base = $habitacion['precio_base'];
                    $descripcion_cuarto = $habitacion['descripcion'];
                    break;
                }
            }

            // Calcular precio ajustado según el tipo de pago
            $tipo_pago = $cuarto['tipo_pago'];
            if ($tipo_pago === 'web') {
                $precio_ajustado = $precio_base * 0.7;
                $tipo_pago_desc = '<span style="color: #007bff;">Pagar por web</span>';
            } elseif ($tipo_pago === 'blackdays') {
                $precio_ajustado = $precio_base * 0.65;
                $tipo_pago_desc = '<span style="color: #28a745;">Black Days</span>';
            } else {
                $precio_ajustado = $precio_base;
                $tipo_pago_desc = '<span style="color: #6c757d;">Pagar en hotel</span>';
            }

            // Calcular el subtotal por cuarto
            $subtotal = $precio_ajustado * $dias_estadia;
            $total_general += $subtotal;

            // Mostrar detalles de cada habitación
            echo "<div style='border-bottom: 1px solid #ddd; padding: 10px 0; display: flex; justify-content: space-between; align-items: center;'>
                <div>
                    <p style='margin: 0; font-size: 16px;'><strong>Habitación " . ($index + 1) . ":</strong></p>
                    <p style='margin: 0; font-size: 14px;'>$dias_estadia noche(s) / $adultos adulto(s) / $ninos niño(s) / Nro $numero_cuarto</p>
                    <p style='margin: 0; font-size: 14px;'>Tipo de pago: <strong>$tipo_pago_desc</strong></p>";

                    // Mostrar precio normal tachado si aplica
                    if ($tipo_pago === 'web' || $tipo_pago === 'blackdays') {
                        echo "<p style='margin: 0; font-size: 14px;'>Precio normal: <strong style='text-decoration: line-through;'>S/ " . number_format($precio_base, 2) . "</strong></p>";
                    } else {
                        echo "<p style='margin: 0; font-size: 14px;'>Precio normal: <strong>S/ " . number_format($precio_base, 2) . "</strong></p>";
                    }

                    // Mostrar precio con descuento
                    echo "<p style='margin: 0; font-size: 14px;'>Precio con descuento: <strong>S/ " . number_format($precio_ajustado, 2) . "</strong></p>
                </div>
            </div>";
        endforeach;
        
// Mostrar el total general
echo "<div style='border-top: 1px solid #ddd; padding: 15px 0; display: flex; justify-content: flex-end; align-items: center;'>
        <div style='margin-right: 10px;'>Subtotal:</div>
        <div style='font-size: 20px; font-weight: bold;'>S/ " . number_format($total_general, 2) . "</div>
      </div>";


    endif;
    ?>
</div>







    <!-- Información del cliente -->
    <div class="section guest-info">
    <h3>Información del Cliente</h3>
    <form method="POST" id="formulario">
        <div class="row mb-2">
            <div class="col-6">
                <label for="nombre" class="form-label">Nombre:</label>
                <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Ingresa tu nombre" required>
            </div>
            <div class="col-6">
                <label for="apellido" class="form-label">Apellido:</label>
                <input type="text" class="form-control" id="apellido" name="apellido" placeholder="Ingresa tu apellido" required>
            </div>
        </div>
        <div class="row mb-2">
            <div class="col-6">
                <label for="tipo_documento" class="form-label">Tipo de Documento:</label>
                <select class="form-select" id="tipo_documento" name="tipo_documento" required>
                    <option value="DNI">DNI</option>
                    <option value="Pasaporte">Pasaporte</option>
                    <option value="Cédula">Cédula</option>
                </select>
            </div>
            <div class="col-6">
                <label for="nro_documento" class="form-label">Número de Documento:</label>
                <input type="text" class="form-control" id="nro_documento" name="nro_documento" placeholder="Número de documento" required>
            </div>
        </div>
        <div class="row mb-2">
            <div class="col-6">
                <label for="celular" class="form-label">Celular:</label>
                <input type="text" class="form-control" id="celular" name="celular" placeholder="Número de celular" required>
            </div>
            <div class="col-6">
                <label for="pais" class="form-label">País:</label>
                <select class="form-select" id="pais" name="pais" required>
                    <option value="Argentina">Argentina</option>
                    <option value="Brasil">Brasil</option>
                    <option value="Chile">Chile</option>
                    <option value="México">México</option>
                    <option value="Perú">Perú</option>
                    <!-- Agregar más países según sea necesario -->
                </select>
            </div>
        </div>
        <div class="row mb-2">
            <div class="col-12">
                <label for="correo" class="form-label">Correo Electrónico:</label>
                <input type="email" class="form-control" id="correo" name="correo" placeholder="Ingresa tu correo" required>
            </div>
        </div>
    </form>
</div>








<!-- Información de pago con resumen de reserva -->
<div class="section payment-info" style="font-family: Arial, sans-serif; padding: 15px; background-color: #f9f9f9; border-radius: 8px;">
    <h3 style="text-align: center; font-size: 22px; color: #333;">Resumen de la Reserva</h3>

    <?php if (!empty($cuartos_seleccionados)): ?>
        <?php 
        $total_general = 0;
        $total_habitaciones = count($cuartos_seleccionados);
        
        // Mostrar total de habitaciones
        echo "<p style='font-size: 16px; color: #555; text-align: center;'><strong>Total de habitaciones:</strong> $total_habitaciones</p>";

        foreach ($cuartos_seleccionados as $index => $cuarto): 
            $numero_cuarto = '';
            $precio_base = 0;
        
            // Obtener el precio de la habitación y el número
            foreach ($habitaciones_info as $habitacion) {
                if ($habitacion['id_cuarto'] == $cuarto['id_cuarto']) {
                    $numero_cuarto = $habitacion['numero'];
                    $precio_base = $habitacion['precio_base'];
                    break;
                }
            }
        
            // Calcular precio ajustado
            $tipo_pago = $cuarto['tipo_pago'];
            if ($tipo_pago === 'web') {
                $precio_ajustado = $precio_base * 0.7;
            } elseif ($tipo_pago === 'blackdays') {
                $precio_ajustado = $precio_base * 0.65;
            } else {
                $precio_ajustado = $precio_base;
            }
        
            // Mostrar el precio de cada habitación
            echo "<p style='font-size: 14px; color: #555; text-align: center;'>Habitación " . ($index + 1) . " (Nro: $numero_cuarto) - <strong>S/ " . number_format($precio_ajustado, 2) . "</strong></p>";

            // Sumar al total general
            $subtotal = $precio_ajustado * $dias_estadia;
            $total_general += $subtotal;
        endforeach;
        
        // Mostrar el total general centrado
        echo "<p style='font-size: 18px; font-weight: bold; color: #333; text-align: center; margin-top: 20px;'><strong>Total a pagar:</strong> S/ " . number_format($total_general, 2) . "</p>";
        ?>
    <?php else: ?>
        <p style="text-align: center; color: #888;">No hay información disponible sobre las habitaciones seleccionadas.</p>
    <?php endif; ?>

    <!-- Botón para pagar -->
    <div style="text-align: center; margin-top: 20px;">
        <button type="submit" form="formulario" class="btn btn-warning" style="padding: 10px 20px; font-size: 16px; border-radius: 5px; background-color: #ffc107; border: none; cursor: pointer;">Pagar</button>
    </div>
</div>




    </div>
</body>
</html> 
