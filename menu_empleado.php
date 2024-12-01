<?php
// Iniciar la sesión
session_start();

// Limpiar todos los datos de la sesión
session_unset();  // Elimina todas las variables de la sesión
session_destroy();  // Destruye la sesión

// Volver a iniciar la sesión para continuar utilizando la funcionalidad si es necesario
session_start();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel del Empleado</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            text-align: center;
            margin-top: 50px;
        }
        h1 {
            color: #333;
        }
        .menu {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        .menu button {
            padding: 15px 30px;
            font-size: 16px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .menu button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <h1>Panel del Empleado</h1>
    <div class="menu">
        <form action="reserva_pago_opcion1.php" method="GET">
            <button type="submit">Completar Datos de los Acompañantes</button>
        </form>
        <form action="pedido_restaurante_bar.php" method="GET">
            <button type="submit">Pedidos</button>
        </form>
        <form action="menu_reportes.php" method="GET">
            <button type="submit">Reportes</button>
        </form>
    </div>
</body>
</html>
