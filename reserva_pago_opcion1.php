<?php
// Inicia la sesión
session_start();

// Manejar reinicio de sesión
if (isset($_GET['reset']) && $_GET['reset'] === '1') {
    unset($_SESSION['id_reserva'], $_SESSION['habitaciones'], $_SESSION['habitacion_actual']);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

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

// Si no se ha ingresado un código de reserva, solicitarlo
if (!isset($_SESSION['id_reserva'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['codigo_reserva'])) {
        $codigo_reserva = trim($_POST['codigo_reserva']);

        // Verificar el código de reserva
        $stmt = $conn->prepare("SELECT id_reserva FROM reservas WHERE id_reserva = ?");
        $stmt->bind_param("i", $codigo_reserva);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $_SESSION['id_reserva'] = $codigo_reserva;

            // Cargar habitaciones asociadas a la reserva
            $sql = "
                SELECT c.id_cuarto, c.numero, c.capacidad_adultos, c.capacidad_niños
                FROM cuartos c
                INNER JOIN reservaporcuartos r ON c.id_cuarto = r.id_cuarto
                WHERE r.id_reserva = ?
            ";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $codigo_reserva);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $_SESSION['habitaciones'] = $result->fetch_all(MYSQLI_ASSOC);
                $_SESSION['habitacion_actual'] = 0;
            } else {
                $error = "No se encontraron habitaciones para esta reserva.";
            }

            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $error = "El código de reserva no es válido.";
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Ingresar Código de Reserva</title>
    </head>
    <body>
        <div class="container">
            <h1>Ingresar Código de Reserva</h1>
            <?php if (isset($error)): ?>
                <p class="error"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <form method="POST">
                <label for="codigo_reserva">Código de Reserva:</label>
                <input type="text" id="codigo_reserva" name="codigo_reserva" placeholder="Ingrese su código" required>
                <button type="submit">Buscar</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Proceso del formulario para registrar huéspedes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['huéspedes'])) {
    $id_reserva = $_SESSION['id_reserva'];
    $habitacion_actual = $_SESSION['habitaciones'][$_SESSION['habitacion_actual']];
    $id_cuarto = $habitacion_actual['id_cuarto'];
    $huespedes = $_POST['huéspedes'];

    // Iniciar transacción
    $conn->begin_transaction();

    try {
        foreach ($huespedes as $datosHuesped) {
            // Extraer datos del huésped
            $nombre = $datosHuesped['nombre'] ?? null;
            $apellido = $datosHuesped['apellido'] ?? null;
            $tipo_documento = $datosHuesped['tipo_documento'] ?? null;
            $nro_documento = $datosHuesped['nro_documento'] ?? null;
            $celular = $datosHuesped['celular'] ?? null;
            $pais = $datosHuesped['pais'] ?? null;
            $correo = $datosHuesped['correo'] ?? null;

            // Validar datos y guardar en la base de datos
            if ($nombre && $apellido && $tipo_documento && $nro_documento && $pais) {
                $stmt = $conn->prepare("
                    INSERT INTO acompañantes (nombre, apellido, tipo_documento, nro_documento, celular, pais, correo, id_reserva)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param(
                    "sssssssi",
                    $nombre,
                    $apellido,
                    $tipo_documento,
                    $nro_documento,
                    $celular,
                    $pais,
                    $correo,
                    $id_reserva
                );

                if (!$stmt->execute()) {
                    throw new Exception("Error al guardar acompañante: " . $stmt->error);
                }
            } else {
                throw new Exception("Datos incompletos para el huésped.");
            }
        }

        // Confirmar transacción
        $conn->commit();

        // Avanzar a la siguiente habitación
        $_SESSION['habitacion_actual']++;

        // Si se completaron todas las habitaciones, finalizar
        if ($_SESSION['habitacion_actual'] >= count($_SESSION['habitaciones'])) {
            unset($_SESSION['habitaciones'], $_SESSION['habitacion_actual']);
            header('Location: confirmacion_huespedes1.php');
            exit();
        }

        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        die("Error al guardar los acompañantes: " . $e->getMessage());
    }
}

// Cargar habitación actual
$habitacion_actual = $_SESSION['habitaciones'][$_SESSION['habitacion_actual']];
$maxHuespedes = $habitacion_actual['capacidad_adultos'] + $habitacion_actual['capacidad_niños'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Huéspedes</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f4f4f9;
        margin: 0;
        padding: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
    }

    .container {
        background-color: #ffffff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        max-width: 600px;
        width: 90%;
    }

    .title {
        font-size: 24px;
        margin-bottom: 10px;
        color: #333;
        text-align: center;
    }

    .subtitle {
        font-size: 18px;
        margin-bottom: 20px;
        color: #666;
        text-align: center;
    }

    .guest-form {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .huesped-form {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        background-color: #f9f9f9;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
    }

    .huesped-form label {
        flex: 1 1 45%;
        font-size: 14px;
        color: #555;
    }

    .huesped-form input, .huesped-form select {
        width: 100%;
        padding: 5px;
        margin-top: 5px;
        font-size: 14px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }

    .buttons {
        display: flex;
        justify-content: space-between;
        gap: 10px;
    }

    .btn {
        padding: 10px 15px;
        font-size: 14px;
        color: #fff;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .add-btn {
        background-color: #007bff;
    }

    .add-btn:hover {
        background-color: #0056b3;
    }

    .submit-btn {
        background-color: #28a745;
    }

    .submit-btn:hover {
        background-color: #218838;
    }
    
    .delete-btn {
        background-color: #dc3545;
        color: #fff;
        padding: 8px 12px;
        font-size: 14px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.3s ease, transform 0.2s ease;
    }

    .delete-btn:hover {
        background-color: #c82333;
        transform: scale(1.05);
    }

    .delete-btn:active {
        background-color: #a71d2a;
    }
</style>

    <script>
        // Función para agregar un formulario de huésped
        function agregarFormulario(idHabitacion, maxFormularios) {
            const contenedor = document.getElementById(`habitacion-${idHabitacion}`);
            const totalFormularios = contenedor.querySelectorAll('.huesped-form').length;

            if (totalFormularios < maxFormularios) {
                const nuevoFormulario = document.createElement('div');
                nuevoFormulario.classList.add('huesped-form');
                nuevoFormulario.innerHTML = `
                    <label>Nombre: <input type="text" name="huéspedes[${totalFormularios}][nombre]" required></label>
                    <label>Apellido: <input type="text" name="huéspedes[${totalFormularios}][apellido]" required></label>
                    <label>Tipo de Documento:
                        <select name="huéspedes[${totalFormularios}][tipo_documento]" required>
                            <option value="DNI">DNI</option>
                            <option value="Pasaporte">Pasaporte</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </label>
                    <label>Número de Documento: <input type="text" name="huéspedes[${totalFormularios}][nro_documento]" required></label>
                    <label>Celular: <input type="text" name="huéspedes[${totalFormularios}][celular]"></label>
                    <label>País: <input type="text" name="huéspedes[${totalFormularios}][pais]" required></label>
                    <label>Correo: <input type="email" name="huéspedes[${totalFormularios}][correo]"></label>
                    <button type="button" class="btn delete-btn" onclick="eliminarFormulario(this)">Eliminar</button>
                `;
                contenedor.appendChild(nuevoFormulario);
            } else {
                alert('No se pueden agregar más huéspedes para esta habitación.');
            }
        }

        // Función para eliminar un formulario de huésped
        function eliminarFormulario(boton) {
            boton.parentElement.remove();
        }
    </script>
</head>
<body>
    <div class="container">
        <h1 class="title">Registro de Huéspedes</h1>
        <h2 class="subtitle">Habitación <?php echo htmlspecialchars($habitacion_actual['numero']); ?> 
        (Máximo <?php echo $maxHuespedes; ?> huéspedes)</h2>
        <form class="guest-form" method="POST">
            <div id="habitacion-<?php echo $habitacion_actual['id_cuarto']; ?>" 
                data-max-formularios="<?php echo $maxHuespedes; ?>">
                <div class="huesped-form">
                    <label>Nombre:
                        <input type="text" name="huéspedes[0][nombre]" required>
                    </label>
                    <label>Apellido:
                        <input type="text" name="huéspedes[0][apellido]" required>
                    </label>
                    <label>Tipo de Documento:
                        <select name="huéspedes[0][tipo_documento]" required>
                            <option value="DNI">DNI</option>
                            <option value="Pasaporte">Pasaporte</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </label>
                    <label>Número de Documento:
                        <input type="text" name="huéspedes[0][nro_documento]" required>
                    </label>
                    <label>Celular:
                        <input type="text" name="huéspedes[0][celular]">
                    </label>
                    <label>País:
                        <input type="text" name="huéspedes[0][pais]" required>
                    </label>
                    <label>Correo:
                        <input type="email" name="huéspedes[0][correo]">
                    </label>
                </div>
            </div>
            <div class="buttons">
                <button type="button" class="btn add-btn" 
                    onclick="agregarFormulario(<?php echo $habitacion_actual['id_cuarto']; ?>, 
                    <?php echo $maxHuespedes; ?>)">
                    Agregar Huésped
                </button>
                <button type="submit" class="btn submit-btn">Registrar Huéspedes</button>
            </div>
        </form>
    </div>
</body>

</html>

