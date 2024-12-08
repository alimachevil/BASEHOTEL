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

// Si no se ha ingresado un código de cuenta, solicitarlo
if (!isset($_SESSION['id_cuenta'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['codigo_cuenta'])) {
        $codigo_cuenta = trim($_POST['codigo_cuenta']);

        // Verificar el código de cuenta en la tabla cuenta_cobranza
        $stmt = $conn->prepare("SELECT id_cuenta FROM cuenta_cobranza WHERE id_cuenta = ?");
        $stmt->bind_param("i", $codigo_cuenta);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Si se encuentra la cuenta, guardar en sesión
            $_SESSION['id_cuenta'] = $codigo_cuenta;

            // Redirigir a la página de facturación
            header('Location: facturaconsumo.php');
            exit();
        } else {
            $error = "Cuenta no existente.";
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
        /* Estilo general */
        body {
            font-family: 'Arial', sans-serif;
            background-color: #FFFFFF;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

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
            <form action="consulta_consumo.php" method="GET">
                <button type="submit"><i class="fas fa-file"></i>Consumo Total</button>
            </form>
        </div>
    </div>
    <div class="content">
        <div class="container">
            <h1>Ingresar Código de Cuenta</h1>
            <?php if (isset($error)): ?>
                <p class="error"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <form method="POST">
                <label for="codigo_cuenta">Código de Cuenta:</label>
                <input type="text" id="codigo_cuenta" name="codigo_cuenta" placeholder="Ingrese el código de cuenta" required>
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
?>