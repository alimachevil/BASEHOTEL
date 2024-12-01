<?php
// Iniciar la sesión
session_start();

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

// Manejo de pedidos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $cantidad = $_POST['cantidad'];
    $tipo = $_POST['tipo']; // 'bebida' o 'plato'

    // Validar entrada
    if (!empty($id) && !empty($cantidad) && $cantidad > 0) {
        // Obtener el precio del producto dependiendo de si es plato o bebida
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

// Consultar platos y bebidas
$platos = $conn->query("SELECT * FROM restaurante");
$bebidas = $conn->query("SELECT * FROM bar");

// Verificar mensaje
$mensaje = isset($_GET['mensaje']) ? htmlspecialchars($_GET['mensaje']) : '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos Restaurante y Bar</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 1200px;
            margin: auto;
            padding: 20px;
        }
        h1, h2 {
            text-align: center;
        }
        .busqueda {
            margin-bottom: 20px;
        }
        .busqueda input {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .menu {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }
        .item {
            width: 250px;
            border: 1px solid #ccc;
            border-radius: 10px;
            padding: 10px;
            text-align: center;
            background-color: white;
        }
        .item img {
            max-width: 100%;
            height: auto;
            border-radius: 5px;
        }
        .item h3 {
            margin: 10px 0;
        }
        .item p {
            margin: 5px 0;
        }
        .item input[type="number"] {
            width: 60px;
            padding: 5px;
            text-align: center;
        }
        .item button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        .item button:hover {
            background-color: #0056b3;
        }
        .mensaje {
            text-align: center;
            color: green;
            font-weight: bold;
            margin-bottom: 20px;
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
    <div class="container">
        <h1>Pedidos Restaurante y Bar</h1>
        <?php if ($mensaje): ?>
            <p class="mensaje"><?php echo $mensaje; ?></p>
        <?php endif; ?>
        
        <a href="#restaurante">restaruante</a>
        <a href="#bar">bar</a>

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
    </div>
    <?php if (isset($_SESSION['pedidos']) && count($_SESSION['pedidos']) > 0): ?>
    <form action="caja_restaurante_bar.php" method="POST" style="text-align: center; margin-top: 20px;">
        <button type="submit" style="background-color: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;">
            Enviar a Caja
        </button>
    </form>
    <?php endif; ?>

</body>
</html>
