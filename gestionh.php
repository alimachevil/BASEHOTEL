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

// Función para calcular los días de estadía
function calcularDiasEstadia($check_in, $check_out) {
    $fecha1 = new DateTime($check_in);
    $fecha2 = new DateTime($check_out);
    return $fecha2->diff($fecha1)->days;
}

// Cálculo de días de estadía
$dias_estadia = calcularDiasEstadia($check_in, $check_out);

// Inicializar habitaciones seleccionadas
if (!isset($_SESSION['cuartos_seleccionados'])) {
    $_SESSION['cuartos_seleccionados'] = [];
}
$cuartos_seleccionados = $_SESSION['cuartos_seleccionados'];

// Redirigir al pago si ya se alcanzó el límite
if (count($cuartos_seleccionados) >= $habitaciones) {
    header('Location: reserva_restaurante.php');
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
            if ($tipo_pago === 'web') {
                $precio_ajustado = $precio_base * 0.7;
            } elseif ($tipo_pago === 'blackdays') {
                $precio_ajustado = $precio_base * 0.65;
            } else {
                $precio_ajustado = $precio_base;
            }
            
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

// Consulta para obtener los cuartos disponibles con JOIN para incluir el tipo y la descripción
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
        SELECT c.*, t.nombre AS tipo_cuarto, c.descripcion, t.capacidad_adultos, t.capacidad_niños
        FROM cuartos c
        JOIN tipo_cuarto t ON c.id_tipo = t.id_tipo
        WHERE t.capacidad_adultos >= $capacidad_adultos
        AND t.capacidad_niños >= $capacidad_ninos
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
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;700&family=Roboto:wght@400;700&display=swap" rel="stylesheet">
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

        /* Estilo para la ventana emergente (popup) */
        .popup-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7); /* Fondo oscuro con transparencia */
            display: none; /* Inicialmente oculto */
            justify-content: center;
            align-items: center;
            overflow: auto; /* Permite el desplazamiento de la ventana emergente */
            z-index: 9999;
        }

        .popup-content {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            max-width: 900px;
            width: 90%;
            max-height: 80vh; /* Limita la altura del contenido del pop-up al 80% de la altura de la ventana */
            overflow-y: auto; /* Permite el desplazamiento vertical */
        }

        .popup-content h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .popup-content ul {
            list-style-type: none;
            padding: 0;
        }

        .popup-content ul li {
            margin: 10px 0;
            font-size: 1.2rem;
        }

        .popup-content #close-popup {
            background-color: #D69C4F;
            color: white;
            font-weight: bold;
            padding: 10px 20px;
            border: none;
            cursor: pointer;
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
        }

        .popup-content #close-popup:hover {
            background-color: #c88942;
        }

        .container {
            margin: 20px auto;
            max-width: 1200px;
            padding: 0 40px 40px 40px;
        }

        .habitaciones-general{
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px;
        }

        h2 {
            font-family: 'Lato', 'Roboto', sans-serif !important;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .habitaciones-container1 {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .habitaciones-container2 {
            padding-right: 78px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .habitaciones-container3 {
            display: flex;
            justify-content: center;
            align-items: baseline;
        }

        .habitaciones-title1 {
            text-align: center;
            font-size: 18px;
            padding-bottom: 7px;
            padding-right: 8px;
            display: inline-flex;
            align-items: center;
            position: relative;
            color: black; /* Asegura que el texto esté en negro */
        }

        .habitaciones-title2 {
            text-align: center;
            font-weight: bold;
            font-size: 18px;
            padding-bottom: 7px;
            padding-right: 8px;
            display: inline-flex;
            align-items: center;
            position: relative;
            color: black; /* Asegura que el texto esté en negro */
        }

        .habitaciones-title2 i {
            margin-right: 8px; /* Espacio entre el ícono y el texto */
            margin-left: 8px;
            font-size: 18px; /* Tamaño del ícono */
            color: black; /* El ícono también será negro */
        }

        .habitaciones-title2:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background-color: black; /* Subrayado negro */
        }

        /* Contenedor de las habitaciones */
        .habitaciones-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            padding: 10px;
        }

        /* Tarjeta de cada habitación */
        .habitacion-card {
            background-color: #fff;
            border: 1px solid #ddd;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            transition: transform 0.3s;
        }

        .habitacion-card:hover {
            transform: translateY(-5px);
        }

        /* Información de la habitación */
        .habitacion-info {
            padding: 20px;
            text-align: center;
        }

        .habitacion-info img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            margin-bottom: 15px;
        }

        .habitacion-info h3 {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 10px;
            color: #D69C4F;
        }

        .habitacion-info p {
            font-size: 1rem;
            margin: 5px 0;
            color: #666;
        }

        /* Opciones de la habitación */
        .habitacion-options {
            padding: 20px;
            background-color: #f9f9f9;
            border-top: 1px solid #ddd;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
        }

        /* Precio por noche subrayado y menos notorio */
        .precio-noche {
            font-size: 0.9rem;
            color: #999;
            text-align: center;
            margin-top: 8px;
        }

        /* Contenedor de pago y precio final */
        .payment-container {
            display: flex;
            width: 100%;
            margin-top: 15px;
            justify-content: center;
            align-items: center;
        }

        /* Monto final con fondo negro */
        .final-price {
            background-color: black;
            color: white;
            font-weight: bold;
            padding: 9.6px 0px 9.6px 0px;
            width: 48%; /* Ajustamos el tamaño */
            text-align: center;
            font-size: 1.2rem;
        }

        .final-price1 {
            background-color: black;
            color: white;
            font-weight: bold;
            padding: 9.6px;
            max-width: 80%;
            font-size: 1.2rem;
        }

        /* Estilo de los botones y selectores */
        .form-label {
            font-weight: bold;
            margin-bottom: 8px;
        }

        .form-select {
            border: none;
            border-bottom: 2px solid #000; /* Línea negra inferior */
            border-radius: 0; /* Sin bordes redondeados */
            background-color: transparent; /* Fondo transparente */
            box-shadow: none; /* Sin sombras */
            padding-left: 0; /* Ajusta el espacio interno */
            font-weight: bold;
            color: #000; /* Color del texto */
            width: 100%; /* Asegura que el select ocupe todo el ancho */
        }

        .form-select:focus {
            outline: none;
            border-bottom: 2px solid #D69C4F; /* Color dorado al enfocar */
        }

        /* Botón "Reservar" */
        .btn-warning {
            background-color: #D69C4F;
            color: white;
            font-weight: bold;
            border: none;
            padding: 12px 20px;
            cursor: pointer;
            width: 48%; /* Ajustamos el tamaño */
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
        <div class="progress-bar">
            <div class="step" onclick="clearSessionAndRedirect()">
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
    
    <!-- Contenedor de la ventana emergente (popup) -->
    <div id="habitaciones-popup" class="popup-container">
        <div class="popup-content">
            <h2 >Habitaciones Seleccionadas</h2>
            
            <!-- Contenedor de habitaciones seleccionadas en formato tarjeta -->
            <div class="habitaciones-container">
                <?php 
                // Inicializamos un contador para el número de la habitación
                $habitacion_numero = 1;

                // Recorremos las habitaciones seleccionadas
                foreach ($cuartos_seleccionados as $seleccion): 
                    // Realizamos una consulta para obtener los detalles de la habitación según el id_cuarto
                    $id_cuarto = (int) $seleccion['id_cuarto']; // Aseguramos que id_cuarto es un número entero
                    $query = "
                        SELECT h.id_cuarto, h.numero, h.piso, t.capacidad_adultos, t.capacidad_niños, h.descripcion, h.precio_base, t.nombre AS tipo_cuarto
                        FROM cuartos h
                        JOIN tipo_cuarto t ON h.id_tipo = t.id_tipo
                        WHERE h.id_cuarto = $id_cuarto
                    ";

                    // Usamos $conn para la conexión, que es el objeto de conexión creado con mysqli
                    $resultado = $conn->query($query); // $conn es el objeto de conexión
                    
                    // Verificamos si la habitación existe
                    if ($resultado && $resultado->num_rows > 0) {
                        $cuarto = $resultado->fetch_assoc(); // Extraemos los datos de la habitación
                ?>
                    <div class="habitacion-card">
                        <div class="habitacion-info">
                            <!-- Modificamos el título con el número de habitación y el total -->
                            <h3>Habitación <?php echo $habitacion_numero; ?> de <?php echo $habitaciones; ?></h3>
                            <img src="images/habitacion1.jpg" alt="Imagen de la Habitación">
                            <p><strong>Tipo de Habitación:</strong> <?php echo htmlspecialchars($cuarto['tipo_cuarto']); ?> #<?php echo htmlspecialchars($cuarto['numero']); ?></p>
                            <p><strong>Pago: </strong>
                                <?php 
                                    if ($seleccion['tipo_pago'] === 'hotel') {
                                        echo 'En Hotel';
                                    } elseif ($seleccion['tipo_pago'] === 'web') {
                                        echo 'Por Web';
                                    } elseif ($seleccion['tipo_pago'] === 'blackdays') {
                                        echo 'Con oferta de Black Days';
                                    }
                                ?>
                            </p>
                            <p><strong>Capacidad Adultos:</strong> <?php echo htmlspecialchars($cuarto['capacidad_adultos']); ?></p>
                            <p><strong>Capacidad Niños:</strong> <?php echo htmlspecialchars($cuarto['capacidad_niños']); ?></p>
                            <p><strong>Piso:</strong> <?php echo htmlspecialchars($cuarto['piso']); ?></p>
                            <p>Disfruta de una experiencia diferente en un acogedor <?php echo htmlspecialchars($cuarto['descripcion']); ?> y otros servicios que te permitirán descansar plácidamente.</p>
                        </div>
                        <div class="habitacion-options">
                            <form method="POST">
                                <input type="hidden" name="id_cuarto" value="<?php echo htmlspecialchars($cuarto['id_cuarto']); ?>">

                                <!-- Tipo de Pago -->
                                <p class="form-label">Precio por noche</p>
                                <p class="precio-noche"> <?php echo htmlspecialchars($seleccion['precio']); ?> x <?php echo $dias_estadia; ?> noches</p> 

                                <!-- Contenedor de pago y precio final -->
                                <div class="payment-container">
                                    <!-- Monto final con fondo negro -->
                                    <div class="final-price1">
                                        <strong>S/ <?php echo htmlspecialchars($seleccion['precio']* $dias_estadia); ?></strong>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php 
                        // Aumentamos el número de habitación
                        $habitacion_numero++;
                    } else {
                        echo "<p>Habitación no disponible.</p>"; // Si no se encuentra la habitación
                    }
                endforeach; 
                ?>
            </div>

            <!-- Botón de Cerrar el pop-up -->
            <button id="close-popup" class="btn-warning">Cerrar</button>
        </div>
    </div>

    <!-- Contenido de la Página -->
    <div class="container">

        <!-- Mostrar habitaciones disponibles -->
        <?php if (count($cuartos_seleccionados) < $habitaciones): ?>
            <div class="habitaciones-general">
                <div class="habitaciones-container3" id="habitaciones-toggle">
                    <h3 class="habitaciones-title1">
                        Habitaciones Seleccionadas
                    </h3>
                    <i class="fa fa-chevron-down" aria-hidden="true"></i>
                </div>

                <div class="habitaciones-container2">
                    <h2 class="habitaciones-title2">
                        <i class="fa fa-hotel" aria-hidden="true"></i> Habitaciones:
                    </h2>
                </div>

                
                <div class="habitaciones-container1">
                    <h3 class="habitaciones-title1">
                        Habitación <?php echo count($cuartos_seleccionados) + 1; ?> de <?php echo $habitaciones; ?>
                    </h3>
                </div>
            </div>
            

            <?php if (!empty($cuartos_disponibles)): ?>
                <div class="habitaciones-container">
                    <?php foreach ($cuartos_disponibles as $cuarto): ?>
                        <div class="habitacion-card">
                            <div class="habitacion-info">
                                <h3>Habitación <?php echo htmlspecialchars($cuarto['tipo_cuarto']); ?> #<?php echo htmlspecialchars($cuarto['numero']); ?></h3>
                                <img src="images/habitacion1.jpg" alt="Imagen de la Habitación">
                                <p><strong>Piso:</strong> <?php echo htmlspecialchars($cuarto['piso']); ?></p>
                                <p><strong>Capacidad Adultos:</strong> <?php echo htmlspecialchars($cuarto['capacidad_adultos']); ?></p>
                                <p><strong>Capacidad Niños:</strong> <?php echo htmlspecialchars($cuarto['capacidad_niños']); ?></p>
                                <p><strong>Tipo de Habitación:</strong> <?php echo htmlspecialchars($cuarto['tipo_cuarto']); ?></p>
                                <p>Disfruta de una experiencia diferente en un acogedor <?php echo htmlspecialchars($cuarto['descripcion']); ?> y otros servicios que te permitirán descansar plácidamente.</p>   
                            </div>
                            <div class="habitacion-options">
                                <form method="POST">
                                    <input type="hidden" name="id_cuarto" value="<?php echo htmlspecialchars($cuarto['id_cuarto']); ?>">

                                    <!-- Tipo de Pago -->
                                    <label class="form-label">Tipo de Pago y Ofertas:</label>
                                    <select name="tipo_pago" class="form-select tipo_pago" data-precio-base="<?php echo htmlspecialchars($cuarto['precio_base']); ?>" onchange="calcularPrecio(this)">
                                        <option value="hotel">Pagar en Hotel</option>
                                        <option value="web">Hasta 30% Dscto: Pagar por Web</option>
                                        <option value="blackdays">Hasta 35% Dscto: Black Days</option>
                                    </select>

                                    <!-- Precio por noche -->
                                    <p class="precio-noche">
                                        <u>Precio x noche</u>
                                    </p>

                                    <!-- Contenedor de pago y precio final -->
                                    <div class="payment-container">
                                        <!-- Monto final con fondo negro -->
                                        <div class="final-price">
                                            <strong>S/ <?php echo htmlspecialchars($cuarto['precio_base']); ?></strong>
                                        </div>

                                        <!-- Botón de Reservar -->
                                        <button type="submit" name="reservar" class="btn-warning">Reservar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No hay habitaciones disponibles para las fechas y capacidades seleccionadas.</p>
            <?php endif; ?>
        <?php else: ?>
            <p>Ya has seleccionado todas las habitaciones necesarias.</p>
            <a href="pagoh.php" class="btn btn-warning">Continuar al Pago</a>
        <?php endif; ?>
    </div>

    <?php $conn->close(); ?>

    <!-- JavaScript -->
    <script>
        function calcularPrecio(selectElement) {
            // Obtener el precio base desde el atributo 'data-precio-base' del select
            var precioBase = parseFloat(selectElement.getAttribute('data-precio-base'));
            var precioFinal = precioBase;

            // Obtener el valor seleccionado
            var tipoPago = selectElement.value;

            // Calcular el precio según la opción seleccionada
            if (tipoPago === 'web') {
                precioFinal = precioBase * 0.7;
            } else if (tipoPago === 'blackdays') {
                precioFinal = precioBase * 0.65;
            }

            // Obtener el div que contiene el precio final
            var finalPriceDiv = selectElement.closest('.habitacion-card').querySelector('.final-price');
            
            // Actualizar el precio final en la tarjeta correspondiente
            finalPriceDiv.innerHTML = '<strong>S/ ' + precioFinal.toFixed(2) + '</strong>';
        }
    
        // Obtener los elementos
        const popup = document.getElementById('habitaciones-popup');
        const toggleButton = document.getElementById('habitaciones-toggle');
        const closeButton = document.getElementById('close-popup');

        // Abrir el popup al hacer clic en "Habitaciones Seleccionadas"
        toggleButton.addEventListener('click', () => {
            popup.style.display = 'flex'; // Mostrar el popup
        });

        // Cerrar el popup al hacer clic en "Cerrar"
        closeButton.addEventListener('click', () => {
            popup.style.display = 'none'; // Ocultar el popup
        });

        // Cerrar el popup si el usuario hace clic fuera del contenido
        window.addEventListener('click', (event) => {
            if (event.target === popup) {
                popup.style.display = 'none';
            }
        });

        function clearSessionAndRedirect() {
            // Redirige al usuario a la página 'hyh.php'
            window.location.href = 'hyh.php?clear_session=true';
        }
    </script>
</body>
</html>
