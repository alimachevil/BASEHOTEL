<?php
// Inicia una sesión
session_start();

// Conexión a la base de datos
$host = "localhost"; // Cambiar según tu configuración
$user = "root";      // Usuario de la base de datos
$password = "";      // Contraseña de la base de datos
$dbname = "hotel_db";   // Nombre de la base de datos
$conn = new mysqli($host, $user, $password, $dbname);

// Verificar la conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Variables para manejar la lógica de la página
$reporteSeleccionado = isset($_GET['reporte']) ? $_GET['reporte'] : null;
$resultado = null;
$campos = null;

// Procesar los datos de los reportes
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $reporteSeleccionado) {
    switch ($reporteSeleccionado) {
        case 'listado_huespedes':
            $fechaInicio = $_GET['fechaInicio'] ?? null;
            $fechaFin = $_GET['fechaFin'] ?? null;

            if ($fechaInicio && $fechaFin) {
                $stmt = $conn->prepare("CALL ListadoHuespedes(?, ?)");
                $stmt->bind_param("ss", $fechaInicio, $fechaFin);
                $stmt->execute();
                $resultado = $stmt->get_result();
                $campos = ['Nombre Cliente', 'Apellido Cliente', 'Documento Cliente', 'Tipo Documento Cliente', 'Celular Cliente', 'Correo Cliente', 'Pais Cliente', 'Nombre Acompañante', 'Apellido Acompañante', 'Documento Acompañante', 'Tipo Documento Acompañante', 'Celular Acompañante', 'Correo Acompañante', 'Pais Acompañante']; // Encabezados de tabla
            }
            break;

        case 'ranking_habitaciones':
            $fechaInicio = $_GET['fechaInicio'] ?? null;
            $fechaFin = $_GET['fechaFin'] ?? null;

            if ($fechaInicio && $fechaFin) {
                $stmt = $conn->prepare("CALL RankingHabitaciones(?, ?)");
                $stmt->bind_param("ss", $fechaInicio, $fechaFin);
                $stmt->execute();
                $resultado = $stmt->get_result();
                $campos = ['Número de Habitación', 'Días Ocupados']; // Encabezados de tabla
            }
            break;

        case 'reporte_monto_restaurante':
            $fechaInicio = $_GET['fechaInicio'] ?? null;
            $fechaFin = $_GET['fechaFin'] ?? null;

            if ($fechaInicio && $fechaFin) {
                $stmt = $conn->prepare("CALL VentasRestaurante(?, ?)");
                $stmt->bind_param("ss", $fechaInicio, $fechaFin);
                $stmt->execute();
                $resultado = $stmt->get_result();
                $campos = ['Monto Total Restaurante']; // Encabezados de tabla
            }
            break;

        case 'ranking_productos_restaurante':
            $mes = $_GET['mes'] ?? null;
            $anio = $_GET['anio'] ?? null;

            if ($mes && $anio) {
                $stmt = $conn->prepare("CALL RankingProductosRestaurante(?, ?)");
                $stmt->bind_param("ii", $mes, $anio);
                $stmt->execute();
                $resultado = $stmt->get_result();
                $campos = ['Nombre Plato', 'Cantidad Vendida']; // Encabezados de tabla
            }
            break;

        case 'ranking_bebidas_bar':
            $mes = $_GET['mes'] ?? null;
            $anio = $_GET['anio'] ?? null;

            if ($mes && $anio) {
                $stmt = $conn->prepare("CALL RankingBebidasBar(?, ?)");
                $stmt->bind_param("ii", $mes, $anio);
                $stmt->execute();
                $resultado = $stmt->get_result();
                $campos = ['Nombre Bebida', 'Cantidad Vendida']; // Encabezados de tabla
            }
            break;

        default:
            $resultado = null;
            $campos = null;
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control del Administrador</title>
    
    <!-- Cargar fuentes Lato y Roboto desde Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    
    <!-- Cargar Font Awesome para iconos -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

    <style>
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

        /* Estilos generales del contenido principal */
.content {
    flex-grow: 1;
    padding: 30px;
    background-color: #f9f9f9; /* Fondo claro para contrastar con la barra lateral */
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Sombra suave */
    font-family: 'Lato', sans-serif;
}

.content h2 {
    font-size: 28px;
    color: #333;
    margin-bottom: 20px;
    text-align: center;
}

.content p {
    font-size: 16px;
    color: #666;
    margin-bottom: 30px;
    text-align: center;
}

/* Estilo del encabezado del reporte seleccionado */
.content h3 {
    font-size: 24px;
    color: #444;
    margin-bottom: 20px;
    text-align: center;
}

/* Formularios */
.formulario {
    display: flex;
    flex-wrap: wrap; /* Adaptable a pantallas pequeñas */
    gap: 15px; /* Espaciado entre elementos */
    justify-content: center;
    margin-bottom: 20px;
}

.formulario label {
    font-size: 16px;
    font-weight: bold;
    color: #333;
    margin-bottom: 5px;
}

.formulario input,
.formulario select,
.formulario button {
    padding: 10px 15px;
    font-size: 16px;
    border: 1px solid #ccc;
    border-radius: 5px;
    outline: none;
    transition: border-color 0.3s, box-shadow 0.3s;
}

.formulario input:focus,
.formulario select:focus {
    border-color: #D69C4F;
    box-shadow: 0 0 5px rgba(214, 156, 79, 0.5);
}

.formulario button {
    background-color: #D69C4F;
    color: #fff;
    border: none;
    cursor: pointer;
    font-weight: bold;
    transition: background-color 0.3s, transform 0.2s;
}

.formulario button:hover {
    background-color: #b47d3c;
    transform: scale(1.05);
}

/* Tabla */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    background-color: #fff; /* Fondo blanco */
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Sombra suave */
    border-radius: 10px;
    overflow: hidden; /* Para bordes redondeados */
}

table thead {
    background-color: #D69C4F; /* Color dorado */
    color: white;
    font-size: 18px;
}

table th,
table td {
    text-align: left;
    padding: 12px 15px;
    border: 1px solid #ddd;
}

table th {
    font-weight: bold;
}

table tbody tr:nth-child(even) {
    background-color: #f5f5f5; /* Alterna el color de las filas */
}

table tbody tr:hover {
    background-color: #f1e0c5; /* Color de fondo al pasar el ratón */
}

/* Mensaje cuando no hay datos */
.content p {
    font-size: 16px;
    color: #888;
    text-align: center;
    margin: 30px 0;
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
                <button type="submit" id="reportesBtn"><i class="fas fa-file-alt"></i>Reportes</button>
                <div class="submenu" id="submenuReportes">
                    <a href="?reporte=listado_huespedes"><i class="fas fa-users"></i>Listado de Huéspedes</a>
                    <a href="?reporte=ranking_habitaciones"><i class="fas fa-bed"></i>Ranking de Cuartos</a>
                    <a href="?reporte=reporte_monto_restaurante"><i class="fas fa-utensils"></i>Reporte Monto Restaurante</a>
                    <a href="?reporte=ranking_productos_restaurante"><i class="fas fa-cocktail"></i>Ranking Productos Restaurante</a>
                    <a href="?reporte=ranking_bebidas_bar"><i class="fas fa-beer"></i>Ranking Bebidas Bar</a>
                </div>
            </form>
        </div>
    </div>


    <!-- Contenido principal -->
    <div class="content">
        <h2>Panel de Control de Reportes</h2>
        <p>Selecciona el reporte que deseas ver</p>

        <!-- Sección para mostrar el reporte seleccionado -->
        <?php if ($reporteSeleccionado): ?>
            <h3>Reporte: <?= ucfirst(str_replace('_', ' ', $reporteSeleccionado)) ?></h3>

            <?php if ($reporteSeleccionado == 'listado_huespedes' || $reporteSeleccionado == 'ranking_habitaciones' || $reporteSeleccionado == 'reporte_monto_restaurante'): ?>
                <!-- Formulario para reportes con fecha de inicio y fin -->
                <form method="GET" action="" class="formulario">
                    <label for="fechaInicio">Fecha Inicio:</label>
                    <input type="date" id="fechaInicio" name="fechaInicio" required>
                    <label for="fechaFin">Fecha Fin:</label>
                    <input type="date" id="fechaFin" name="fechaFin" required>
                    <button type="submit">Generar Reporte</button>
                    <input type="hidden" name="reporte" value="<?= $reporteSeleccionado ?>">
                </form>
            <?php elseif ($reporteSeleccionado == 'ranking_productos_restaurante' || $reporteSeleccionado == 'ranking_bebidas_bar'): ?>
                <!-- Formulario para reportes con mes y año -->
                <form method="GET" action="" class="formulario">
                    <label for="mes">Mes:</label>
                    <select name="mes" id="mes" required>
                        <option value="1">Enero</option>
                        <option value="2">Febrero</option>
                        <option value="3">Marzo</option>
                        <option value="4">Abril</option>
                        <option value="5">Mayo</option>
                        <option value="6">Junio</option>
                        <option value="7">Julio</option>
                        <option value="8">Agosto</option>
                        <option value="9">Septiembre</option>
                        <option value="10">Octubre</option>
                        <option value="11">Noviembre</option>
                        <option value="12">Diciembre</option>
                    </select>

                    <label for="anio">Año:</label>
                    <select name="anio" id="anio" required>
                        <option value="2023">2023</option>
                        <option value="2024">2024</option>
                        <option value="2025">2025</option>
                    </select>

                    <button type="submit">Generar Reporte</button>
                    <input type="hidden" name="reporte" value="<?= $reporteSeleccionado ?>">
                </form>
            <?php endif; ?>

            <?php if ($resultado): ?>
                <table border="1">
                    <thead>
                        <tr>
                            <?php foreach ($campos as $campo): ?>
                                <th><?= $campo ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $resultado->fetch_assoc()): ?>
                            <tr>
                                <?php foreach ($row as $campo => $valor): ?>
                                    <td><?= $valor ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No hay datos disponibles para este reporte.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script>
        // Manejar la expansión del submenú de los reportes
        const menuButton = document.querySelector('.menu-button');
        menuButton.addEventListener('click', function() {
            this.classList.toggle('active');
        });
    </script>


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