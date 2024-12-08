<?php
session_start();

// Verifica si hay datos de pedidos en la sesión
if (!isset($_SESSION['pedidos']) || count($_SESSION['pedidos']) == 0) {
    echo "No hay pedidos en la sesión.";
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

// Fecha y hora actuales
$fecha_actual = date('Y-m-d H:i:s');

// Manejo del formulario de ID de reserva
$id_reserva = null;
$id_cuenta = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_reserva'])) {
    $id_reserva = (int)$_POST['id_reserva'];

    // Verificar si la reserva ya tiene una cuenta asociada
    $query_cuenta = "SELECT id_cuenta FROM cuenta_cobranza WHERE id_reserva = $id_reserva";
    $result_cuenta = $conn->query($query_cuenta);

    if ($result_cuenta->num_rows > 0) {
        // Recuperar el ID de la cuenta existente
        $id_cuenta = $result_cuenta->fetch_assoc()['id_cuenta'];
    } else {
        // Crear una nueva cuenta
        $query_nueva_cuenta = "INSERT INTO cuenta_cobranza (id_reserva, monto) VALUES ($id_reserva, 0)";
        if ($conn->query($query_nueva_cuenta)) {
            $id_cuenta = $conn->insert_id;
        } else {
            die("Error al crear una nueva cuenta: " . $conn->error);
        }
    }

    // Procesar los pedidos en la sesión
    $total = 0;
    foreach ($_SESSION['pedidos'] as $pedido) {
        $id_item = $pedido['id'];
        $cantidad = $pedido['cantidad'];
        $precio = $pedido['precio'];
        $subtotal = $cantidad * $precio;

        $total += $subtotal;

        if ($pedido['tipo'] === 'bebida') {
            // Insertar en consumo_bar
            $query_bar = "
                INSERT INTO consumo_bar (id_cuenta, id_bebida, cantidad, subtotal, fecha_consumo)
                VALUES ($id_cuenta, $id_item, $cantidad, $subtotal, '$fecha_actual')
            ";
            if (!$conn->query($query_bar)) {
                die("Error al registrar consumo de bebida: " . $conn->error);
            }
        } elseif ($pedido['tipo'] === 'plato') {
            // Insertar en consumo_restaurante
            $query_restaurante = "
                INSERT INTO consumo_restaurante (id_cuenta, id_plato, cantidad, subtotal, fecha_consumo)
                VALUES ($id_cuenta, $id_item, $cantidad, $subtotal, '$fecha_actual')
            ";
            if (!$conn->query($query_restaurante)) {
                die("Error al registrar consumo de plato: " . $conn->error);
            }
        }
    }

    // Actualizar el total en cuenta_cobranza
    $query_actualizar_cuenta = "
        UPDATE cuenta_cobranza SET monto = monto + $total WHERE id_cuenta = $id_cuenta
    ";
    if (!$conn->query($query_actualizar_cuenta)) {
        die("Error al actualizar el monto de la cuenta: " . $conn->error);
    }

    // Limpiar la sesión de pedidos
    unset($_SESSION['pedidos']);

    include 'confirmacion_registro.php';
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caja Restaurante-Bar</title>
     <!-- Cargar fuentes Lato y Roboto desde Google Fonts -->
     <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    
    <!-- Cargar Font Awesome para iconos -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        /* Estilo general */
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f7f7f7;
            color: #333;
            margin: 0;
            padding: 0;
        }

        h1 {
            text-align: center;
            color: #007BFF;
            margin-top: 20px;
        }

        #procesar {
            max-width: 400px;
            margin: 20px auto;
            padding: 15px;
            background-color: #fff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }

        input[type="number"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button[type="submit"] {
            background-color: #28a745;
            color: #fff;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        button[type="submit"]:hover {
            background-color: #218838;
        }

        h2 {
            text-align: center;
            margin-top: 30px;
            color: #333;
        }

        /* Tabla de resumen */
        table {
            width: 90%;
            margin: 20px auto;
            border-collapse: collapse;
            background-color: #fff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            overflow: hidden;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
        }

        th {
            background-color: #007BFF;
            color: #fff;
        }

        td {
            border-bottom: 1px solid #ddd;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        tfoot td {
            font-weight: bold;
            font-size: 16px;
            background-color: #f9f9f9;
        }

        /* Estilo responsivo */
        @media (max-width: 600px) {
            table {
                font-size: 14px;
            }

            form {
                width: 90%;
            }
        }

        /*ESTILOS NUEVOS*/
        /* Estilos generales */
        body {
            font-family: 'Lato', 'Roboto', sans-serif !important;
            margin: 0;
            padding: 0;
            display: flex;
            height: 100vh;
            background-color: #f4f4f4;
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
            border: none;
        }
        .menu {
            display: flex;
            flex-direction: column;
            width: 100%;
            border: none;
        }
        .menu form {
            width: 100%;
            margin-bottom: 0;
            border: none;
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
            border: none;
        }
        .menu button i {
            margin-right: 10px;
            border: none;
        }

        /* Estilos del submenú (opciones dentro del botón PEDIDOS) */
        .submenu {
            display: none;
            flex-direction: column;
            width: 100%;
            margin-top: 10px;
            border: none;
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
            border: none;
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
        <h1>Caja Restaurante-Bar</h1>
        <form id="procesar" method="POST">
            <label for="id_reserva">ID de Reserva:</label>
            <input type="number" id="id_reserva" name="id_reserva" required>
            <button type="submit">Procesar Pedido</button>
        </form>

        <?php if (isset($_SESSION['pedidos']) && count($_SESSION['pedidos']) > 0): ?>
            <h2>Resumen del Pedido</h2>
            <table border="1">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Nombre</th>
                        <th>Cantidad</th>
                        <th>Precio</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $total = 0;
                    foreach ($_SESSION['pedidos'] as $pedido):
                        $id_item = $pedido['id'];
                        $cantidad = $pedido['cantidad'];
                        $precio = $pedido['precio'];
                        $subtotal = $pedido['cantidad'] * $pedido['precio'];
                        $total += $subtotal;
                    ?>
                        <tr>
                            <td><?php echo ucfirst($pedido['tipo']); ?></td>
                            <td><?php echo htmlspecialchars($pedido['nombre']); ?></td>
                            <td><?php echo $pedido['cantidad']; ?></td>
                            <td><?php echo number_format($pedido['precio'], 2); ?></td>
                            <td><?php echo number_format($subtotal, 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4">Total</td>
                        <td><?php echo number_format($total, 2); ?></td>
                    </tr>
                </tfoot>
            </table>
        <?php endif; ?>
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
