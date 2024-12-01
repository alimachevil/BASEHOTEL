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
$cuartos_seleccionados = $_SESSION['cuartos_seleccionados']; // Array con habitaciones seleccionadas

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

// Consultar información de las habitaciones seleccionadas
$habitaciones_info = [];
if (!empty($ids)) {
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

    //calculo del total
    $total = 0;
foreach ($cuartos_seleccionados as $cuarto) {
    $id_cuarto = $cuarto['id_cuarto'];
    $tipo_pago = $cuarto['tipo_pago'];

    // Obtener el número de la habitación y el precio base desde la base de datos
    $precio_base = 0;
    $numero_cuarto = '';
    foreach ($habitaciones_info as $habitacion) {
        if ($habitacion['id_cuarto'] == $id_cuarto) {
            $precio_base = $habitacion['precio_base'];
            $numero_cuarto = $habitacion['numero']; // Guardar el número de la habitación
            break;
        }
    }

    // Calcular el subtotal, aplicando el descuento si es pago por web
    $precio_ajustado = ($tipo_pago === 'web') ? $precio_base * 0.7 : $precio_base;
    $subtotal = $precio_ajustado * $dias_estadia;
    $total += $subtotal;
}

    $query_reserva = "
    INSERT INTO reservas (fecha_reserva, fecha_checkin, fecha_checkout, total_pago, id_cliente, id_promocion, id_hotel)
    VALUES ('$fecha_reserva', '$check_in', '$check_out', $total, $id_cliente, " . ($id_promocion ? $id_promocion : 'NULL') . ", $id_hotel)
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
    <?php foreach ($cuartos_seleccionados as $cuarto): ?>
        <?php
        $id_cuarto = $cuarto['id_cuarto'];
        $tipo_pago = $cuarto['tipo_pago'];

        // Obtener el número de la habitación y el precio base
        $precio_base = 0;
        $numero_cuarto = '';
        foreach ($habitaciones_info as $habitacion) {
            if ($habitacion['id_cuarto'] == $id_cuarto) {
                $precio_base = $habitacion['precio_base'];
                $numero_cuarto = $habitacion['numero']; // Guardar el número de la habitación
                break;
            }
        }

        // Calcular el precio ajustado si el tipo de pago es "web"
        $precio_ajustado = ($tipo_pago === 'web') ? $precio_base * 0.7 : $precio_base;
        ?>

        <li>
            Habitación <?php echo htmlspecialchars($numero_cuarto); ?> - 
            Costo por noche: <?php echo htmlspecialchars($precio_base); ?> USD
            <?php if ($tipo_pago === 'web'): ?>
                (Precio con descuento: <?php echo htmlspecialchars($precio_ajustado); ?> USD)
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
</ul>



    <!-- Formulario para los datos del cliente -->
    <form action="pagoh.php" method="POST">
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
<select id="pais" name="pais" required>
    <option value="">Selecciona un país</option>
    <option value="Afganistán">Afganistán</option>
    <option value="Albania">Albania</option>
    <option value="Alemania">Alemania</option>
    <option value="Andorra">Andorra</option>
    <option value="Angola">Angola</option>
    <option value="Antigua y Barbuda">Antigua y Barbuda</option>
    <option value="Arabia Saudita">Arabia Saudita</option>
    <option value="Argelia">Argelia</option>
    <option value="Argentina">Argentina</option>
    <option value="Armenia">Armenia</option>
    <option value="Australia">Australia</option>
    <option value="Austria">Austria</option>
    <option value="Azerbaiyán">Azerbaiyán</option>
    <option value="Bahamas">Bahamas</option>
    <option value="Bangladesh">Bangladesh</option>
    <option value="Baréin">Baréin</option>
    <option value="Bélgica">Bélgica</option>
    <option value="Belice">Belice</option>
    <option value="Benín">Benín</option>
    <option value="Bielorrusia">Bielorrusia</option>
    <option value="Bolivia">Bolivia</option>
    <option value="Bosnia y Herzegovina">Bosnia y Herzegovina</option>
    <option value="Botsuana">Botsuana</option>
    <option value="Brasil">Brasil</option>
    <option value="Brunéi">Brunéi</option>
    <option value="Bulgaria">Bulgaria</option>
    <option value="Burkina Faso">Burkina Faso</option>
    <option value="Burundi">Burundi</option>
    <option value="Bután">Bután</option>
    <option value="Cabo Verde">Cabo Verde</option>
    <option value="Camboya">Camboya</option>
    <option value="Camerún">Camerún</option>
    <option value="Canadá">Canadá</option>
    <option value="Catar">Catar</option>
    <option value="Colombia">Colombia</option>
    <option value="Comoras">Comoras</option>
    <option value="Congo">Congo</option>
    <option value="Congo (República Democrática del)">Congo (República Democrática del)</option>
    <option value="Corea del Norte">Corea del Norte</option>
    <option value="Corea del Sur">Corea del Sur</option>
    <option value="Costa de Marfil">Costa de Marfil</option>
    <option value="Costa Rica">Costa Rica</option>
    <option value="Croacia">Croacia</option>
    <option value="Cuba">Cuba</option>
    <option value="Chipre">Chipre</option>
    <option value="Chequia">Chequia</option>
    <option value="Dinamarca">Dinamarca</option>
    <option value="Dominica">Dominica</option>
    <option value="Ecuador">Ecuador</option>
    <option value="Egipto">Egipto</option>
    <option value="El Salvador">El Salvador</option>
    <option value="Emiratos Árabes Unidos">Emiratos Árabes Unidos</option>
    <option value="Eslovaquia">Eslovaquia</option>
    <option value="Eslovenia">Eslovenia</option>
    <option value="España">España</option>
    <option value="Estados Unidos">Estados Unidos</option>
    <option value="Estonia">Estonia</option>
    <option value="Etiopía">Etiopía</option>
    <option value="Fiji">Fiji</option>
    <option value="Filipinas">Filipinas</option>
    <option value="Finlandia">Finlandia</option>
    <option value="Francia">Francia</option>
    <option value="Gabón">Gabón</option>
    <option value="Gambia">Gambia</option>
    <option value="Georgia">Georgia</option>
    <option value="Ghana">Ghana</option>
    <option value="Granada">Granada</option>
    <option value="Grecia">Grecia</option>
    <option value="Guatemala">Guatemala</option>
    <option value="Guinea">Guinea</option>
    <option value="Guinea Ecuatorial">Guinea Ecuatorial</option>
    <option value="Guinea-Bisáu">Guinea-Bisáu</option>
    <option value="Guyana">Guyana</option>
    <option value="Haití">Haití</option>
    <option value="Honduras">Honduras</option>
    <option value="Hungría">Hungría</option>
    <option value="India">India</option>
    <option value="Indonesia">Indonesia</option>
    <option value="Irak">Irak</option>
    <option value="Irlanda">Irlanda</option>
    <option value="Irán">Irán</option>
    <option value="Isla de Man">Isla de Man</option>
    <option value="Islandia">Islandia</option>
    <option value="Islas Marshall">Islas Marshall</option>
    <option value="Islas Salomón">Islas Salomón</option>
    <option value="Israel">Israel</option>
    <option value="Italia">Italia</option>
    <option value="Jamaica">Jamaica</option>
    <option value="Japón">Japón</option>
    <option value="Jordania">Jordania</option>
    <option value="Kazajistán">Kazajistán</option>
    <option value="Kenia">Kenia</option>
    <option value="Kirguistán">Kirguistán</option>
    <option value="Kiribati">Kiribati</option>
    <option value="Kuwait">Kuwait</option>
    <option value="Laos">Laos</option>
    <option value="Lesoto">Lesoto</option>
    <option value="Letonia">Letonia</option>
    <option value="Líbano">Líbano</option>
    <option value="Liberia">Liberia</option>
    <option value="Libia">Libia</option>
    <option value="Liechtenstein">Liechtenstein</option>
    <option value="Lituania">Lituania</option>
    <option value="Luxemburgo">Luxemburgo</option>
    <option value="Madagascar">Madagascar</option>
    <option value="Malasia">Malasia</option>
    <option value="Malaui">Malaui</option>
    <option value="Maldivas">Maldivas</option>
    <option value="Malí">Malí</option>
    <option value="Malta">Malta</option>
    <option value="Marruecos">Marruecos</option>
    <option value="Mauricio">Mauricio</option>
    <option value="Mauritania">Mauritania</option>
    <option value="México">México</option>
    <option value="Micronesia">Micronesia</option>
    <option value="Mónaco">Mónaco</option>
    <option value="Mongolia">Mongolia</option>
    <option value="Mozambique">Mozambique</option>
    <option value="Namibia">Namibia</option>
    <option value="Naurú">Naurú</option>
    <option value="Nepal">Nepal</option>
    <option value="Nicaragua">Nicaragua</option>
    <option value="Níger">Níger</option>
    <option value="Nigeria">Nigeria</option>
    <option value="Noruega">Noruega</option>
    <option value="Nueva Zelanda">Nueva Zelanda</option>
    <option value="Omán">Omán</option>
    <option value="Países Bajos">Países Bajos</option>
    <option value="Pakistán">Pakistán</option>
    <option value="Palaos">Palaos</option>
    <option value="Panamá">Panamá</option>
    <option value="Papúa Nueva Guinea">Papúa Nueva Guinea</option>
    <option value="Paraguay">Paraguay</option>
    <option value="Perú">Perú</option>
    <option value="Polonia">Polonia</option>
    <option value="Portugal">Portugal</option>
    <option value="Reino Unido">Reino Unido</option>
    <option value="República Checa">República Checa</option>
    <option value="República Dominicana">República Dominicana</option>
    <option value="Ruanda">Ruanda</option>
    <option value="Rumanía">Rumanía</option>
    <option value="Rusia">Rusia</option>
    <option value="Samoa">Samoa</option>
    <option value="San Cristóbal y Nieves">San Cristóbal y Nieves</option>
    <option value="San Marino">San Marino</option>
    <option value="Santo Tomé y Príncipe">Santo Tomé y Príncipe</option>
    <option value="Senegal">Senegal</option>
    <option value="Serbia">Serbia</option>
    <option value="Seychelles">Seychelles</option>
    <option value="Sierra Leona">Sierra Leona</option>
    <option value="Singapur">Singapur</option>
    <option value="Siria">Siria</option>
    <option value="Somalia">Somalia</option>
    <option value="Sri Lanka">Sri Lanka</option>
    <option value="Suazilandia">Suazilandia</option>
    <option value="Sudáfrica">Sudáfrica</option>
    <option value="Sudán">Sudán</option>
    <option value="Sudán del Sur">Sudán del Sur</option>
    <option value="Suecia">Suecia</option>
    <option value="Suiza">Suiza</option>
    <option value="Surinam">Surinam</option>
    <option value="Siria">Siria</option>
    <option value="Somalia">Somalia</option>
    <option value="Sri Lanka">Sri Lanka</option>
    <option value="Tailandia">Tailandia</option>
    <option value="Tanzania">Tanzania</option>
    <option value="Tayikistán">Tayikistán</option>
    <option value="Timor Oriental">Timor Oriental</option>
    <option value="Togo">Togo</option>
    <option value="Tonga">Tonga</option>
    <option value="Trinidad y Tobago">Trinidad y Tobago</option>
    <option value="Túnez">Túnez</option>
    <option value="Turkmenistán">Turkmenistán</option>
    <option value="Turquía">Turquía</option>
    <option value="Tuvalu">Tuvalu</option>
    <option value="Uganda">Uganda</option>
    <option value="Ucrania">Ucrania</option>
    <option value="Uruguay">Uruguay</option>
    <option value="Uzbekistán">Uzbekistán</option>
    <option value="Vanuatu">Vanuatu</option>
    <option value="Vaticano">Vaticano</option>
    <option value="Venezuela">Venezuela</option>
    <option value="Vietnam">Vietnam</option>
    <option value="Yemen">Yemen</option>
    <option value="Yibuti">Yibuti</option>
    <option value="Zambia">Zambia</option>
    <option value="Zimbabue">Zimbabue</option>
</select>

        <br><br>
        <!-- Resumen -->
        <h2>Resumen:</h2>
<?php
$total = 0;
foreach ($cuartos_seleccionados as $cuarto) {
    $id_cuarto = $cuarto['id_cuarto'];
    $tipo_pago = $cuarto['tipo_pago'];

    // Obtener el número de la habitación y el precio base desde la base de datos
    $precio_base = 0;
    $numero_cuarto = '';
    foreach ($habitaciones_info as $habitacion) {
        if ($habitacion['id_cuarto'] == $id_cuarto) {
            $precio_base = $habitacion['precio_base'];
            $numero_cuarto = $habitacion['numero']; // Guardar el número de la habitación
            break;
        }
    }

    // Calcular el subtotal, aplicando el descuento si es pago por web
    $precio_ajustado = ($tipo_pago === 'web') ? $precio_base * 0.7 : $precio_base;
    $subtotal = $precio_ajustado * $dias_estadia;

    echo "<p>Habitación {$numero_cuarto}: $subtotal USD</p>";
    $total += $subtotal;
}
?>

        <h3>Total: <?php echo htmlspecialchars($total); ?> USD</h3>
        <button type="submit">Proceder al Pago</button>
    </form>
</body>
</html>
