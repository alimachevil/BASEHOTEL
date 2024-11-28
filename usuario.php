<?php
session_start();
if (!isset($_SESSION['id_cliente'])) {
    // Si no hay sesión iniciada, redirigir al login
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Usuario</title>
</head>
<body>
    <h2>Bienvenido al Panel de Usuario</h2>
    <p>Elige una opción:</p>
    <button onclick="window.location.href='consumo.php'">Consumo</button>
    <button onclick="window.location.href='reserva.php'">Nuevas Reservas</button>
    <button onclick="window.location.href='ver_reserva.php?id_cliente=<?php echo $_SESSION['id_cliente']; ?>'">Ver Reservas</button>
</body>
</html>
