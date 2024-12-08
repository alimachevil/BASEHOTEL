<?php
// Iniciar la sesión
session_start();

// Conexión a la base de datos
$host = 'srv1006.hstgr.io';
$user = 'u472469844_est18';
$pass = '#Bd00018';
$dbname = 'u472469844_est18';
$conn = new mysqli($host, $user, $pass, $dbname);

// Verificar la conexión
if ($conn->connect_error) {
    die("Error en la conexión a la base de datos: " . $conn->connect_error);
}

// Manejo de pedidos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $cantidad = $_POST['cantidad'];
    $tipo = $_POST['tipo']; // 'bebida', 'plato', o 'habitacion'

    // Validar entrada
    if (!empty($id) && !empty($cantidad) && $cantidad > 0) {
        if ($tipo === 'plato') {
            // Consultar el precio del plato
            $query_plato = "SELECT precio, nombre_plato FROM restaurante WHERE id_plato = $id";
            $result_plato = $conn->query($query_plato);
            $plato = $result_plato->fetch_assoc();
            $precio = $plato['precio'];
            $nombre = $plato['nombre_plato'];
        } elseif ($tipo === 'bebida') {
            // Consultar el precio de la bebida
            $query_bebida = "SELECT precio, nombre_bebida FROM bar WHERE id_bebida = $id";
            $result_bebida = $conn->query($query_bebida);
            $bebida = $result_bebida->fetch_assoc();
            $precio = $bebida['precio'];
            $nombre = $bebida['nombre_bebida'];
        } elseif ($tipo === 'habitacion') {
            // Consultar el precio del servicio a la habitación
            $query_habitacion = "SELECT precio, nombre_producto FROM servicio_habitacion WHERE id_servicio = $id";
            $result_habitacion = $conn->query($query_habitacion);
            $habitacion = $result_habitacion->fetch_assoc();
            $precio = $habitacion['precio'];
            $nombre = $habitacion['nombre_producto'];
        }

        // Guardar en la sesión
        $_SESSION['pedidos'][] = [
            'id' => $id,
            'cantidad' => $cantidad,
            'tipo' => $tipo,
            'precio' => $precio,
            'nombre' => $nombre
        ];

        // Redirigir con mensaje
        header('Location: pedido_restaurante_bar.php?mensaje=Pedido agregado correctamente');
        exit();
    }
}

// Consultar platos, bebidas y servicios a la habitación
$platos = $conn->query("SELECT * FROM restaurante");
$bebidas = $conn->query("SELECT * FROM bar");
$habitacion = $conn->query("SELECT * FROM servicio_habitacion");

