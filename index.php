<?php
// Inicia la sesión
session_start();

// Limpiar toda la información de sesión previa
session_unset();
session_destroy();
session_start();

// Datos de conexión a la base de datos
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'hotel_db';

// Conexión a la base de datos MySQL
$conn = new mysqli($host, $user, $pass, $dbname);

// Verificar la conexión
if ($conn->connect_error) {
    die("Error en la conexión a la base de datos: " . $conn->connect_error);
}

// Verifica si el formulario ha sido enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Guarda las fechas de Check-in y Check-out en la sesión
    $_SESSION['check_in'] = $_POST['check_in'];
    $_SESSION['check_out'] = $_POST['check_out'];

    // Redirigir a la página hyh.php
    header("Location: hyh.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserva de Hotel</title>
</head>
<body>
    <h1>Reserva de Hotel</h1>
    
    <form action="index.php" method="POST">
        <label for="check_in">Fecha Check-in:</label>
        <input type="date" id="check_in" name="check_in" required>
        <br><br>
        
        <label for="check_out">Fecha Check-out:</label>
        <input type="date" id="check_out" name="check_out" required>
        <br><br>
        
        <button type="submit">Buscar</button>
    </form>

    <?php
    // Cerrar la conexión a la base de datos
    $conn->close();
    ?>
</body>
</html>
