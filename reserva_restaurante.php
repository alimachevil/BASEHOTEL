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

// Verificar si se ha enviado el formulario de consultar mesas
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['consultar_mesas'])) {
    // Obtener la información de la mesa y reserva
    $fecha_reserva = $_POST['fecha_reserva'];
    $tipo_reserva = $_POST['tipo_reserva']; // Tipo de reserva (desayuno, almuerzo, cena)
    $_SESSION['fecha_reserva_mesas'] = $fecha_reserva; // Guardar en sesión

    // Buscar mesas reservadas para esa fecha y tipo de reserva
    $sql_mesas_reservadas = "SELECT id_mesa FROM reserva_restaurante WHERE fecha_reserva = ? AND tipo_reserva = ?";
    $stmt_mesas_reservadas = $conn->prepare($sql_mesas_reservadas);
    $stmt_mesas_reservadas->bind_param("ss", $fecha_reserva, $tipo_reserva);
    $stmt_mesas_reservadas->execute();
    $result_mesas_reservadas = $stmt_mesas_reservadas->get_result();

    $mesas_reservadas = [];
    while ($row_mesa_reservada = $result_mesas_reservadas->fetch_assoc()) {
        $mesas_reservadas[] = $row_mesa_reservada['id_mesa'];
    }

    // Mostrar mesas disponibles
    if (count($mesas_reservadas) > 0) {
        $sql_mesas_disponibles = "SELECT m.id_mesa, m.descripcion, m.precio_reservam, t.tipo FROM mesas m JOIN tipo_mesa t ON m.id_tipo = t.id_tipo WHERE id_mesa NOT IN (" . implode(",", array_fill(0, count($mesas_reservadas), '?')) . ")";
        $stmt_mesas_disponibles = $conn->prepare($sql_mesas_disponibles);
        $stmt_mesas_disponibles->bind_param(str_repeat('i', count($mesas_reservadas)), ...$mesas_reservadas);
    } else {
        $sql_mesas_disponibles = "SELECT m.id_mesa, m.descripcion, m.precio_reservam, t.tipo FROM mesas m JOIN tipo_mesa t ON m.id_tipo = t.id_tipo";
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

// Procesar la reserva de mesas
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reservar_mesa'])) {
    // Verificar que se han seleccionado mesas
    if (isset($_POST['id_mesa'])) {
        // Obtener información de la mesa seleccionada
        $id_mesa = $_POST['id_mesa'];
        $fecha_reserva = $_POST['fecha_reserva'];
        $tipo_reserva = $_POST['tipo_reserva']; // Desayuno, almuerzo, cena

        // Obtener el precio de la mesa
        $sql_precio_mesa = "SELECT precio_reservam FROM mesas WHERE id_mesa = ?";
        $stmt_precio_mesa = $conn->prepare($sql_precio_mesa);
        $stmt_precio_mesa->bind_param("i", $id_mesa);
        $stmt_precio_mesa->execute();
        $result_precio_mesa = $stmt_precio_mesa->get_result();
        $row_precio_mesa = $result_precio_mesa->fetch_assoc();
        $total_costo_mesas = $row_precio_mesa['precio_reservam'];

        // Guardar la información de la mesa en la sesión
        $_SESSION['reserva_mesa'][] = [
            'id_mesa' => $id_mesa,
            'fecha_reserva' => $fecha_reserva,
            'tipo_reserva' => $tipo_reserva,
            'precio_reservam' => $total_costo_mesas
        ];

        // Actualizar el costo total en la sesión
        if (isset($_SESSION['total_costo_mesas'])) {
            $_SESSION['total_costo_mesas'] += $total_costo_mesas;
        } else {
            $_SESSION['total_costo_mesas'] = $total_costo_mesas;
        }
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

    .progress-bar {
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
        margin-top: 20px;
        display: flex;
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

    .form-section {
        max-width: 1200px;
        background-color: transparent;
        padding: 20px;
        display: flex;
        flex-direction: column;
    }

    h2 {
        font-weight: bold;
        margin-bottom: 20px;
    }

    .form-section .col-4 {
        margin-bottom: 10px;
    }

    .form-label {
        font-weight: bold;
        margin-bottom: 8px;
    }
    .reserva-container{
    }
    .forms1 {
        border: none;
        border-bottom: 2px solid #000; /* Línea negra inferior */
        border-radius: 0; /* Sin bordes redondeados */
        background-color: transparent; /* Fondo transparente */
        box-shadow: none; /* Sin sombras */
        padding-left: 0; /* Ajusta el espacio interno */
        font-weight: bold;
        color: #000; /* Color del texto */
        width: 100%; /* Asegura que el input ocupe todo el ancho */
    }

    .forms1:focus {
        outline: none;
        border-bottom: 2px solid #D69C4F; /* Color dorado al enfocar */
    }


    /* Contenedor de las mesas */
    .habitaciones-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        padding: 10px;
    }

    /* Tarjeta de cada mesa */
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

    /* Información de la mesa */
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

    /* Opciones de la mesa */
    .habitacion-options {
        padding: 20px;
        background-color: #f9f9f9;
        border-top: 1px solid #ddd;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        align-items: stretch;
    }
    
    /* Contenedor de pago y precio final */
    .payment-container {
        display: flex;
        width: 100%;
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
    .btn-warning1 {
        background-color: #D69C4F;
        color: white;
        font-weight: bold;
        border: none;
        padding: 6px 12px;
        cursor: pointer;
        border-radius: 0px;
    }
    .btn-warning1:hover {
        background-color: #c88942;
    }

    /* Botón "Reservar" */
    .btn-warning {
        background-color: #D69C4F;
        color: white;
        font-weight: bold;
        border: none;
        padding: 12px 20px;
        cursor: pointer;
        width: 60%; /* Ajustamos el tamaño */
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
                                <option value="Desayuno">Desayuno</option>
                                <option value="Almuerzo">Almuerzo</option>
                                <option value="Cena">Cena</option>
                            </select>
                        </div>
                        <div class="col-4 text-start">
                            <br><p class="form-label">Total: <p class="forms1"> S/. <span id="total-costo-mesas"><?php echo $_SESSION['total_costo_mesas'] ?? '0'; ?></span></p></p>
                        </div>
                    </div>
                </div>
                <button type="submit" name="consultar_mesas" class="btn btn-warning1" >Consultar Disponibilidad</button>
                <button type="button" class="btn btn-warning1" onclick="window.location.href='pagoh.php'" >Confirmar Reserva</button>            
                
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
                            <input type="hidden" name="fecha_reserva" value="<?php echo htmlspecialchars($fecha_reserva); ?>">
                            <input type="hidden" name="tipo_reserva" value="<?php echo htmlspecialchars($tipo_reserva); ?>">
                            
                            <div class="payment-container">
                                <div class="final-price">
                                    <strong>S/ <?php echo htmlspecialchars($mesa['precio_reservam']); ?></strong>
                                </div>
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
</body>
</html>