<?php
session_start();

// Verificar si se desea cerrar sesión
if (isset($_POST['cerrar_sesion'])) {
    session_unset(); // Borra todas las variables de sesión
    session_destroy(); // Destruye la sesión
    header("Location: index.php"); // Redirige a la página principal
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Hoteles</title>
</head>
<body>
    <h1>Bienvenido al Sistema de Hotel</h1>
    <a href="registro.php"><button>Registro</button></a>
    <a href="login.php"><button>Inicio de Sesión</button></a>

    <!-- Botón para cerrar sesión -->
    <form action="index.php" method="POST" style="display:inline;">
        <button type="submit" name="cerrar_sesion">Cerrar Sesión</button>
    </form>
</body>
</html>
