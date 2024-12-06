<?php
// Inicio de sesión y conexión a la base de datos
session_start();

// Conexión a la base de datos
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'hotel_db';
$conn = new mysqli($host, $user, $pass, $dbname);

// Función para calcular los días de estadía
function calcularDiasEstadia($check_in, $check_out) {
    $fecha1 = new DateTime($check_in);
    $fecha2 = new DateTime($check_out);
    return $fecha2->diff($fecha1)->days;
}

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

// Array de descripciones vacío para llenar
$descripciones = [];

// Recorrer los cuartos seleccionados y obtener la descripción de cada cuarto
foreach ($cuartos_seleccionados as $cuarto) {
    $id_cuarto = $cuarto['id_cuarto'];

    // Realizar la consulta para obtener la descripción
    $sql = "SELECT descripcion FROM cuartos WHERE id_cuarto = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_cuarto); // Vincular el id_cuarto como parámetro entero
    $stmt->execute();
    $result = $stmt->get_result();

    // Verificar si se encontró la descripción
    if ($row = $result->fetch_assoc()) {
        $descripciones[] = $row['descripcion']; // Añadir la descripción al array
    } else {
        $descripciones[] = 'Descripción no disponible'; // Si no hay descripción, usar un texto predeterminado
    }
}

// Cálculo de días de estadía
$dias_estadia = calcularDiasEstadia($check_in, $check_out);

// Configuración específica para la promoción de Navidad (ID 1)
$promocion_id = 1;
$_SESSION['fechas'] = [
    'checkin' => '2024-12-24',
    'checkout' => '2024-12-26'
];
$_SESSION['habitaciones'] = [
    [
        'id' => 201, // ID de la habitación predefinida
        'descripcion' => 'Habitación para 2 adultos con desayunos incluidos',
        'precio' => 120.00 // Precio por noche
    ]
];
$_SESSION['promocion'] = [
    'id_promocion' => $promocion_id,
    'descuento' => 0.10 // 10% de descuento
];
$_SESSION['total_sin_descuento'] = 240.00; // Total antes del descuento (120.00 * 2 noches)
$_SESSION['total'] = $_SESSION['total_sin_descuento'] - ($_SESSION['total_sin_descuento'] * $_SESSION['promocion']['descuento']); // Total con descuento

