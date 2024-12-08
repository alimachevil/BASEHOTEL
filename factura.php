<?php
    session_start(); // Asegúrate de que la sesión esté iniciada

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

    // Recuperamos las variables de sesión
    $location = isset($_SESSION['location']) ? $_SESSION['location'] : '';
    $check_in = isset($_SESSION['check_in']) ? $_SESSION['check_in'] : '';
    $check_out = isset($_SESSION['check_out']) ? $_SESSION['check_out'] : '';
    $cuartos_seleccionados = isset($_SESSION['cuartos_seleccionados']) ? $_SESSION['cuartos_seleccionados'] : [];
    $reserva_mesa = isset($_SESSION['reserva_mesa']) ? $_SESSION['reserva_mesa'] : [];
    $total_costo_mesas = isset($_SESSION['total_costo_mesas']) ? $_SESSION['total_costo_mesas'] : 0;
    $id_reserva = isset($_SESSION['id_reserva']) ? $_SESSION['id_reserva'] : '';

    date_default_timezone_set('America/Lima');

    function calcularDiasEstadia($check_in, $check_out) {
        $fecha1 = new DateTime($check_in);
        $fecha2 = new DateTime($check_out);
        return $fecha2->diff($fecha1)->days;
    }

    $dias_estadia = calcularDiasEstadia($check_in, $check_out);

    $query_cliente = "SELECT cl.nombre, cl.apellido 
                     FROM reservas r
                     JOIN clientes cl ON r.id_cliente = cl.id_cliente
                     WHERE r.id_reserva = ?";
    if ($stmt_cliente = $conn->prepare($query_cliente)) {
        $stmt_cliente->bind_param("i", $id_reserva);
        $stmt_cliente->execute();
        $stmt_cliente->bind_result($nombre_huesped, $apellido_huesped);
        $stmt_cliente->fetch();
        $stmt_cliente->close();
    }

    $query_hotel = "SELECT h.nombre, h.direccion, h.ciudad, r.total_pago
                    FROM reservas r
                    JOIN hoteles h ON r.id_hotel = h.id_hotel
                    WHERE r.id_reserva = ?";
    if ($stmt_hotel = $conn->prepare($query_hotel)) {
        $stmt_hotel->bind_param("i", $id_reserva);
        $stmt_hotel->execute();
        $stmt_hotel->bind_result($nombre_hotel, $direccion_hotel, $ciudad_hotel, $total_pago);
        $stmt_hotel->fetch();
        $stmt_hotel->close();
    }
    $sub_total = 0;
    $IGV = 0;

    function convertirNumeroEnLetras($numero) {
        $numero = number_format($numero, 2, ".", ""); // Formatea el número a 2 decimales
    
        // Mapeo de números
        $numeros = array(
            0 => 'CERO', 1 => 'UNO', 2 => 'DOS', 3 => 'TRES', 4 => 'CUATRO', 5 => 'CINCO',
            6 => 'SEIS', 7 => 'SIETE', 8 => 'OCHO', 9 => 'NUEVE', 10 => 'DIEZ',
            11 => 'ONCE', 12 => 'DOCE', 13 => 'TRECE', 14 => 'CATORCE', 15 => 'QUINCE',
            16 => 'DIECISEIS', 17 => 'DIECISIETE', 18 => 'DIECIOCHO', 19 => 'DIECINUEVE',
            20 => 'VEINTE', 21 => 'VEINTIUNO', 22 => 'VEINTIDOS', 23 => 'VEINTITRES',
            24 => 'VEINTICUATRO', 25 => 'VEINTICINCO', 26 => 'VEINTISEIS', 27 => 'VEINTISIETE',
            28 => 'VEINTIOCHO', 29 => 'VEINTINUEVE', 30 => 'TREINTA', 31 => 'TREINTA Y UNO',
            32 => 'TREINTA Y DOS', 33 => 'TREINTA Y TRES', 34 => 'TREINTA Y CUATRO', 35 => 'TREINTA Y CINCO',
            36 => 'TREINTA Y SEIS', 37 => 'TREINTA Y SIETE', 38 => 'TREINTA Y OCHO', 39 => 'TREINTA Y NUEVE',
            40 => 'CUARENTA', 41 => 'CUARENTA Y UNO', 42 => 'CUARENTA Y DOS', 43 => 'CUARENTA Y TRES', 
            44 => 'CUARENTA Y CUATRO', 45 => 'CUARENTA Y CINCO', 46 => 'CUARENTA Y SEIS', 47 => 'CUARENTA Y SIETE',
            48 => 'CUARENTA Y OCHO', 49 => 'CUARENTA Y NUEVE', 50 => 'CINCUENTA', 51 => 'CINCUENTA Y UNO',
            52 => 'CINCUENTA Y DOS', 53 => 'CINCUENTA Y TRES', 54 => 'CINCUENTA Y CUATRO', 55 => 'CINCUENTA Y CINCO',
            56 => 'CINCUENTA Y SEIS', 57 => 'CINCUENTA Y SIETE', 58 => 'CINCUENTA Y OCHO', 59 => 'CINCUENTA Y NUEVE',
            60 => 'SESENTA', 61 => 'SESENTA Y UNO', 62 => 'SESENTA Y DOS', 63 => 'SESENTA Y TRES',
            64 => 'SESENTA Y CUATRO', 65 => 'SESENTA Y CINCO', 66 => 'SESENTA Y SEIS', 67 => 'SESENTA Y SIETE',
            68 => 'SESENTA Y OCHO', 69 => 'SESENTA Y NUEVE', 70 => 'SETENTA', 71 => 'SETENTA Y UNO',
            72 => 'SETENTA Y DOS', 73 => 'SETENTA Y TRES', 74 => 'SETENTA Y CUATRO', 75 => 'SETENTA Y CINCO',
            76 => 'SETENTA Y SEIS', 77 => 'SETENTA Y SIETE', 78 => 'SETENTA Y OCHO', 79 => 'SETENTA Y NUEVE',
            80 => 'OCHENTA', 81 => 'OCHENTA Y UNO', 82 => 'OCHENTA Y DOS', 83 => 'OCHENTA Y TRES',
            84 => 'OCHENTA Y CUATRO', 85 => 'OCHENTA Y CINCO', 86 => 'OCHENTA Y SEIS', 87 => 'OCHENTA Y SIETE',
            88 => 'OCHENTA Y OCHO', 89 => 'OCHENTA Y NUEVE', 90 => 'NOVENTA', 91 => 'NOVENTA Y UNO',
            92 => 'NOVENTA Y DOS', 93 => 'NOVENTA Y TRES', 94 => 'NOVENTA Y CUATRO', 95 => 'NOVENTA Y CINCO',
            96 => 'NOVENTA Y SEIS', 97 => 'NOVENTA Y SIETE', 98 => 'NOVENTA Y OCHO', 99 => 'NOVENTA Y NUEVE',
            100 => 'CIENTO', 200 => 'Doscientos', 300 => 'Trescientos', 400 => 'Cuatrocientos',
            500 => 'Quinientos', 600 => 'Seiscientos', 700 => 'Setecientos', 800 => 'Ochocientos', 900 => 'Novecientos',
            1000 => 'MIL', 1000000 => 'UN MILLON'
        );
    
        // Parte entera y decimal
        $parte_entera = floor($numero); // Parte entera
        $parte_decimal = round(($numero - $parte_entera) * 100); // Parte decimal (céntimos)
    
        $resultado = "";
    
        // Manejo de miles
        if ($parte_entera >= 1000) {
            $miles = floor($parte_entera / 1000);
            if ($miles > 1) {
                $resultado .= $numeros[$miles] . " MIL ";
            } else {
                $resultado .= "MIL ";
            }
            $parte_entera = $parte_entera % 1000; // Resto después de extraer los miles
        }
    
        // Manejo de centenas
        if ($parte_entera >= 100) {
            $centenas = floor($parte_entera / 100) * 100;
            $resultado .= $numeros[$centenas] . " ";
            $parte_entera -= $centenas;
        }
    
        // Manejo de decenas y unidades
        if ($parte_entera > 0) {
            if (isset($numeros[$parte_entera])) {
                $resultado .= $numeros[$parte_entera] . " ";
            } else {
                $decena = floor($parte_entera / 10) * 10;
                $unidad = $parte_entera % 10;
                $resultado .= $numeros[$decena] . " Y " . $numeros[$unidad] . " ";
            }
        }
    
        // Agregar decimales (céntimos)
        if ($parte_decimal > 0) {
            $resultado .= "CON " . str_pad($parte_decimal, 2, "0", STR_PAD_LEFT) . "/100";
        } else {
            $resultado .= "CON " . "00/100";
        }
    
        return strtoupper(trim($resultado)) . " SOLES";
    }    
      
    
    
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura</title>
    <style>
        /* Estilos generales para la página */
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        .invoice {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ccc; /* Borde del contenedor principal */
        }

        .info-container {
            margin-top: 21.4px;
            display: flex;
            justify-content: space-between;
            width: 100%;
        }

        .empresa-info {
            text-align: left;
            font-size: 1.5em;
        }

        .empresa-info div {
            margin-bottom: 5px;
        }

        .factura-info {
            text-align: center;
            width: 300px;
            padding: 10px 25px;
            border: 2px solid black;
        }

        .factura-info h1 {
            font-size: 1.5em;
            margin: 0px;
            font-weight: normal;
        }

        .factura-info p {
            font-size: 1.5em;
            margin: 5px 0;
        }

        .invoice-details {
            margin-top: 20px;
        }

        /* Estilo para los datos de la factura */
        .invoice-data {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .invoice-data div {
            width: 48%;
        }

        .invoice-data p{
            margin: 5px;
        }

        .invoice-data div.left {
            text-align: left;
            width: 20%; /* Deja menos espacio para el contenido de la izquierda */
        }

        .invoice-data div.right {
            text-align: left;
            width: 80%; /* Deja más espacio para el contenido de la derecha */
        }

       /* Estilo para la tabla de productos */
        .invoice-table {
            width: 100%;
            border-collapse: collapse; /* Elimina los bordes */
            margin-bottom: 20px;
            border-bottom: 10px solid #0046ad;
        }

        .invoice-table th, .invoice-table td {
            padding: 8px;
            text-align: center; /* Alinea el texto por defecto al centro */
            border: none; /* Elimina los bordes */
        }

        .invoice-table th {
            background-color: #0046ad;
            color: white;
        }

        /* Alineación para los datos */
        .invoice-table td {
            text-align: left; /* Alinea por defecto los td a la izquierda */
        }

        /* Específico para las celdas debajo de CANT., U.M., CÓDIGO, DESCRIPCIÓN */
        .invoice-table th:nth-child(1),
        .invoice-table th:nth-child(2) {
            text-align: left; /* Alinea "CANT." y "U.M." a la izquierda */
        }

        .invoice-table td:nth-child(2),
        .invoice-table td:nth-child(3),
        .invoice-table td:nth-child(4) {
            text-align: left; /* Alinea "U.M.", "CÓDIGO", "DESCRIPCIÓN" a la izquierda */
            max-width: 200px; /* Ajusta este valor según lo que necesites */
        }

        /* Específico para las celdas debajo de VALOR U., DCTO., TOTAL */
        .invoice-table td:nth-child(1),
        .invoice-table td:nth-child(5),
        .invoice-table td:nth-child(6),
        .invoice-table td:nth-child(7) {
            text-align: right; /* Alinea "VALOR U.", "DCTO.", "TOTAL" a la derecha */
        }

        .total-label{
            font-size: 16px;
        }

        /* Estilo para la sección de Totales */
        .total-section {
            margin-top: 20px;
            font-size: 1.2em;
            display: flex;
            align-items: flex-end;
            flex-direction: row;
        }

        /* Estilo para el monto total */
        .total-amount {
            margin-bottom: 10px;
            text-align: right;
            font-size: 1.5em;
            font-weight: bold;
        }

        /* Estilo para la categoría de totales (OP. GRAVADAS, etc.) */
        .total-categories {
            display: flex;
            width: 35%; /* Limita el ancho de la categoría total */
            margin-bottom: 70px;
            justify-content: flex-end;
        }

        /* Estilo para las categorías dentro de total-categories */
        .category {
            display: flex;
            flex-direction: column;
            align-items: flex-start; /* Alinea el contenido a la izquierda */
        }

        .category p{
            font-size: 16px;
            margin: 1px;
        }

        /* Alineación para la segunda columna (centro) */
        .category:nth-child(2) {
            text-align: center;
        }

        /* Alineación para la tercera columna (izquierda) */
        .category:nth-child(3) {
            text-align: left;
        }

        /* Estilo para los textos dentro de cada categoría */
        .category span {
            margin: 5px 0;
        }

        /* Estilo para los importes */
        .category-amounts {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        /* Espaciado entre los importes */
        .category-amounts span {
            margin: 5px 0;
        }

        /* Estilo para el importe total (resaltado en azul) */
        .total-amount-final {
            background-color: #0046ad; /* Color azul */
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
        }

        /* Estilo para el valor de los totales (con fondo azul) */
        .total-value {
            background-color: #0046ad; /* Color azul */
            color: white;
            padding: 5px 15px;
            border-radius: 5px;
        }


        /* Estilo para los textos dentro de la tercera columna (IMPORTE TOTAL) */
        .category:last-child span:last-child {
            background-color: #0046ad;
            color: white;
            font-weight: bold;
        }

        /* Ajustes de alineación y espaciado para los valores */
        .currency-labels {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        .currency-labels span {
            margin: 5px 0;
        }

        .qr-section {
            display: flex;
            align-items: left;
            margin-top: 20px;
            font-size: 16px;
            width: 76%;
        }

        .qr-section img {
            width: 80px;
            height: 80px;
            margin-right: 10px;
        }

        .qr-section div {
            font-size: 16px;
            text-align: left;
        }

        .print-section{
            display: flex;
            justify-content: center;
        }

        .print-button {
            display: block;
            margin: 30px 10px 30px 10px;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            font-size: 1em;
            cursor: pointer;
            font-weight: bold;
            
        }

        .print-button:hover {
            background-color: #0056b3;
        }

        @media print {
            body {
                margin: 0;
                padding: 20px;
                font-size: 12px;
            }

            .invoice-table th {
                background-color: #0046ad;
                color: white;
            }

            .invoice {
                border: none;
                padding: 10px;
                width: 100%;
                max-width: 100%;
                box-shadow: none;
            }

            .factura-info {
                border: 2px solid black;
                padding: auto;
                width: auto;
                max-width: auto;
                box-shadow: none;
            }

            .print-button {
                display: none;
            }

            /* Eliminar encabezados y pies de página de la impresión */
            @page {
                margin-top: 0;
                margin-bottom: 0;
            }
            body {
                margin-top: 0;
                margin-bottom: 0;
            }
            /* Desactivar los encabezados predeterminados en la impresión */
            html, body {
                -webkit-print-color-adjust: exact !important; /* Forzar la impresión de colores */
            }
        }
    </style>
</head>
<body>

    <div class="invoice">
        <div class="info-container">
            <div class="empresa-info">
                <div><strong>VISTA ANDINA HOTELES</strong></div>
                <div style="font-size: 0.9em;">NESSUS HOTELES PERU S.A.</div>
                <div style="font-size: 0.7em;"><?=$direccion_hotel ?></div>
                <div style="font-size: 0.6em;"><?=$ciudad_hotel ?> - <?=$ciudad_hotel ?> - <?=$nombre_hotel ?></div>
            </div>
            <div class="factura-info">
                <h1>FACTURA ELECTRÓNICA</h1>
                <br>
                <p>RUC: 20505670443</p>
                <p>F001-000000<?= trim($id_reserva) ?></p>
            </div>
        </div>

        <div class="invoice-details">
            <div class="invoice-data">
                <div class="left">
                    <p>F. VENCIMIENTO</p>
                    <p>F. EMISIÓN</p>
                    <p>C.C</p>
                    <p>N° DE DOCUMENTO</p>
                    <p>SEÑOR(ES)</p>
                    <p>DIR. DEL CLIENTE</p>
                    <p>TIPO DE MONEDA</p>
                </div>
                <div class="right">
                    <p><?= date('d-m-Y', strtotime($check_in)) ?></p>
                    <p><?= date('d-m-Y', strtotime($check_in)) ?> <?= date('H:i:s') ?></p>
                    <p>-</p>
                    <p>20505670443</p>
                    <p>NESSUS HOTELES PERU S.A.</p>
                    <p><?=$direccion_hotel ?> - <?=$ciudad_hotel ?></p>
                    <p>PEN</p>
                </div>
            </div>

            <table class="invoice-table">
                <thead>
                    <tr>
                        <th>CANT.</th>
                        <th>U.M.</th>
                        <th>CÓDIGO</th>
                        <th>DESCRIPCIÓN</th>
                        <th>VALOR U.</th>
                        <th>DCTO.</th>
                        <th>TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cuartos_seleccionados as $cuarto) {
                        if ($cuarto['tipo_pago'] != 'hotel') {
                            $id_cuarto = $cuarto['id_cuarto'];
                            $query = "SELECT c.precio_base, t.nombre AS tipo_cuarto 
                                    FROM cuartos c
                                    JOIN tipo_cuarto t ON c.id_tipo = t.id_tipo
                                    WHERE c.id_cuarto = ?";
                            if ($stmt = $conn->prepare($query)) {
                                $stmt->bind_param("i", $id_cuarto);
                                $stmt->execute();
                                $stmt->bind_result($precio_base, $tipo_cuarto);
                                $stmt->fetch();
                                $stmt->close();
                            }
                            $descuento = (1 - ($cuarto['precio']/$precio_base)) * 100;
                    ?>
                    <tr>
                        <td><?= $dias_estadia ?></td>
                        <td>UNIDAD</td>
                        <td>H - <?= $cuarto['id_cuarto'] ?></td>
                        <td>HOSPEDAJE HAB. <?= $tipo_cuarto ?> - HUESPED: <?= $nombre_huesped . ' ' . $apellido_huesped ?> - DEL: <?= date('d-m-Y', strtotime($check_in)) ?> HASTA: <?= date('d-m-Y', strtotime($check_out)) ?></td>
                        <td><?= number_format($precio_base, 2) ?></td>
                        <td><?= number_format($descuento, 2) ?></td>
                        <td><?= number_format($cuarto['precio']*$dias_estadia, 2) ?></td>
                        <?php $sub_total +=  $cuarto['precio']*$dias_estadia?>
                    </tr>
                    <?php } }?>

                    <?php foreach ($reserva_mesa as $mesa) {
                    ?>
                    <tr>
                        <td>1</td>
                        <td>UNIDAD</td>
                        <td>M - <?= $mesa['id_mesa'] ?></td>
                        <td>RESERVA MESA: <?= $mesa['tipo_reserva'] ?> - HUESPED: <?= $nombre_huesped . ' ' . $apellido_huesped ?> - FECHA: <?= date('d-m-Y', strtotime($mesa['fecha_reserva'])) ?></td>
                        <td><?= number_format($mesa['precio_reservam'], 2) ?></td>
                        <td>-</td>
                        <td><?= number_format($mesa['precio_reservam'], 2) ?></td>
                        <?php $sub_total +=  $mesa['precio_reservam']?>
                    </tr>
                    <?php } ?>
                    <?php $IGV = $sub_total * 0.18;?>
                    <?php $total_pago = $sub_total + $IGV;?>
                </tbody>
            </table>

            <div class="total-label" style="text-align: left;">
                
                10<?= trim($id_reserva) ?> - <?= convertirNumeroEnLetras($total_pago) ?>
            </div>

            <div class="total-section">
                <div class="qr-section" style="display: flex; align-items: flex-end; margin-top: 20px; font-size: 16px; height: 150px;">
                    <img src="images/qr.png" alt="Código QR" style="width: 150px; height: 150px; margin-right: 10px;">
                    <div style="text-align: left;">
                        <p style="margin: 0px 0px 7px 0px;">Hash: vqBcmx5YvZsoYogeT+T3sYQIO5U=</p>
                        <p style="margin: 0;">Representación impresa del Comprobante Electrónico generado desde el sistema Facturador SUNAT.</p>
                        <p style="margin: 0;">Puede verificarlo en el portal de SUNAT</p>
                    </div>
                </div>
                <div class="total-categories">
                    <div class="category" style="width: 51%; text-align: left;">
                        <p>OP. GRAVADAS</p>
                        <p>OP. EXONERADAS</p>
                        <p>OP. INAFECTAS</p>
                        <p>IGV</p>
                        <p style="font-weight: bold; padding-left: 5px; padding-top: 4px; background-color: #0046ad; color: white; width: 100%">IMPORTE TOTAL</p>
                    </div>
                    <div class="category" style="width: 9%; text-align: left;">
                        <p>S/</p>
                        <p>S/</p>
                        <p>S/</p>
                        <p>S/</p>
                        <p style="font-weight: bold; padding-top: 4px; background-color: #0046ad; color: white; width: 100%">S/</p>
                    </div>
                    <div class="category" style="width: 30%; text-align: right;">
                        <p style="width: 100%"><?= number_format($sub_total, 2) ?></p>
                        <p style="width: 100%">0.00</p>
                        <p style="width: 100%">0.00</p>
                        <p style="width: 100%"><?= number_format($IGV, 2) ?></p>
                        <p style="font-weight: bold; padding-right: 5px; padding-top: 4px;background-color: #0046ad; color: white; width: 100%" ><?= number_format($total_pago, 2) ?></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="print-section">
            <button class="print-button" onclick="autoPrint()">IMPRIMIR FACTURA</button>
            <button class="print-button" onclick="location.href='index.php';">REGRESAR AL INICIO</button>
        </div>
    </div>

    <script>
        function autoPrint() {
            window.print();
        }
    </script>
</body>
</html>