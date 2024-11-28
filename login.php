<?php
session_start();

// Configuración de la conexión a la base de datos
$host = 'localhost'; // Cambia si es necesario
$dbname = 'hotel_db'; // Asegúrate de que sea el nombre correcto de la base de datos
$username = 'root'; // El usuario de la base de datos
$password_db = ''; // La contraseña de la base de datos

// Crear la conexión
$conn = new mysqli($host, $username, $password_db, $dbname);

// Verificar la conexión
if ($conn->connect_error) {
    die("Error en la conexión: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $dni = $_POST['dni'];
    $password = $_POST['password'];

    // Consulta SQL para verificar si el DNI y la contraseña coinciden
    $query = "SELECT * FROM clientes WHERE dni = ? AND contrasena = ?";
    $stmt = $conn->prepare($query);
    
    if ($stmt === false) {
        die("Error en la consulta: " . $conn->error);
    }

    // Enlazar parámetros y ejecutar la consulta
    $stmt->bind_param("ss", $dni, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        // Recuperar el id_cliente
        $row = $result->fetch_assoc(); // Obtener el resultado como un array asociativo
        $id_cliente = $row['id_cliente']; // Asegúrate de que el campo 'id_cliente' exista en tu tabla

        // Inicio de sesión exitoso, redirigir al panel de usuarios
        $_SESSION['id_cliente'] = $id_cliente; // Guardar el id_cliente en la sesión
        header("Location: usuario.php");
        exit();
    } else {
        // Error en el inicio de sesión
        $error = "DNI o contraseña incorrectos";
    }
    
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de Sesión</title>
</head>
<body>
    <h2>Inicio de Sesión</h2>
    <form method="POST" action="login.php">
        <label for="dni">DNI:</label>
        <input type="text" id="dni" name="dni" required><br>
        <label for="password">Contraseña:</label>
        <input type="password" id="password" name="password" required><br>
        <button type="submit">Iniciar Sesión</button>
    </form>

    <?php if (isset($error)) { echo "<p style='color:red;'>$error</p>"; } ?>
</body>
</html>
