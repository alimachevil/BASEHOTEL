<?php
// Inicia la sesión
session_start();

// Verifica si las reservas están confirmadas en la sesión
if (!isset($_SESSION['cuartos_seleccionados']) || empty($_SESSION['cuartos_seleccionados'])) {
    header('Location: index.php'); // Redirigir al inicio si no hay información
    exit();
}

// Opcional: Limpiar la sesión para finalizar el flujo
unset($_SESSION['check_in'], $_SESSION['check_out'], $_SESSION['habitaciones'], $_SESSION['adultos'], $_SESSION['ninos'], $_SESSION['cuartos_seleccionados']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Reservas</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin: 50px;
        }
        h1 {
            color: #4CAF50;
        }
        p {
            font-size: 1.2em;
        }
        .btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            font-size: 1em;
            color: #fff;
            background-color: #007BFF;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <h1>¡Reservas Confirmadas!</h1>
    <p>Gracias por realizar su reserva con nosotros. Hemos confirmado los detalles de su estadía.</p>
    <p>Si tiene alguna duda o necesita asistencia adicional, no dude en contactarnos.</p>
    <a href="menu_empleado.php" class="btn">Volver al Inicio</a>
</body>
</html>
