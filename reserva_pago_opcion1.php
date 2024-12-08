<?php
// Inicia la sesión
session_start();

// Manejar reinicio de sesión
if (isset($_GET['reset']) && $_GET['reset'] === '1') {
    unset($_SESSION['id_reserva'], $_SESSION['habitaciones'], $_SESSION['habitacion_actual']);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

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

// Si no se ha ingresado un código de reserva, solicitarlo
if (!isset($_SESSION['id_reserva'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['codigo_reserva'])) {
        $codigo_reserva = trim($_POST['codigo_reserva']);

        // Verificar el código de reserva
        $stmt = $conn->prepare("SELECT id_reserva FROM reservas WHERE id_reserva = ?");
        $stmt->bind_param("i", $codigo_reserva);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Verificar si ya hay acompañantes registrados para esta reserva
            $stmt = $conn->prepare("
                SELECT COUNT(*) AS total_acompañantes 
                FROM acompañantes 
                WHERE id_reserva = ?
            ");
            $stmt->bind_param("i", $codigo_reserva);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            if ($row['total_acompañantes'] > 0) {
                // Mensaje de acompañantes ya registrados
                $error = "Acompañantes ya registrados para esta reserva.";
            } else {
                $_SESSION['id_reserva'] = $codigo_reserva;

                // Cargar habitaciones asociadas a la reserva
                $sql = "
                    SELECT c.id_cuarto, c.numero, t.capacidad_adultos, t.capacidad_niños
                    FROM cuartos c
                    INNER JOIN reservaporcuartos r ON c.id_cuarto = r.id_cuarto
                    INNER JOIN tipo_cuarto t ON t.id_tipo = c.id_tipo
                    WHERE r.id_reserva = ?
                ";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $codigo_reserva);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $_SESSION['habitaciones'] = $result->fetch_all(MYSQLI_ASSOC);
                    $_SESSION['habitacion_actual'] = 0;
                } else {
                    $error = "No se encontraron habitaciones para esta reserva.";
                }

                header('Location: ' . $_SERVER['PHP_SELF']);
                exit();
            }
        } else {
            $error = "El código de reserva no es válido.";
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Ingresar Código de Reserva</title>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <style>


        .container {
            background-color: #ffffff;
            padding: 30px 40px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
            text-align: center;
        }

        .container h1 {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
        }

        .error {
            color: #ff4d4d;
            font-size: 16px;
            margin-bottom: 15px;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        label {
            font-size: 16px;
            color: #555;
            margin-bottom: 8px;
            text-align: left;
        }

        input[type="text"] {
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 20px;
            width: 100%;
            box-sizing: border-box;
            outline: none;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus {
            border-color: #C88942;
        }

        button {
            background-color: #D69C4F;
            color: #fff;
            font-size: 16px;
            font-weight: normal;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #C88942;
        }

        button:active {
            transform: scale(0.98);
        }
        /* Estilos generales */
        body {
            font-family: 'Lato', 'Roboto', sans-serif !important;
            margin: 0;
            padding: 0;
            display: flex;
            height:100vh;
            background-color: #FFFFFF;
            justify-content: center;
            align-items: center;
             /* Asegura que el contenido principal llene la ventana */
        }
        .sidebar {
            width: 250px;
            background-color: #333;
            color: white;
            padding-top: 20px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            height: 100%;
        }
        .sidebar img {
            width: 80%;
            max-width: 150px;
            margin-bottom: 20px;
        }
        .sidebar h2 {
            text-align: center;
            font-weight: bold;
            font-size: 18px;
            padding-bottom: 7px;
            display: inline-flex;
            align-items: center;
            position: relative;
            color: #fff;
            margin-bottom: 30px;
        }
        .menu {
            display: flex;
            flex-direction: column;
            width: 100%;
        }
        .menu form {
            width: 100%;
            margin-bottom: 0;
        }
        .menu button {
            display: flex;
            align-items: center;
            padding: 15px;
            font-size: 16px;
            background-color: #333;
            color: white;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s, color 0.3s;
            width: 100%;
            margin-bottom: 0;
        }
        .menu button:hover {
            background-color: #D69C4F;
            color: black;
        }
        .menu button i {
            margin-right: 10px;
        }

        /* Estilos del submenú (opciones dentro del botón PEDIDOS) */
        .submenu {
            display: none;
            flex-direction: column;
            width: 100%;
            margin-top: 10px;
        }
        .submenu a {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            font-size: 16px;
            background-color: #333;
            color: white;
            text-decoration: none;
            border: none;
            transition: background-color 0.3s, color 0.3s;
            padding-left: 30px; /* Agregar desplazamiento a la derecha */
        }
        .submenu a:hover {
            background-color: #D69C4F;
            color: black;
        }
        .submenu a i {
            margin-right: 10px;
        }

        /* Efecto de deslizamiento hacia abajo */
        .menu button.active + .submenu {
            display: flex;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                max-height: 0;
            }
            to {
                opacity: 1;
                max-height: 500px;
            }
        }

        /* Estilos para la sección de contenido a la derecha */
        .content {
            flex-grow: 1;
            padding: 30px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background-color: #fff;
        }
        .content h2 {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
        }
        .content p {
            font-size: 18px;
            color: #555;
            margin-bottom: 40px;
        }
        .content .option-box {
            width: 100%;
            display: flex;
            justify-content: space-around;
            gap: 20px;
        }
        .content .option-box div {
            padding: 20px;
            background-color: #28a745;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            text-align: center;
            flex: 1;
        }
        .content .option-box div:hover {
            background-color: #218838;
        }
        
    </style>
    </head>
    <body>
    <div class="sidebar">
        <!-- Espacio para el logo -->
        <img src="images/logo.png" alt="Logo">
        <h2>Panel de Control del Administrador</h2>
        <div class="menu">
            <form action="reserva_pago_opcion1.php" method="GET">
                <button type="submit"><i class="fas fa-user"></i>Datos de Acompañantes</button>
            </form>
            <form action="pedido_restaurante_bar.php" method="GET">
                <button type="submit" id="pedidosBtn"><i class="fas fa-utensils"></i>Pedidos</button>
                <div class="submenu" id="submenuPedidos">
                    <a href="pedido_restaurante_bar.php#restaurante"><i class="fas fa-cocktail"></i>Restaurante</a>
                    <a href="pedido_restaurante_bar.php#bar"><i class="fas fa-beer"></i>Bar</a>
                    <a href="pedido_restaurante_bar.php#habitacion"><i class="fas fa-bed"></i>Habitación</a>
                </div>
            </form>
            <form action="reportes.php" method="GET">
                <button type="submit" id="reportesBtn"><i class="fas fa-file-alt"></i>Reportes</button>
                <div class="submenu" id="submenuReportes">
                    <a href="reportes.php?reporte=listado_huespedes"><i class="fas fa-users"></i>Listado de Huéspedes</a>
                    <a href="reportes.php?reporte=ranking_habitaciones"><i class="fas fa-bed"></i>Ranking de Cuartos</a>
                    <a href="reportes.php?reporte=reporte_monto_restaurante"><i class="fas fa-utensils"></i>Reporte Monto Restaurante</a>
                    <a href="reportes.php?reporte=ranking_productos_restaurante"><i class="fas fa-cocktail"></i>Ranking Productos Restaurante</a>
                    <a href="reportes.php?reporte=ranking_bebidas_bar"><i class="fas fa-beer"></i>Ranking Bebidas Bar</a>
                </div>
            </form>
        </div>
    </div>
    <div class="content">
        <div class="container">
                <h1>Ingresar Código de Reserva</h1>
                <?php if (isset($error)): ?>
                    <p class="error"><?php echo htmlspecialchars($error); ?></p>
                <?php endif; ?>
                <form method="POST">
                    <label for="codigo_reserva">Código de Reserva:</label>
                    <input type="text" id="codigo_reserva" name="codigo_reserva" placeholder="Ingrese su código" required>
                    <button type="submit">Buscar</button>
                </form>
        </div>  
    </div>
    <script>
        // Obtener el contenedor del botón "Pedidos" y el submenú
        const pedidosBtn = document.getElementById('pedidosBtn');
        const submenuPedidos = document.getElementById('submenuPedidos');
        
        // Mostrar el submenú cuando se pasa el ratón sobre "Pedidos"
        pedidosBtn.addEventListener('mouseover', function() {
            submenuPedidos.style.display = 'flex'; // Mostrar el submenú
        });

        // Mantener el submenú abierto cuando se pasa el ratón sobre el submenú
        submenuPedidos.addEventListener('mouseover', function() {
            submenuPedidos.style.display = 'flex'; // Mantenerlo abierto
        });

        // Cerrar el submenú cuando el ratón sale del botón "Pedidos" o el submenú
        pedidosBtn.addEventListener('mouseout', function() {
            setTimeout(() => {
                if (!submenuPedidos.matches(':hover') && !pedidosBtn.matches(':hover')) {
                    submenuPedidos.style.display = 'none'; // Ocultar el submenú si no está sobre él
                }
            }, 100); // Retraso para evitar un cierre inmediato
        });

        // Obtener el contenedor del botón "Reportes" y el submenú de reportes
        const reportesBtn = document.getElementById('reportesBtn');
        const submenuReportes = document.getElementById('submenuReportes');

        // Mostrar el submenú cuando se pasa el ratón sobre "Reportes"
        reportesBtn.addEventListener('mouseover', function() {
            submenuReportes.style.display = 'flex'; // Mostrar el submenú
        });

        // Mantener el submenú abierto cuando se pasa el ratón sobre el submenú
        submenuReportes.addEventListener('mouseover', function() {
            submenuReportes.style.display = 'flex'; // Mantenerlo abierto
        });

        // Cerrar el submenú cuando el ratón sale del botón "Reportes" o el submenú
        reportesBtn.addEventListener('mouseout', function() {
            setTimeout(() => {
                if (!submenuReportes.matches(':hover') && !reportesBtn.matches(':hover')) {
                    submenuReportes.style.display = 'none'; // Ocultar el submenú si no está sobre él
                }
            }, 100); // Retraso para evitar un cierre inmediato
        });
    </script>
</body>
</html>
    <?php
    exit();
}

// Proceso del formulario para registrar huéspedes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['huéspedes'])) {
    $id_reserva = $_SESSION['id_reserva'];
    $habitacion_actual = $_SESSION['habitaciones'][$_SESSION['habitacion_actual']];
    $id_cuarto = $habitacion_actual['id_cuarto'];
    $huespedes = $_POST['huéspedes'];

    // Iniciar transacción
    $conn->begin_transaction();

    try {
        foreach ($huespedes as $datosHuesped) {
            // Extraer datos del huésped
            $nombre = $datosHuesped['nombre'] ?? null;
            $apellido = $datosHuesped['apellido'] ?? null;
            $tipo_documento = $datosHuesped['tipo_documento'] ?? null;
            $nro_documento = $datosHuesped['nro_documento'] ?? null;
            $celular = $datosHuesped['celular'] ?? null;
            $pais = $datosHuesped['pais'] ?? null;
            $correo = $datosHuesped['correo'] ?? null;

            // Validar datos y guardar en la base de datos
            if ($nombre && $apellido && $tipo_documento && $nro_documento && $pais) {
                $stmt = $conn->prepare("
                    INSERT INTO acompañantes (nombre, apellido, tipo_documento, nro_documento, celular, pais, correo, id_reserva)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param(
                    "sssssssi",
                    $nombre,
                    $apellido,
                    $tipo_documento,
                    $nro_documento,
                    $celular,
                    $pais,
                    $correo,
                    $id_reserva
                );

                if (!$stmt->execute()) {
                    throw new Exception("Error al guardar acompañante: " . $stmt->error);
                }
            } else {
                throw new Exception("Datos incompletos para el huésped.");
            }
        }

        // Confirmar transacción
        $conn->commit();

        // Avanzar a la siguiente habitación
        $_SESSION['habitacion_actual']++;

        // Si se completaron todas las habitaciones, finalizar
        if ($_SESSION['habitacion_actual'] >= count($_SESSION['habitaciones'])) {
            unset($_SESSION['habitaciones'], $_SESSION['habitacion_actual']);
            header('Location: confirmacion_huespedes1.php');
            exit();
        }

        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        die("Error al guardar los acompañantes: " . $e->getMessage());
    }
}

// Cargar habitación actual
$habitacion_actual = $_SESSION['habitaciones'][$_SESSION['habitacion_actual']];
$maxHuespedes = $habitacion_actual['capacidad_adultos'] + $habitacion_actual['capacidad_niños'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Huéspedes</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        
    body {
        font-family: 'Lato', 'Roboto', sans-serif !important;
        background-color: #f4f4f9;
        margin: 0;
        padding: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
    }

    .container {
        background-color: #ffffff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        max-width: 600px;
        width: 90%;
    }

    .title {
        font-size: 24px;
        margin-bottom: 10px;
        color: #333;
        text-align: center;
    }

    .subtitle {
        font-size: 18px;
        margin-bottom: 20px;
        color: #666;
        text-align: center;
    }

    .guest-form {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .huesped-form {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        background-color: #FFFFFF;
        padding: 10px;
        border-radius: 5px;
    }

    .huesped-form label {
        flex: 1 1 45%;
        font-size: 14px;
        color: #555;
    }

    .huesped-form input, .huesped-form select {
        width: 100%;
        padding: 5px;
        margin-top: 5px;
        font-size: 14px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }

    .buttons {
        display: flex;
        justify-content: space-between;
        gap: 10px;
    }

    .btn {
        padding: 10px 15px;
        font-size: 14px;
        color: #fff;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    
        body {
            font-family: 'Lato', 'Roboto', sans-serif !important;
            margin: 0;
            padding: 0;
            display: flex;
            height: 100vh;
            background-color: #FFFFFF;
        }
        .sidebar {
            width: 250px;
            background-color: #333;
            color: white;
            padding-top: 20px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            height: 100%;
        }
        .sidebar img {
            width: 80%;
            max-width: 150px;
            margin-bottom: 20px;
        }
        .sidebar h2 {
            text-align: center;
            font-weight: bold;
            font-size: 18px;
            padding-bottom: 7px;
            display: inline-flex;
            align-items: center;
            position: relative;
            color: #fff;
            margin-bottom: 30px;
        }
        .menu {
            display: flex;
            flex-direction: column;
            width: 100%;
        }
        .menu form {
            width: 100%;
            margin-bottom: 0;
        }
        .menu button {
            display: flex;
            align-items: center;
            padding: 15px;
            font-size: 16px;
            background-color: #333;
            color: white;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s, color 0.3s;
            width: 100%;
            margin-bottom: 0;
        }
        .menu button:hover {
            background-color: #D69C4F;
            color: black;
        }
        .menu button i {
            margin-right: 10px;
        }

        /* Estilos del submenú (opciones dentro del botón PEDIDOS) */
        .submenu {
            display: none;
            flex-direction: column;
            width: 100%;
            margin-top: 10px;
        }
        .submenu a {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            font-size: 16px;
            background-color: #333;
            color: white;
            text-decoration: none;
            border: none;
            transition: background-color 0.3s, color 0.3s;
            padding-left: 30px; /* Agregar desplazamiento a la derecha */
        }
        .submenu a:hover {
            background-color: #D69C4F;
            color: black;
        }
        .submenu a i {
            margin-right: 10px;
        }

        /* Efecto de deslizamiento hacia abajo */
        .menu button.active + .submenu {
            display: flex;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                max-height: 0;
            }
            to {
                opacity: 1;
                max-height: 500px;
            }
        }

        /* Estilos para la sección de contenido a la derecha */
        .content {
            flex-grow: 1;
            padding: 30px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background-color: #FFFFFF;
        }
        
        /*NUEVO ESTILO DE FORMULARIO*/
        /* General Styles */

/* Container */
.container {
    max-width: 800px;
    margin: 50px auto;
    padding: 20px;
    background-color: #FFFFFF; /* Negro profundo para contraste */
    border: 2px solid #D69C4F; /* Borde dorado */
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
}

/* Titles */
.title {
    font-size: 28px;
    text-align: center;
    color: #D69C4F;
    margin-bottom: 10px;
}

.subtitle {
    font-size: 18px;
    text-align: center;
    color: #E1B97C; /* Tono más claro del dorado */
    margin-bottom: 20px;
}

/* Form Styles */
.guest-form label {
    display: block;
    margin-bottom: 10px;
    color: #D69C4F;
    font-weight: bold;
}

.guest-form input,
.guest-form select {
    width: calc(100% - 10px);
    padding: 10px;
    margin-top: 5px;
    margin-bottom: 15px;
    border: 1px solid #D69C4F;
    border-radius: 5px;
    background-color: #FFFFFF;
    color: #000;
    font-size: 14px;
}

.guest-form input:focus,
.guest-form select:focus {
    border-color: #E1B97C;
    outline: none;
    box-shadow: 0 0 8px rgba(214, 156, 79, 0.8);
}

/* Buttons */
.btn {
    display: inline-block;
    padding: 10px 20px;
    font-size: 16px;
    font-weight: bold;
    color: #FFFFFF;
    background-color: #D69C4F;
    border: none;
    border-radius: 5px;
    text-align: center;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn:hover {
    background-color: #E1B97C;
    color: #121212;
    transform: scale(1.05);
}

.add-btn {
    margin-right: 10px;
}

.submit-btn {
    background-color: #121212;
    border: 1px solid #D69C4F;
    color: #D69C4F;
}

.submit-btn:hover {
    background-color: #D69C4F;
    color: #FFFFFF;
}

/* Reset Button */
.delete-btn {
    color: #FFFFFF;
    background-color: #FF4D4D; /* Rojo llamativo */
    border: none;
}

.delete-btnform{
    height:50px;
    margin-top:20px;
}

.delete-btn:hover {
    background-color: #FF6666;
    color: #121212;
}

/* Responsive Design */
@media screen and (max-width: 768px) {
    .container {
        padding: 15px;
    }
    .title {
        font-size: 24px;
    }
    .subtitle {
        font-size: 16px;
    }
    .btn {
        font-size: 14px;
    }
}
 
</style>

    <script>
        function ajustarAlturaBody(valor) {
    const body = document.body;
    const alturaBase = 100; // Altura base en vh
    const alturaActual = parseFloat(window.getComputedStyle(body).height) / window.innerHeight * 100 || alturaBase; // Calcula la altura actual en vh
    
    // Calcula la nueva altura asegurándote de no bajar de la altura base
    const nuevaAltura = Math.max(alturaBase, alturaActual + valor);

    // Ajusta la altura del body
    body.style.height = nuevaAltura + "vh";
}

        // Función para agregar un formulario de huésped
        function agregarFormulario(idHabitacion, maxFormularios) {
            const contenedor = document.getElementById(`habitacion-${idHabitacion}`);
            const totalFormularios = contenedor.querySelectorAll('.huesped-form').length;

            if (totalFormularios < maxFormularios) {
                const nuevoFormulario = document.createElement('div');
                nuevoFormulario.classList.add('huesped-form');
                nuevoFormulario.innerHTML = `
                    <label>Nombre: <input type="text" name="huéspedes[${totalFormularios}][nombre]" required></label>
                    <label>Apellido: <input type="text" name="huéspedes[${totalFormularios}][apellido]" required></label>
                    <label>Tipo de Documento:
                        <select name="huéspedes[${totalFormularios}][tipo_documento]" required>
                            <option value="DNI">DNI</option>
                            <option value="Pasaporte">Pasaporte</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </label>
                    <label>Número de Documento: <input type="text" name="huéspedes[${totalFormularios}][nro_documento]" required></label>
                    <label>Celular: <input type="text" name="huéspedes[${totalFormularios}][celular]"></label>
                    <label>País: <input type="text" name="huéspedes[${totalFormularios}][pais]" required></label>
                    <label>Correo: <input type="email" name="huéspedes[${totalFormularios}][correo]"></label>
                    <button type="button" class="btn delete-btn delete-btnform" onclick="eliminarFormulario(this)">Eliminar</button>
                `;
                ajustarAlturaBody(45); // Incrementa en 45vh
                contenedor.appendChild(nuevoFormulario);
            } else {
                alert('No se pueden agregar más huéspedes para esta habitación.');
            }
        }

        // Función para eliminar un formulario de huésped
        function eliminarFormulario(boton) {
            boton.parentElement.remove();
            ajustarAlturaBody(-45); // Decrementa en 45vh
        }
    </script>
</head>
<body>
    <div class="sidebar">
        <!-- Espacio para el logo -->
        <img src="images/logo.png" alt="Logo">
        <h2>Panel de Control del Administrador</h2>
        <div class="menu">
            <form action="reserva_pago_opcion1.php" method="GET">
                <button type="submit"><i class="fas fa-user"></i>Datos de Acompañantes</button>
            </form>
            <form action="pedido_restaurante_bar.php" method="GET">
                <button type="submit" id="pedidosBtn"><i class="fas fa-utensils"></i>Pedidos</button>
                <div class="submenu" id="submenuPedidos">
                    <a href="pedido_restaurante_bar.php#restaurante"><i class="fas fa-cocktail"></i>Restaurante</a>
                    <a href="pedido_restaurante_bar.php#bar"><i class="fas fa-beer"></i>Bar</a>
                    <a href="pedido_restaurante_bar.php#habitacion"><i class="fas fa-bed"></i>Habitación</a>
                </div>
            </form>
            <form action="reportes.php" method="GET">
                <button type="submit" id="reportesBtn"><i class="fas fa-file-alt"></i>Reportes</button>
                <div class="submenu" id="submenuReportes">
                    <a href="reportes.php?reporte=listado_huespedes"><i class="fas fa-users"></i>Listado de Huéspedes</a>
                    <a href="reportes.php?reporte=ranking_habitaciones"><i class="fas fa-bed"></i>Ranking de Cuartos</a>
                    <a href="reportes.php?reporte=reporte_monto_restaurante"><i class="fas fa-utensils"></i>Reporte Monto Restaurante</a>
                    <a href="reportes.php?reporte=ranking_productos_restaurante"><i class="fas fa-cocktail"></i>Ranking Productos Restaurante</a>
                    <a href="reportes.php?reporte=ranking_bebidas_bar"><i class="fas fa-beer"></i>Ranking Bebidas Bar</a>
                </div>
            </form>
        </div>
    </div>
    <div class="content">
        <div class="container">
            <h1 class="title">Registro de Huéspedes</h1>
            <h2 class="subtitle">Habitación <?php echo htmlspecialchars($habitacion_actual['numero']); ?> 
            (Máximo <?php echo $maxHuespedes; ?> huéspedes)</h2>
            <form class="guest-form" method="POST">
                <div id="habitacion-<?php echo $habitacion_actual['id_cuarto']; ?>" 
                    data-max-formularios="<?php echo $maxHuespedes; ?>">
                    <div class="huesped-form">
                        <label>Nombre:
                            <input type="text" name="huéspedes[0][nombre]" required>
                        </label>
                        <label>Apellido:
                            <input type="text" name="huéspedes[0][apellido]" required>
                        </label>
                        <label>Tipo de Documento:
                            <select name="huéspedes[0][tipo_documento]" required>
                                <option value="DNI">DNI</option>
                                <option value="Pasaporte">Pasaporte</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </label>
                        <label>Número de Documento:
                            <input type="text" name="huéspedes[0][nro_documento]" required>
                        </label>
                        <label>Celular:
                            <input type="text" name="huéspedes[0][celular]">
                        </label>
                        <label>País:
                            <input type="text" name="huéspedes[0][pais]" required>
                        </label>
                        <label>Correo:
                            <input type="email" name="huéspedes[0][correo]">
                        </label>
                    </div>
                </div>
                <div class="buttons">
                    <button type="button" class="btn add-btn" 
                        onclick="agregarFormulario(<?php echo $habitacion_actual['id_cuarto']; ?>, 
                        <?php echo $maxHuespedes; ?>)">
                        Agregar Huésped
                    </button>
                    <button type="submit" class="btn submit-btn">Registrar Huéspedes</button>
                </div>
            </form>
            <!-- Botón para reiniciar sesión y salir -->
            <div style="text-align: center; margin-top: 20px;">
                <a href="?reset=1" class="btn delete-btn">Salir y Reiniciar</a>
            </div>
        </div>
    </div>
    <script>
        // Obtener el contenedor del botón "Pedidos" y el submenú
        const pedidosBtn = document.getElementById('pedidosBtn');
        const submenuPedidos = document.getElementById('submenuPedidos');
        
        // Mostrar el submenú cuando se pasa el ratón sobre "Pedidos"
        pedidosBtn.addEventListener('mouseover', function() {
            submenuPedidos.style.display = 'flex'; // Mostrar el submenú
        });

        // Mantener el submenú abierto cuando se pasa el ratón sobre el submenú
        submenuPedidos.addEventListener('mouseover', function() {
            submenuPedidos.style.display = 'flex'; // Mantenerlo abierto
        });

        // Cerrar el submenú cuando el ratón sale del botón "Pedidos" o el submenú
        pedidosBtn.addEventListener('mouseout', function() {
            setTimeout(() => {
                if (!submenuPedidos.matches(':hover') && !pedidosBtn.matches(':hover')) {
                    submenuPedidos.style.display = 'none'; // Ocultar el submenú si no está sobre él
                }
            }, 100); // Retraso para evitar un cierre inmediato
        });

        // Obtener el contenedor del botón "Reportes" y el submenú de reportes
        const reportesBtn = document.getElementById('reportesBtn');
        const submenuReportes = document.getElementById('submenuReportes');

        // Mostrar el submenú cuando se pasa el ratón sobre "Reportes"
        reportesBtn.addEventListener('mouseover', function() {
            submenuReportes.style.display = 'flex'; // Mostrar el submenú
        });

        // Mantener el submenú abierto cuando se pasa el ratón sobre el submenú
        submenuReportes.addEventListener('mouseover', function() {
            submenuReportes.style.display = 'flex'; // Mantenerlo abierto
        });

        // Cerrar el submenú cuando el ratón sale del botón "Reportes" o el submenú
        reportesBtn.addEventListener('mouseout', function() {
            setTimeout(() => {
                if (!submenuReportes.matches(':hover') && !reportesBtn.matches(':hover')) {
                    submenuReportes.style.display = 'none'; // Ocultar el submenú si no está sobre él
                }
            }, 100); // Retraso para evitar un cierre inmediato
        });
    </script>
</body>
</html>