// Mostrar la página de promoción con los detalles configurados
$fechas = $_SESSION['fechas'];
$habitaciones = $_SESSION['habitaciones'];
$promocion = $_SESSION['promocion'];
$total_sin_descuento = $_SESSION['total_sin_descuento'];
$total = $_SESSION['total'];

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
    $total_general = isset($_SESSION['total_general']) ? $_SESSION['total_general'] : 0;

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
    $id_hotel = $location;

    // Insertar la reserva en la tabla `reservas` con el total de pago
    $query_reserva = "
        INSERT INTO reservas (fecha_reserva, fecha_checkin, fecha_checkout, total_pago, id_cliente, id_promocion, id_hotel)
        VALUES ('$fecha_reserva', '$check_in', '$check_out', $total_general, $id_cliente, " . ($id_promocion ? $id_promocion : 'NULL') . ", $id_hotel)
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

    // Agregar los datos de reserva de mesa (si existen) a la base de datos
    foreach ($reserva_mesa as $mesa) {
        $id_mesa = $mesa['id_mesa'];
        $fecha_reserva_mesa = $mesa['fecha_reserva'];
        $tipo_reserva = $mesa['tipo_reserva'];

        $query_reserva_mesa = "
            INSERT INTO reserva_restaurante (id_reserva, id_mesa, fecha_reserva, tipo_reserva)
            VALUES ($id_reserva, $id_mesa, '$fecha_reserva_mesa', '$tipo_reserva')
        ";

        if (!$conn->query($query_reserva_mesa)) {
            die("Error al guardar la reserva de mesa: " . $conn->error);
        }
    }

    if (isset($_POST['nombre_acompanhante'])) {
        // Preparamos la consulta SQL con placeholders (?)
        $stmt = $conn->prepare("INSERT INTO acompañantes (nombre, apellido, tipo_documento, nro_documento, celular, pais, correo, id_reserva)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        // Verificamos si la preparación fue exitosa
        if ($stmt === false) {
            die("Error en la preparación de la consulta: " . $conn->error);
        }
    
        // Recorremos los datos de los acompañantes y los insertamos
        foreach ($_POST['nombre_acompanhante'] as $index => $acompanhantes) {
            foreach ($acompanhantes as $i => $acompanhante) {
                // Asignamos los valores a las variables
                $nombre_acompanhante = $_POST['nombre_acompanhante'][$index][$i];
                $apellido_acompanhante = $_POST['apellido_acompanhante'][$index][$i];
                $tipo_documento = $_POST['tipo_documento_acompanhante'][$index][$i];
                $nro_documento = $_POST['nro_documento_acompanhante'][$index][$i];
                $celular = $_POST['celular_acompanhante'][$index][$i];
                $pais = $_POST['pais_acompanhante'][$index][$i];
                $correo = $_POST['correo_acompanhante'][$index][$i];
    
                // Ejecutamos la consulta para insertar los datos
                $stmt->bind_param("sssssssi", $nombre_acompanhante, $apellido_acompanhante, $tipo_documento, $nro_documento, $celular, $pais, $correo, $id_reserva);
                $stmt->execute();
    
                // Verificamos si hubo error en la inserción
                if ($stmt->error) {
                    echo "Error al insertar los datos: " . $stmt->error;
                }
            }
        }
    
        // Cerramos la declaración preparada
        $stmt->close();
    }    

    // Guardar el ID de reserva en sesión
    $_SESSION['id_reserva'] = $id_reserva;
    $_SESSION['mostrar_modal'] = true; // Agregar esta línea para indicar que se debe mostrar el modal

    // Redirigir al mismo formulario o a la página de confirmación
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
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
    <!-- Agregar FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
            border-radius: 0px;
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
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        /* Contenedor del checkbox y el texto */
        .form-check {
            display: flex;
            justify-content: flex-start;  /* Alinea todo el contenido a la izquierda */
            margin: 0;  /* Asegura que no haya márgenes automáticos que puedan estar centrando el contenido */
        }

        /* Asegurarse de que el checkbox tenga un tamaño adecuado */
        .form-check-input {
            margin-right: 8px;  /* Espacio entre el checkbox y el texto */
            transform: scale(1);  /* Asegura que el checkbox no se agrande */
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
            if (isset($_SESSION['check_in'], $_SESSION['check_out'], $_SESSION['cuartos_seleccionados'], $_SESSION['adultos'], $_SESSION['ninos'], $habitaciones_info)): 
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
                            <p style='margin: 0; font-size: 16px; color: #D69C4F;'><strong>Habitación " . ($index + 1) . ":<br> $descripcion_cuarto</strong></p>
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

                // Mostrar detalles de las mesas seleccionadas
                if (isset($_SESSION['reserva_mesa']) && !empty($_SESSION['reserva_mesa'])):
                    // Iterar sobre las mesas en la sesión
                    foreach ($_SESSION['reserva_mesa'] as $index => $mesa):
                        // Asegúrate de que cada mesa tenga los valores necesarios
                        if (isset($mesa['id_mesa'], $mesa['fecha_reserva'], $mesa['tipo_reserva'])):
                            $id_mesa = $mesa['id_mesa']; // ID de la mesa
                            $fecha_reserva = $mesa['fecha_reserva']; // Fecha de la reserva
                            $tipo_reserva = $mesa['tipo_reserva']; // Tipo de reserva
                            $precio_reservam = $mesa['precio_reservam']; // Costo de reserva

                            // Mostrar los detalles de la mesa
                            echo "<div style='border-bottom: 1px solid #ddd; padding: 10px 0; display: flex; justify-content: space-between; align-items: center;'>
                                    <div>
                                        <p style='margin: 0; font-size: 16px; color: #D69C4F;'><strong>Mesa " . ($index + 1) . ":</strong></p>
                                        <p style='margin: 0; font-size: 14px;'>ID de la mesa: <strong>$id_mesa</strong></p>
                                        <p style='margin: 0; font-size: 14px;'>Fecha de reserva: <strong>$fecha_reserva</strong></p>
                                        <p style='margin: 0; font-size: 14px;'>Tipo de reserva: <strong>$tipo_reserva</strong></p>
                                        <p style='margin: 0; font-size: 14px;'>Total por mesas: <strong>S/ " . number_format($precio_reservam, 2) . "</strong></p>
                                    </div>
                                </div>";
                                $total_general += $precio_reservam; // Sumar el costo de las mesas al total general
                        else:
                            echo "<p>Faltan datos para la mesa " . ($index + 1) . ".</p>";
                        endif;
                    endforeach;
                else:
                    echo "<p>No se han reservado mesas.</p>";
                endif;

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

            <!-- Checkbox "Datos del huésped" -->
            <div class="mb-0">
                <label style='margin: 0; font-size: 16px; font-weight: bold;' for="usar_datos_huesped">Datos del huésped</label>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="" id="usar_datos_huesped" checked>
                    <label class="form-check-label" for="usar_datos_huesped">
                        Registrar a los acompañantes en el hotel
                    </label>
                </div>
            </div>

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

                <div id="acompanhantes-container"></div>

            </form>
        </div>


        <script>
            // Obtener las descripciones desde PHP
            let descripciones = <?php echo json_encode($descripciones); ?>;
            document.getElementById('usar_datos_huesped').addEventListener('change', function() {
                let container = document.getElementById('acompanhantes-container');
                container.innerHTML = ''; // Limpiar el contenedor de acompañantes cada vez que se cambia el estado del checkbox
                
                if (!this.checked) {
                    // Si el checkbox está desmarcado, agregar formularios para los acompañantes
                    let habitaciones = <?php echo json_encode($_SESSION['cuartos_seleccionados']); ?>;  // Obtener las habitaciones desde PHP
                    let adultos = <?php echo json_encode($_SESSION['adultos']); ?>;  // Obtener la cantidad de adultos desde PHP
                    let ninos = <?php echo json_encode($_SESSION['ninos']); ?>;  // Obtener la cantidad de niños desde PHP

                    // Recorrer las habitaciones y agregar formularios de acompañantes
                    habitaciones.forEach((habitacion, index) => {
                        // Calcular el total de acompañantes (adultos + niños)
                        let total_acompanhantes = (parseInt(adultos[index]) || 0) + (parseInt(ninos[index]) || 0);
                        
                        if (index == 0) {
                            total_acompanhantes = total_acompanhantes - 1;
                        }

                        // Crear el contenedor de la habitación con el ícono
                        let habitacionContenedor = document.createElement('div');
                        habitacionContenedor.classList.add('habitacion-contenedor');
                        habitacionContenedor.setAttribute('style', 'cursor: pointer; margin-bottom: 15px;');

                        // Crear el título de la habitación con el ícono
                        let tituloHabitacion = document.createElement('div');
                        tituloHabitacion.classList.add('habitacion-titulo');
                        tituloHabitacion.setAttribute('style', 'display: flex; justify-content: flex-start; align-items: center;');
                        tituloHabitacion.innerHTML = `
                            <i class="fa fa-chevron-down" aria-hidden="true" style="color: #D69C4F;"></i>
                            <h4 style="margin: 0; margin-left: 7px; margin-bottom: 7px; font-size: 16px; font-weight: bold; color: #D69C4F;">
                                Habitación ${index + 1}: ${descripciones[index] || 'Descripción no disponible'}
                            </h4>
                        `;
                        habitacionContenedor.appendChild(tituloHabitacion);  // Añadir título e ícono al contenedor

                        // Crear un contenedor oculto para los formularios de acompañantes
                        let formulariosContainer = document.createElement('div');
                        formulariosContainer.classList.add('formularios-container');
                        formulariosContainer.style.display = 'none'; // Ocultar los formularios por defecto

                        // Crear los formularios de acompañantes
                        for (let i = 0; i < total_acompanhantes; i++) {
                            let div = document.createElement('div');
                            div.classList.add('acompanhante-form');
                            div.innerHTML = `
                                <h4 style='margin: 0; font-size: 16px; font-weight: bold; color: #D69C4F;'>Acompañante ${i + 1}</h4>
                                <div class="row mb-2">
                                    <div class="col-6">
                                        <label for="nombre_acompanhante_${index}_${i}" class="form-label">Nombre:</label>
                                        <input type="text" class="form-control" id="nombre_acompanhante_${index}_${i}" name="nombre_acompanhante[${index}][${i}]" placeholder="Ingresa el nombre" required>
                                    </div>
                                    <div class="col-6">
                                        <label for="apellido_acompanhante_${index}_${i}" class="form-label">Apellido:</label>
                                        <input type="text" class="form-control" id="apellido_acompanhante_${index}_${i}" name="apellido_acompanhante[${index}][${i}]" placeholder="Ingresa el apellido" required>
                                    </div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-6">
                                        <label for="tipo_documento_acompanhante_${index}_${i}" class="form-label">Tipo de Documento:</label>
                                        <select class="form-select" id="tipo_documento_acompanhante_${index}_${i}" name="tipo_documento_acompanhante[${index}][${i}]" required>
                                            <option value="DNI">DNI</option>
                                            <option value="Pasaporte">Pasaporte</option>
                                            <option value="Cédula">Cédula</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label for="nro_documento_acompanhante_${index}_${i}" class="form-label">Número de Documento:</label>
                                        <input type="text" class="form-control" id="nro_documento_acompanhante_${index}_${i}" name="nro_documento_acompanhante[${index}][${i}]" placeholder="Número de documento" required>
                                    </div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-6">
                                        <label for="celular_acompanhante_${index}_${i}" class="form-label">Celular:</label>
                                        <input type="text" class="form-control" id="celular_acompanhante_${index}_${i}" name="celular_acompanhante[${index}][${i}]" placeholder="Número de celular" required>
                                    </div>
                                    <div class="col-6">
                                        <label for="pais_acompanhante_${index}_${i}" class="form-label">País:</label>
                                        <select class="form-select" id="pais_acompanhante_${index}_${i}" name="pais_acompanhante[${index}][${i}]" required>
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
                                        <label for="correo_acompanhante_${index}_${i}" class="form-label">Correo Electrónico:</label>
                                        <input type="email" class="form-control" id="correo_acompanhante_${index}_${i}" name="correo_acompanhante[${index}][${i}]" placeholder="Correo electrónico" required>
                                    </div>
                                </div>
                            `;
                            formulariosContainer.appendChild(div);  // Agregar el formulario de acompañante al contenedor de formularios
                        }

                        habitacionContenedor.appendChild(formulariosContainer); // Añadir los formularios al contenedor de la habitación
                        container.appendChild(habitacionContenedor);  // Añadir el contenedor de la habitación al contenedor principal

                        // Añadir la funcionalidad para mostrar/ocultar los formularios al hacer clic
                        tituloHabitacion.addEventListener('click', function() {
                            let icono = tituloHabitacion.querySelector('i');
                            if (formulariosContainer.style.display === 'none') {
                                formulariosContainer.style.display = 'block';
                                icono.classList.remove('fa-chevron-down');
                                icono.classList.add('fa-chevron-up');
                            } else {
                                formulariosContainer.style.display = 'none';
                                icono.classList.remove('fa-chevron-up');
                                icono.classList.add('fa-chevron-down');
                            }
                        });
                    });
                }
            });
        </script>


        <!-- Información de pago con resumen de reserva -->
        <div class="section payment-info" style="font-family: Arial, sans-serif; padding: 15px; background-color: #f9f9f9; border-radius: 8px;">
            <h3 style="text-align: center; font-size: 22px; color: #333;">Resumen de la Reserva</h3>

            <?php 
            $total_general = 0; // Inicializar el total general

            // Mostrar el resumen de habitaciones si existen
            if (!empty($cuartos_seleccionados)): 
                $total_habitaciones = count($cuartos_seleccionados);
                
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
                    echo "<p style='font-size: 14px; color: #555; text-align: center;'>Habitación " . ($index + 1) . " (Nro: $numero_cuarto) x <strong> $dias_estadia noches = S/ " . number_format($precio_ajustado * $dias_estadia, 2) . "</strong></p>";

                    // Sumar al total general
                    $subtotal = $precio_ajustado * $dias_estadia;
                    $total_general += $subtotal;
                endforeach;
            else:
            endif;

            // Mostrar el resumen de mesas si existen reservas
            if (!empty($reserva_mesa)): 
            // echo "<h4 style='font-size: 18px; color: #333; text-align: center; margin-top: 20px;'>Mesas Reservadas</h4>";
                foreach ($reserva_mesa as $index => $mesa) {
                    $tipo_mesa = $mesa['tipo_reserva'];
                    $precio_reservam = $mesa['precio_reservam'];

                    echo "<p style='font-size: 14px; color: #555; text-align: center;'>Mesa " . ($index + 1) . " (Tipo: $tipo_mesa) = <strong>S/ "  . number_format($precio_reservam, 2) ."</strong></p>";

                    $total_general += $precio_reservam;
                }
            else:
            endif;

            // Mostrar el total general
            echo "<p style='font-size: 18px; font-weight: bold; color: #333; text-align: center; margin-top: 20px;'><strong>Total a pagar:</strong> S/ " . number_format($total_general, 2) . "</p>";
            $_SESSION['total_general'] = $total_general;
            ?>
            
            <!-- Botón para pagar -->
            <div style="text-align: center; margin-top: 20px;">
                <button type="submit" form="formulario" class="btn btn-warning">Pagar</button>
            </div>
        </div>

        <!-- Modal de Confirmación -->
        <div id="confirmacionModal" class="modal" style="display: none; position: fixed; z-index: 1050; left: 0; top: 0; width: 100%; height: 100%; overflow: hidden; background-color: rgba(0, 0, 0, 0.7); justify-content: center; align-items: center;">
            <div style="margin: auto; padding: 30px; width: 40%; background: linear-gradient(145deg, #ffffff, #f3f3f3); border-radius: 15px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); text-align: center;">
                <!-- Mensaje de Confirmación -->
                <h3 style="font-size: 24px; color: #333; font-weight: bold; margin-bottom: 20px;">¡Reserva Confirmada!</h3>

                <!-- Ícono de check -->
                <div style="width: 80px; height: 80px; margin: 0 auto 20px; background-color: #28a745; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="white" viewBox="0 0 24 24" width="50px" height="50px">
                        <path d="M9.00002 16.2L4.80002 12L3.40002 13.4L9.00002 19L21 7.00001L19.6 5.60001L9.00002 16.2Z"/>
                    </svg>
                </div>
                <!-- Mensaje de Confirmación -->
                <p style="font-size: 18px; color: #666; margin-bottom: 15px;">Tu reserva ha sido registrada exitosamente.</p>
                <p style="font-size: 20px; font-weight: bold; color: #333; margin-bottom: 25px;">ID de la Reserva: <span id="idReservaModal" style="color: #007bff;">
                    <?php echo isset($_SESSION['id_reserva']) ? $_SESSION['id_reserva'] : 'No disponible'; ?>
                </span></p>
            
                <!-- Opciones -->
                <h4 style="color: #444; font-size: 18px; font-weight: bold; margin-bottom: 15px;">¿Desea ver tu factura electrónica?</h4>
                <div style="margin-top: 20px; display: flex; justify-content: center; gap: 15px;">
                    <button onclick="window.location.href='ver_factura.php';"  
                        style="padding: 12px 25px; font-size: 16px; background-color: #D69C4F; color: #fff; border: none; border-radius: 8px; cursor: pointer; transition: all 0.3s ease;">
                        Descargar Factura
                    </button>
                    <button onclick="location.href='index.php';" 
                        style="padding: 12px 25px; font-size: 16px; background-color: #6c757d; color: #fff; border: none; border-radius: 8px; cursor: pointer; transition: all 0.3s ease;">
                        Regresar a Inicio
                    </button>
                </div>
            </div>
        </div>

        <script>
            // Mostrar el modal si la variable de sesión 'mostrar_modal' está activa
            <?php if (isset($_SESSION['mostrar_modal']) && $_SESSION['mostrar_modal'] === true): ?>
                document.getElementById('confirmacionModal').style.display = 'flex';
                <?php unset($_SESSION['mostrar_modal']); ?> // Limpiar la variable para evitar que se muestre en el futuro
            <?php endif; ?>
        </script>
    </div>
</body>
</html> 
