<?php
session_start();

// Verifica si hay una reserva activa
if (!isset($_SESSION['id_reserva'])) {
    header("Location: reserva.php");
    exit();
}

// Conexión a la base de datos
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'hotel_db';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtener el ID de la reserva y el total a pagar
$id_reserva = $_SESSION['id_reserva'];
$sql_reserva = "SELECT total_pago FROM reservas WHERE id_reserva = ?";
$stmt_reserva = $conn->prepare($sql_reserva);
$stmt_reserva->bind_param("i", $id_reserva);
$stmt_reserva->execute();
$result_reserva = $stmt_reserva->get_result();

if ($result_reserva->num_rows > 0) {
    $row_reserva = $result_reserva->fetch_assoc();
    $total_pago = $row_reserva['total_pago'];
} else {
    echo "No se encontró la reserva.";
    exit();
}

// Variable para mostrar el mensaje de confirmación
$pago_confirmado = false;

// Procesar el pago
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['metodo_pago'])) {
        $metodo_pago = $_POST['metodo_pago'];
        
        if ($metodo_pago === 'transferencia') {
            // Subida del comprobante
            if (isset($_FILES['comprobante']) && $_FILES['comprobante']['error'] == UPLOAD_ERR_OK) {
                $nombre_archivo = $_FILES['comprobante']['name'];
                $ruta_temporal = $_FILES['comprobante']['tmp_name'];
                $directorio_destino = 'comprobantes/'; // directorio a crear con permisos adecuados
                
                // Mover el archivo a la carpeta de comprobantes
                if (move_uploaded_file($ruta_temporal, $directorio_destino . basename($nombre_archivo))) {
                    // Aquí podrías realizar la lógica para verificar el pago
                    $pago_confirmado = true;
                    unset($_SESSION['id_reserva']); // Limpiar la reserva de la sesión
                } else {
                    echo "Error al subir el archivo.";
                }
            } else {
                echo "No se subió ningún archivo o ocurrió un error.";
            }
        } else {
            // Simulación de un pago exitoso para otros métodos
            $pago_exitoso = true; 
            if ($pago_exitoso) {
                $pago_confirmado = true;
                unset($_SESSION['id_reserva']);
            }
        }
    }
}

// Generar la factura HTML
function generarFactura($id_reserva, $total_pago) {
    $nombre_hotel = "Hotel Ejemplo"; // Nombre del hotel
    ob_start(); // Iniciar almacenamiento en buffer
    ?>
    <html>
    <head>
        <title>Factura</title>
        <style>
            body { font-family: Arial, sans-serif; }
            h1 { text-align: center; }
            .factura { width: 80%; margin: auto; }
            .detalle { margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class="factura">
            <h1>Factura</h1>
            <p><strong>Hotel:</strong> <?php echo $nombre_hotel; ?></p>
            <p><strong>ID de Reserva:</strong> <?php echo $id_reserva; ?></p>
            <p><strong>Total Pagado:</strong> S/. <?php echo number_format($total_pago, 2); ?></p>
            <div class="detalle">
                <h3>Detalles de la Reserva</h3>
                <p>Aquí irían más detalles sobre la reserva...</p>
            </div>
        </div>
    </body>
    </html>
    <?php
    return ob_get_clean(); // Devolver el contenido del buffer
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 20px;
            background-color: #f9f9f9;
        }
        h1 {
            color: #333;
        }
        .confirmacion {
            display: <?php echo $pago_confirmado ? 'block' : 'none'; ?>;
        }
        .pago-form {
            display: <?php echo $pago_confirmado ? 'none' : 'block'; ?>;
        }
        button {
            padding: 10px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <?php if ($pago_confirmado): ?>
        <h1>Pago Procesado Exitosamente</h1>
        <p>Gracias por su pago.</p>
        <p>Detalles de la Reserva:</p>
        <p>ID de Reserva: <?php echo $id_reserva; ?></p>
        <p>Total Pagado: S/. <?php echo number_format($total_pago, 2); ?></p>
        
        <form action="usuario.php" method="POST">
            <button type="submit">Regresar al Panel Principal</button>
        </form>
        <form action="reserva.php" method="POST">
            <button type="submit">Hacer Otra Reserva</button>
        </form>
        <button onclick="imprimirFactura()">Imprimir Factura</button>

        <script>
            function imprimirFactura() {
                const factura = `<?php echo addslashes(generarFactura($id_reserva, $total_pago)); ?>`;
                const ventana = window.open('', '_blank');
                ventana.document.write(factura);
                ventana.document.close();
                ventana.print();
            }
        </script>

    <?php else: ?>
        <h1>Proceder al Pago</h1>
        <p>Total a Pagar: S/. <?php echo number_format($total_pago, 2); ?></p>
        
        <form action="" method="POST" class="pago-form" enctype="multipart/form-data">
            <label for="metodo_pago">Seleccione un método de pago:</label>
            <select name="metodo_pago" id="metodo_pago" required onchange="mostrarFormulario(this.value)">
                <option value="" disabled selected>Seleccione...</option>
                <option value="tarjeta_credito">Tarjeta de Crédito</option>
                <option value="transferencia">Transferencia Bancaria</option>
            </select>

            <div id="form_tarjeta" style="display:none;">
                <h3>Datos de la Tarjeta</h3>
                <div class="form-group">
                    <label for="numero_tarjeta">Número de Tarjeta:</label>
                    <input type="text" name="numero_tarjeta" id="numero_tarjeta" required>
                </div>
                <div class="form-group">
                    <label for="nombre_titular">Nombre del Titular:</label>
                    <input type="text" name="nombre_titular" id="nombre_titular" required>
                </div>
                <div class="form-group">
                    <label for="fecha_expiracion">Fecha de Expiración (MM/AA):</label>
                    <input type="text" name="fecha_expiracion" id="fecha_expiracion" required>
                </div>
                <div class="form-group">
                    <label for="cvv">CVV:</label>
                    <input type="text" name="cvv" id="cvv" required>
                </div>
            </div>

            <div id="form_transferencia" style="display:none;">
                <h3>Instrucciones de Transferencia</h3>
                <p>Por favor, realice la transferencia a la cuenta proporcionada y confirme.</p>
                <p>Detalles de la cuenta: <strong>Banco XYZ, Cuenta 123456789</strong></p>
                <div class="form-group">
                    <label for="comprobante">Subir Comprobante de Pago:</label>
                    <input type="file" name="comprobante" id="comprobante" accept=".jpg,.jpeg,.png,.pdf" required>
                </div>
            </div>

            <button type="submit">Confirmar Pago</button>
        </form>

        <script>
            function mostrarFormulario(metodo) {
                document.getElementById('form_tarjeta').style.display = 'none';
                document.getElementById('form_transferencia').style.display = 'none';

                if (metodo === 'tarjeta_credito') {
                    document.getElementById('form_tarjeta').style.display = 'block';
                } else if (metodo === 'transferencia') {
                    document.getElementById('form_transferencia').style.display = 'block';
                }
            }
        </script>
    <?php endif; ?>
</body>
</html>
