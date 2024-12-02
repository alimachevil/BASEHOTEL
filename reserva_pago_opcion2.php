<?php
// Inicia la sesión
session_start();

// Verifica que el ID de reserva esté en sesión
if (!isset($_SESSION['id_reserva'])) {
    header('Location: index.php');
    exit();
}

// Recupera el ID de reserva desde la sesión
$id_reserva = $_SESSION['id_reserva'];

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

// Obtener las habitaciones relacionadas con la reserva
$sql = "
    SELECT c.id_cuarto, c.numero, c.capacidad_adultos, c.capacidad_niños
    FROM cuartos c
    INNER JOIN reservaporcuartos r ON c.id_cuarto = r.id_cuarto
    WHERE r.id_reserva = $id_reserva
";
$result = $conn->query($sql);

// Verificar si se encontraron habitaciones
if ($result->num_rows > 0) {
    // Almacenar las habitaciones en una variable
    $habitaciones = [];
    while ($row = $result->fetch_assoc()) {
        $habitaciones[] = $row;
    }
} else {
    echo "No se encontraron habitaciones para esta reserva.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['huéspedes'])) {
        $huespedes = $_POST['huéspedes'];

        // Iniciar transacción
        $conn->begin_transaction();

        try {
            // Iterar sobre las habitaciones
            foreach ($huespedes as $habitacionId => $huespedesHabitacion) {
                // Iterar sobre los huéspedes de cada habitación
                foreach ($huespedesHabitacion as $index => $datosHuesped) {
                    // Extraer datos del huésped
                    $nombre = $datosHuesped['nombre'] ?? null;
                    $apellido = $datosHuesped['apellido'] ?? null;
                    $tipo_documento = $datosHuesped['tipo_documento'] ?? null;
                    $nro_documento = $datosHuesped['nro_documento'] ?? null;
                    $celular = $datosHuesped['celular'] ?? null;
                    $pais = $datosHuesped['pais'] ?? null;
                    $correo = $datosHuesped['correo'] ?? null;

                    // Validar que todos los campos requeridos estén completos
                    if ($nombre && $apellido && $tipo_documento && $nro_documento && $pais) {
                        // Preparar la consulta
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

                        // Ejecutar la consulta
                        if (!$stmt->execute()) {
                            throw new Exception("Error al guardar acompañante: " . $stmt->error);
                        }
                    } else {
                        throw new Exception("Datos incompletos para el huésped en habitación $habitacionId.");
                    }
                }
            }

            // Confirmar transacción
            $conn->commit();

            // Redirigir a una página de confirmación
            header('Location: confirmacion_huespedes1.php');
            exit();
        } catch (Exception $e) {
            // Si hay un error, hacer rollback
            $conn->rollback();
            die("Error al guardar los acompañantes: " . $e->getMessage());
        }
    }
}


?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Huéspedes</title>
    <style>
        .huesped-form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 10px;
        }
        .huesped-form label {
            display: flex;
            flex-direction: column;
            font-size: 14px;
            margin: 0;
        }
        .huesped-form input, .huesped-form select {
            padding: 5px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 3px;
            width: 150px;
        }
        .huesped-form button {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
        }
    </style>
    <script>
    // Añadir nuevo formulario de huésped
    function añadirHuesped(habitacionId) {
        const container = document.getElementById(`habitacion-${habitacionId}`);
        const maxFormularios = parseInt(container.dataset.maxFormularios);
        const formulariosActuales = container.querySelectorAll('.huesped-form').length;

        if (formulariosActuales < maxFormularios) {
            const formularioBase = container.querySelector('.huesped-form:first-child');
            const nuevoFormulario = formularioBase.cloneNode(true);

            // Incrementar índice dinámico
            const nuevoIndice = formulariosActuales;
            nuevoFormulario.querySelectorAll('input, select').forEach((input) => {
                const name = input.name;
                if (name) {
                    input.name = name.replace(/\[\d+\]/, `[${nuevoIndice}]`);
                    input.value = ''; // Limpiar valor
                }
            });

            container.appendChild(nuevoFormulario);
        } else {
            alert('Se ha alcanzado el límite de huéspedes para esta habitación.');
        }
    }

    // Eliminar último formulario añadido
    function quitarUltimoHuesped(habitacionId) {
        const container = document.getElementById(`habitacion-${habitacionId}`);
        const formularios = container.querySelectorAll('.huesped-form');
        if (formularios.length > 1) {
            formularios[formularios.length - 1].remove();
        } else {
            alert('Debe haber al menos un formulario por habitación.');
        }
    }
</script>
</head>
<body>
    <h1>Registro de Huéspedes</h1>
    <form method="POST">
        <?php foreach ($habitaciones as $habitacion): 
            $maxHuespedes = $habitacion['capacidad_adultos'] + $habitacion['capacidad_niños'];
        ?>
            <h2>Habitación <?php echo htmlspecialchars($habitacion['numero']); ?> (Máximo <?php echo $maxHuespedes; ?> huéspedes)</h2>
            <div id="habitacion-<?php echo $habitacion['id_cuarto']; ?>" data-max-formularios="<?php echo $maxHuespedes; ?>">
                <div class="huesped-form">
                    <label>Nombre: <input type="text" name="huéspedes[<?php echo $habitacion['id_cuarto']; ?>][0][nombre]" required></label>
                    <label>Apellido: <input type="text" name="huéspedes[<?php echo $habitacion['id_cuarto']; ?>][0][apellido]" required></label>
                    <label>Tipo de Documento:
                        <select name="huéspedes[<?php echo $habitacion['id_cuarto']; ?>][0][tipo_documento]" required>
                            <option value="DNI">DNI</option>
                            <option value="Pasaporte">Pasaporte</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </label>
                    <label>Número de Documento: <input type="text" name="huéspedes[<?php echo $habitacion['id_cuarto']; ?>][0][nro_documento]" required></label>
                    <label>Celular: <input type="text" name="huéspedes[<?php echo $habitacion['id_cuarto']; ?>][0][celular]"></label>
                    <label>País: <input type="text" name="huéspedes[<?php echo $habitacion['id_cuarto']; ?>][0][pais]" required></label>
                    <label>Correo: <input type="email" name="huéspedes[<?php echo $habitacion['id_cuarto']; ?>][0][correo]"></label>
                </div>
            </div>
            <button type="button" onclick="añadirHuesped(<?php echo $habitacion['id_cuarto']; ?>)">Añadir otro huésped</button>
            <button type="button" onclick="quitarUltimoHuesped(<?php echo $habitacion['id_cuarto']; ?>)">Quitar último huésped</button>
        <?php endforeach; ?>
        <br><br>
        <button type="submit">Registrar Huéspedes</button>
    </form>
</body>
</html>