// Verificar mensaje
$mensaje = isset($_GET['mensaje']) ? htmlspecialchars($_GET['mensaje']) : '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos Restaurante y Bar</title>
    <!-- Cargar fuentes Lato y Roboto desde Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    
    <!-- Cargar Font Awesome para iconos -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
    /* General Styles */
    body {
        font-family: 'Roboto', Arial, sans-serif;
        background-color: #f3f4f6; /* Color suave para el fondo */
        margin: 0;
        padding: 0;
        color: #333; /* Texto legible */
        line-height: 1.6; /* Espaciado agradable */
        overflow-x: hidden; /* Ocultar el desplazamiento horizontal */
    }

    .container {
        width: 100%;
        margin: 0 auto;
        align-items: center;
        padding: 20px;
    }

    .content {
        padding-left: 260px;
    }

    /* Headers */
    h1, h2 {
        text-align: center;
        color: #222;
        margin-bottom: 20px;
    }

    h1 {
        font-size: 2.5em;
        font-weight: 700;
    }

    h2 {
        font-size: 2em;
        font-weight: 600;
        border-bottom: 2px solid #007bff;
        display: inline-block;
        padding-bottom: 5px;
    }

    #panel {
        
        border-bottom: 2px solid #333333;
    
    }

    /* Search Bar */
    .busqueda {
        margin-bottom: 30px;
        display: flex;
        justify-content: center;
    }

    .busqueda input {
        width: 100%;
        max-width: 600px;
        padding: 12px 15px;
        font-size: 16px;
        border: 2px solid #ccc;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        transition: border-color 0.3s ease, box-shadow 0.3s ease;
    }

    .busqueda input:focus {
        border-color: #007bff;
        box-shadow: 0 0 8px rgba(0, 123, 255, 0.4);
        outline: none;
    }

    /* Menu Items */
    .menu {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        justify-content: center;
    }

    .item {
        width: 250px;
        border: 1px solid #ddd;
        border-radius: 10px;
        padding: 15px;
        text-align: center;
        background-color: #fff;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .item:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    .item img {
        max-width: 100%;
        height: auto;
        border-radius: 10px;
        margin-bottom: 10px;
    }

    .item h3 {
        margin: 10px 0;
        font-size: 1.2em;
        font-weight: 600;
        color: #444;
    }

    .item p {
        margin: 5px 0;
        color: #666;
    }

    /* Quantity Input */
    .item input[type="number"] {
        width: 60px;
        padding: 5px;
        font-size: 16px;
        text-align: center;
        border: 1px solid #ccc;
        border-radius: 5px;
        margin-top: 10px;
    }

    /* Buttons */
    .item button {
        background-color: #feb657;
        color: white;
        border: none;
        padding: 10px 15px;
        font-size: 14px;
        font-weight: 600;
        text-transform: uppercase;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.3s ease, box-shadow 0.3s ease;
    }

    .item button:hover {
        background-color: #C88942;
        box-shadow: 0 3px 6px rgba(0, 123, 255, 0.4);
    }

    /* Notification Messages */
    .mensaje {
        text-align: center;
        color: green;
        font-weight: bold;
        background-color: #e7f9e7;
        border: 1px solid #c3e6c3;
        padding: 10px;
        border-radius: 5px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .item {
            width: 100%;
        }

        .busqueda input {
            width: 90%;
        }
    }
        .anclas {
        display: flex;
        justify-content: center;
        gap: 20px;
        margin: 20px 0;
    }

    .enlace {
        display: flex;
        align-items: center;
        text-decoration: none;
        color: #333;
        font-size: 18px;
        font-weight: bold;
        padding: 10px 20px;
        border: 2px solid transparent;
        border-radius: 5px;
        transition: all 0.3s ease;
        background-color: #feb657;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .enlace:hover {
        background-color: #C88942;
        color: white;
        border-color: #C88942;
        box-shadow: 0 4px 8px rgba(0, 123, 255, 0.4);
    }

    .icono {
        width: 24px;
        height: 24px;
        margin-right: 8px;
    }
    /* ESTILOS NUEVOS*/
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
        position: fixed;
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
    .admin-menu {
        display: flex;
        flex-direction: column;
        width: 100%;
    }
    .admin-menu form {
        width: 100%;
        margin-bottom: 0;
    }
    .admin-menu button {
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
    .admin-menu button:hover {
        background-color: #D69C4F;
        color: black;
    }
    .admin-menu button i {
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
    .admin-menu button.active + .submenu {
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

    </style>
    <script>
        function buscar(inputId, menuClass) {
            const input = document.getElementById(inputId);
            const filter = input.value.toLowerCase();
            const items = document.querySelectorAll(`.${menuClass} .item`);

            items.forEach(item => {
                const text = item.textContent.toLowerCase();
                item.style.display = text.includes(filter) ? '' : 'none';
            });
        }
    </script>
</head>
<body>
    <div class="sidebar">
        <!-- Espacio para el logo -->
        <img src="images/logo.png" alt="Logo">
        <h2 id="panel">Panel de Control del Administrador</h2>
        <div class="admin-menu">
            <form action="reserva_pago_opcion1.php" method="GET">
                <button type="submit"><i class="fas fa-user"></i>Datos de Acompañantes</button>
            </form>
            <form action="pedido_restaurante_bar.php" method="GET">
                <button type="button" id="pedidosBtn"><i class="fas fa-utensils"></i>Pedidos</button>
                <div class="submenu" id="submenuPedidos">
                    <a href="pedido_restaurante_bar.php#restaurante"><i class="fas fa-cocktail"></i>Restaurante</a>
                    <a href="pedido_restaurante_bar.php#bar"><i class="fas fa-beer"></i>Bar</a>
                    <a href="pedido_restaurante_bar.php#habitacion"><i class="fas fa-bed"></i>Habitación</a>
                </div>
            </form>
            <form action="reportes.php" method="GET">
                <button type="button" id="reportesBtn"><i class="fas fa-file-alt"></i>Reportes</button>
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
        <h1>Pedidos Restaurante y Bar</h1>
        <?php if ($mensaje): ?>
            <p class="mensaje"><?php echo $mensaje; ?></p>
        <?php endif; ?>
        
        <div class="anclas">
            <a href="#restaurante" class="enlace">
            <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAYAAACqaXHeAAAACXBIWXMAAAsTAAALEwEAmpwYAAAENUlEQVR4nO2aa4hVVRTHf844kxpNJk4oIROSKRozoaYoyFBWDH7oSyoVhKaUDkkII4yPfIDvD0ERBVbQKCI6ISoIag+sKAxfIGHE4HzQCdIP+R6foxML1obF7px7Lvfec+/Mbf9hfzhrrbPXOf+zz15rr70hICAgICAgICAgJ1QAE4CXgeeASoqPMcBKYD9wHDgFPJO20wHAYuA80GtaF/CBEpM26oEfPP+uvZ+m40pgT4xj1w4AVSk+w2qgx/i7qM90Xa/lI6SGj43jfcALQA0wEfjG6L7I0McjQCtwFrgBdAJbgKFZjLxtxofc9xYwUPUX0iZgKvBAnXwWY/OR6h8CjTEvfzRm5PwJ1Gbwv8rYtivxFqkT0G4etDrGRr7G72p3MEK/zrzEbmAB8LkZ0rti+m00Nu0xE26qBAwB7qqD5gTb+WrXEzGsu1T3tSdfofJ7wOMRff6m+g59FopNQKP5cqMSbGv1FxDbJiMfbPp4zbvnWaN73tM1Gd0rGfymSsDb2rnMtNngktq/Z2Q15kVmevZPGd0UT9em8tMJPlMloFk7/ztL+3Nq35InARUa5kS+vJQEvKGd384y0bmi9jLJ5UNAnZFLFCoZAZPMg0j6mwmjje2MPAmYauQjSknAQPNV1ybYtqpdNzAoTwJmGXlc6C1aHvCpOvgHGB5jM9RMgF95ulwImGbkUeGxqATUaeoqTn4EHvP0jwLfmblCVmr5EvC0kTeUmgA0rNlcXJy9CizRJMXplvJf5EKALKquqXwRfYAAwTJvNWabrBU+JBq55gEuBZfR1ScIQFeBe4FbZsgf0H+WAhPwpllgNfQVAhx2qFMhIwm5EiALnz9U92uGPKRsCRC8bvQb+R8SgIZVS4IUSEpCwIua7LTqAqVXawBOJlEhDQIkIvxk7H7WMFlUAp4A7sdEABsJRqZAgOAXz9eRYhMwyjj/S3MB11yxQ9q4lAg4a7JRqUwtLCUBkz3d+CISIPVBH/2KgN4MrWwJqAKuJrx8j645ypIAdIE0R9shE0GcTPYXKGcCopbX35M9AgGEEUC//wXGArMj5oCk/78sokBbQhT4NmGTtF8TsNLo72jr1dS62+i2lyMBg4GbZl+w2osClVpJcoUPv57Y7wmoNzqxiwqD1WahNbfcCJhidJL3x+UBrgA6L8Z/IIAwAgi/AGEOoGST4JNmMnvJ09l9PFurK+Qk6GqQWz35ALPUtocyCo4Kc2BBKsIWX6r8sjm6VmgCdqq+09ubnG36n07KWGucyWnNNVqcdLLNEfcUioDp5vyR7EWuV+LvqexkMU6qVukRuLhcXs4C+phsbFzF+BNzj4M7gyBnkuLQYkiwrStDBllwVALv6IZlh46EdyOGvk2FD+s5QLehIadHzuhpT4dNwDEzSuIwQzdNZcvsBLABGFagdwsICAgICAgIoOzxL0nBugjw35njAAAAAElFTkSuQmCC" alt="Restaurante" class="icono">
                Restaurante
            </a>
            <a href="#bar" class="enlace">
            <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADIAAAAyCAYAAAAeP4ixAAAACXBIWXMAAAsTAAALEwEAmpwYAAADlUlEQVR4nO2ZSYhUVxSGP4eoqFGkdacJjRNxAudZUQQRhKBuFcQBREXQGF3YuHBEjRgEJ3ChCKJuXDgEFBuHmODYKlHiwmGl4oDz2HY/Ofg/ORZdVa+qu1+9gvrgUq9enTvVveeec+6B7AwA1gJXgVdAoPIGuAKsAwaRYPoBx93As5W/gP4kjAVAtQZYC5wBFgOjge4qo4Cl+i2czEdgIQlhsxvYOWBIhDrDgX9dvT9IwEoEKn8CzXKo+wOw1dVfQgF1otpNIl+2uW3WiwJw3G2ndCsxFXgAjMzQTnOdZtZWJTEz0Cn20AxyOyRn/3omhrkt1pcYWatO7QTKRIXk9kVo86Jk7fCIjSp1akdsJsZLbrfbRvOAU8DyFNnfJXuBGHmpTs1OhLQE9gJHgGVAF6AF8AxYpcPhmttCZ1PaHKf3b3M8/fKmjRtMN/e+J1DjfrPn/cAd4H+5KEGBSmW2iZjF9owANgIHgUMqjws4gUDlci5bqy7ma2s9lfwG4mOD+rS+u2ZT9kzWeCLwGdij/f9JdabT+ExXX9U6cNKyJo3ChpQBTyRjkwhXx76/z2J76stQ9RHIhcoac4QG0YxZKkv0+3WgiXu/PUa92Bl15sdU4bzsg2emVmRwHY5iZQyTOKO+ItHX7XvzYqNSpiPZ6h1IWbF8aaK2rM17QKdcG5jt/oVdOfwLvwAvVG8F9adCbb2W4c2LTW4y/yhoiqJj/znD+Wu+nfO1bo30dQr1ZJ7bZrVy7X8DxgA9gHK58ouA05IJ3KddUvTJo98+7oJjJQ1Eb+BoDgp5UnoW7u27QMcc+jPZu6p7qIF07TvsZmS1gqVQD6x8AC4pPvcxR2u5EKFPFEXP/Ol3VW00OtbJj1lkOgMPIwZhPjx+BPxEwrCo850GaDqXjlmSMZ0cS0KZ4QYZujaeUbqkMJm5JJxNabzWn11IsIUioKmiTBvwTaAd0FZ+m707UYdLlFjaA7c08MMq9nwb6ECRUe5CgdBomq0qSibI+luZRJETqBQ9QWkiCaO0IkmjtCJJo7QiSaO0IoXGwuLJwHpltsIVOaV3kyOEzgWjly7Z/nYp70ylWrIVuuwrKK0Urt5IGWSN0hYWBU6TO1+u5y1K3/nMWKCga47ajJUw/x4O5Lnyj1N1N5yNMsnuVd2wnQcNcduYSxImvGGs0ndLmuZLS11ahMmn2piSSdyPIa1wL46J2L1wY08kXSbtG18AyOHjgcXDFRoAAAAASUVORK5CYII=" alt="Bar" class="icono">
                Bar
            </a>
            <a href="#habitacion" class="enlace">
            <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADIAAAAyCAYAAAAeP4ixAAAACXBIWXMAAAsTAAALEwEAmpwYAAAEPklEQVR4nO2YXYhVVRTHf1mNSWaZQRNmWU2UY2E9+JX1EFhW9FKThD5pFKRo0EMgPvQQGD2UHyNRkEWBKb0URCE0hThlgmAJY6VpaVmUQX7rjGXeWPLfsDqcs885d+6dORPzh829d6+Pu9fZ6/PAMIYOJgOrgF3ASa1d2jNa5dECrAXOArWMZbRO8VYSLUCXDtsLrAamAZdqTQfWAH3i6aqqMWt1wJ+B2yN8U4CD4rWbqRQmy2V6c4zwxvRJpp0KYZWesH0WRadkVlIhfKNDTS0hM10yPVQIJ3So0SVkRkvmOBXC8ToMGSOZY1TQtaaVkJnRJNe6HngdeKnkg/1PsFudKJuuX6Gx2OyKrxlTV/rtU2rNwx3AmSal353OkHX1KAjp9GCOMWbEL+K16t8IXAzMA9YDfzpDfgM2APPFU1hZaFH6ZNgM+amtmXKnM65FKaw8gkfVTdRylj3gR4oqbZEBsabxb91Ef424wMWZra+AJcAk199N0t7Xjq9TsoVjZqUy0gmtHgV2o2IiGGFt0RM5hxsBPCneSvV4j+lAp4FZJeTudsZ0MMgwl/xJh7GbKIunJLvfu/diYCPQDezWJPhJAWVdCvrDwA/Al8AbwNPAlTmy811MmMuUxQiXoi3TncdfGQHdFlHUlpNdLGVOjMhvEJ8Fcb14RjreDRuWUh+X79kB3xPDgoiSBeJ5X0//JuBeYBGwXbTPIsG7Tzy39sOQdunYm8WwSAxvRpS8JZ6lKbQr3PSY9cRP1tGgZnXepisVt4nh+ywG0Yznzgz6w+5PvIuOB/4oUPhqJZc1vKmBFFqE1hR6q2hHgQsL3Fq3C2gz5PeBMsTwoRgs12fl/03EcblrO55NoV/nYsWq9lUJ+khgtoryAXfoI8B9FMRzkZZ+jWjLC+ixPzynoneL279BNaCm5DDW0a4F3tawlsyELwMTKIGZEt6RQtsh2j0Fdb0m/m1yxZvdTW3VzQXcCBxK9F4rgLvqrDfnG8dTahxtnA0Y42aWS0pklx91sFeBX/V9cyJr2a1857rq9kZPaXPc3gMugMvAeql/3JNOGmFtxqei9SRuqd94QYrtagNWaO/FiNwcGZzEasl+lLhNK5rvuHljPA3G/VK+xe1t0d6DEblTevpzE/sXqXuwT4/npdPe4tjk2XBcpgGqV6lwpL6fzbn64D69BRLCPGU10/kQTUTIULP0NEMmicGnzKOR98hTdXv9bR5LvSJappVVWzyCER+4meGaBM9El2YHZNLr0J99rFVL8f0sQ0apToR6ZK6KPnuc3lib0zBcLR8+opXVf6UZYhinYS209a3KWvb720SNajr2uMPFOuI0Q4IbhbE2rMOq8AOKde4AsRkly5DQW23Sm5gvmpVm87DQHS42NcYMqQTa3OFspB2yhqDsYkHKUDekDIYNqRoG7UY+b8LLgP+NId2DYcgwqBD+BeSX0gdQMJjCAAAAAElFTkSuQmCC" alt="Habitacion" class="icono">
                Habitacion
            </a>
        </div>

        <h2 id="restaurante">Platos</h2>
        <div class="busqueda">
            <input type="text" id="busquedaPlatos" placeholder="Buscar platos..." onkeyup="buscar('busquedaPlatos', 'menuPlatos')">
        </div>
        <div class="menu menuPlatos">
            <?php while ($plato = $platos->fetch_assoc()): ?>
                <div class="item">
                    <img src="img/platos/<?php echo $plato['id_plato']; ?>.jpg" alt="Imagen de <?php echo htmlspecialchars($plato['nombre_plato']); ?>">
                    <h3><?php echo htmlspecialchars($plato['nombre_plato']); ?></h3>
                    <p><?php echo htmlspecialchars($plato['tipo']); ?></p>
                    <p><strong>S/ <?php echo number_format($plato['precio'], 2); ?></strong></p>
                    <form method="POST">
                        <input type="hidden" name="id" value="<?php echo $plato['id_plato']; ?>">
                        <input type="hidden" name="tipo" value="plato">
                        <input type="number" name="cantidad" min="1" value="1" required>
                        <button type="submit">Agregar Pedido</button>
                    </form>
                </div>
            <?php endwhile; ?>
        </div>

        <h2 id="bar">Bebidas</h2>
        <div class="busqueda">
            <input type="text" id="busquedaBebidas" placeholder="Buscar bebidas..." onkeyup="buscar('busquedaBebidas', 'menuBebidas')">
        </div>
        <div class="menu menuBebidas">
            <?php while ($bebida = $bebidas->fetch_assoc()): ?>
                <div class="item">
                    <img src="img/bebidas/<?php echo $bebida['id_bebida']; ?>.jpg" alt="Imagen de <?php echo htmlspecialchars($bebida['nombre_bebida']); ?>">
                    <h3><?php echo htmlspecialchars($bebida['nombre_bebida']); ?></h3>
                    <p><strong>S/ <?php echo number_format($bebida['precio'], 2); ?></strong></p>
                    <form method="POST">
                        <input type="hidden" name="id" value="<?php echo $bebida['id_bebida']; ?>">
                        <input type="hidden" name="tipo" value="bebida">
                        <input type="number" name="cantidad" min="1" value="1" required>
                        <button type="submit">Agregar Pedido</button>
                    </form>
                </div>
            <?php endwhile; ?>
        </div>

        <!-- Menú del servicio a la habitación -->
        <h2 id="habitacion">Servicio a la Habitación</h2>
        <div class="busqueda">
            <input type="text" id="buscar_habitacion" placeholder="Buscar en servicio a la habitación..." onkeyup="buscar('buscar_habitacion', 'menu_habitacion')">
        </div>
        <div class="menu menu_habitacion">
            <?php while ($item = $habitacion->fetch_assoc()): ?>
                <div class="item">
                <img src="img/bebidas/<?php echo $item['id_servicio']; ?>.jpg" alt="Imagen de <?php echo htmlspecialchars($item['nombre_producto']); ?>">
                    <h3><?php echo $item['nombre_producto']; ?></h3>
                    <p><strong>S/ <?php echo number_format($item['precio'], 2); ?></strong></p>
                    <form method="POST">
                        <input type="hidden" name="id" value="<?php echo $item['id_servicio']; ?>">
                        <input type="hidden" name="tipo" value="habitacion">
                        <input type="number" name="cantidad" min="1" value="1" required>
                        <button type="submit">Agregar Pedido</button>
                    </form>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php if (isset($_SESSION['pedidos']) && count($_SESSION['pedidos']) > 0): ?>
    <form action="caja_restaurante_bar.php" method="POST" style="text-align: center; margin-top: 20px;">
        <button type="submit" style="background-color: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;">
            Enviar a Caja
        </button>
    </form>
    <?php endif; ?>
    </div>

    <script>
        // Script para manejar los submenús
        const pedidosBtn = document.getElementById('pedidosBtn');
        const submenuPedidos = document.getElementById('submenuPedidos');
        const reportesBtn = document.getElementById('reportesBtn');
        const submenuReportes = document.getElementById('submenuReportes');

        // Mostrar y ocultar submenú de pedidos
        pedidosBtn.addEventListener('mouseover', function() {
            submenuPedidos.style.display = 'flex';
        });
        submenuPedidos.addEventListener('mouseover', function() {
            submenuPedidos.style.display = 'flex';
        });
        pedidosBtn.addEventListener('mouseout', function() {
            setTimeout(() => {
                if (!submenuPedidos.matches(':hover') && !pedidosBtn.matches(':hover')) {
                    submenuPedidos.style.display = 'none';
                }
            }, 100);
        });

        // Mostrar y ocultar submenú de reportes
        reportesBtn.addEventListener('mouseover', function() {
            submenuReportes.style.display = 'flex';
        });
        submenuReportes.addEventListener('mouseover', function() {
            submenuReportes.style.display = 'flex';
        });
        reportesBtn.addEventListener('mouseout', function() {
            setTimeout(() => {
                if (!submenuReportes.matches(':hover') && !reportesBtn.matches(':hover')) {
                    submenuReportes.style.display = 'none';
                }
            }, 100);
        });
    </script>
</body>
</html>