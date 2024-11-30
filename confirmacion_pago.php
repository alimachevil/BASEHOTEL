<?php
// Inicia la sesión
session_start();

// Verifica que el ID de reserva esté en sesión
if (!isset($_SESSION['id_reserva'])) {
    header('Location: index.php');
    exit();
}

// Variables de sesión
$id_reserva = $_SESSION['id_reserva'];
$habitaciones = $_SESSION['habitaciones']; // Si las habitaciones están en la sesión
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Reserva</title>
</head>
<body>
    <h1>¡Reserva Confirmada!</h1>
    <p>Su reserva ha sido registrada exitosamente.</p>
    <p><strong>ID de Reserva:</strong> <?php echo htmlspecialchars($id_reserva); ?></p>
    
    <h2>Opciones:</h2>
    <button onclick="location.href='reserva_pago_opcion2.php';">Completar datos Reserva</button>

    <button onclick="location.href='index.php';">Omitir</button>
</body>
</html>
