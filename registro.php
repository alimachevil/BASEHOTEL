<?php
// Conexión a la base de datos
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'hotel_db';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Verificar si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $dni = $_POST['dni'];
    $fecha_nacimiento = $_POST['fecha_nacimiento'];
    $telefono = $_POST['telefono'];
    $correo_electronico = $_POST['correo_electronico'];
    $direccion = $_POST['direccion'];
    $contrasena = $_POST['contrasena']; // Contraseña en texto plano

    // Validar si el DNI o el teléfono ya existen
    $check_sql = "SELECT * FROM clientes WHERE dni = ? OR telefono = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("ss", $dni, $telefono);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "El DNI o el teléfono ya están registrados.";
    } else {
        // Insertar nuevo cliente
        $sql = "INSERT INTO clientes (nombre, apellido, dni, fecha_nacimiento, telefono, correo_electronico, direccion, contrasena) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssss", $nombre, $apellido, $dni, $fecha_nacimiento, $telefono, $correo_electronico, $direccion, $contrasena);

        if ($stmt->execute()) {
            echo "Registro exitoso.";
        } else {
            echo "Error: " . $conn->error;
        }
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
    <title>Registro de Usuarios</title>
</head>
<body>
    <h1>Registro de Nuevos Usuarios</h1>
    <form action="registro.php" method="POST">
        <label for="nombre">Nombre:</label>
        <input type="text" name="nombre" id="nombre" required><br>

        <label for="apellido">Apellido:</label>
        <input type="text" name="apellido" id="apellido" required><br>

        <label for="dni">DNI:</label>
        <input type="text" name="dni" id="dni" maxlength="8" required><br>

        <label for="fecha_nacimiento">Fecha de Nacimiento:</label>
        <input type="date" name="fecha_nacimiento" id="fecha_nacimiento" required><br>

        <label for="telefono">Teléfono:</label>
        <input type="text" name="telefono" id="telefono" maxlength="15" required><br>

        <label for="correo_electronico">Correo Electrónico:</label>
        <input type="email" name="correo_electronico" id="correo_electronico" required><br>

        <label for="direccion">Dirección:</label>
        <input type="text" name="direccion" id="direccion" required><br>

        <label for="contrasena">Contraseña:</label>
        <input type="password" name="contrasena" id="contrasena" required><br>

        <input type="submit" value="Registrar">
    </form>

    <br>
    <a href="index.php"><button>Volver a la página principal</button></a>
</body>
</html>