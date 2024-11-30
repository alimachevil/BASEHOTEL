<?php
// Inicia la sesión
session_start();

// Función para formatear la fecha
function formatearFecha($fecha) {
    setlocale(LC_TIME, 'es_ES.UTF-8');
    return strftime("%B %d", strtotime($fecha));
}

// Verifica si las fechas están guardadas en la sesión
$check_in = isset($_SESSION['check_in']) ? $_SESSION['check_in'] : null;
$check_out = isset($_SESSION['check_out']) ? $_SESSION['check_out'] : null;

// Formatear fechas
$fecha_estadia = "";
if ($check_in && $check_out) {
    $fecha_estadia = "Desde " . formatearFecha($check_in) . " hasta " . formatearFecha($check_out);
}

// Manejo del formulario de habitaciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Guarda la cantidad de adultos y niños por habitación en la sesión
    $_SESSION['habitaciones'] = count($_POST['adultos']); // Número total de habitaciones
    $_SESSION['adultos'] = $_POST['adultos']; // Array con la cantidad de adultos por habitación
    $_SESSION['ninos'] = $_POST['ninos']; // Array con la cantidad de niños por habitación
    
    // Redirige a gestionh.php después de guardar la información en la sesión
    header('Location: gestionh.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserva de Hotel</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Lato', 'Roboto', sans-serif !important;
            background-color: #f5f5f5;
        }
        header {
            background-color: #000;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 0;
        }
        .header-container {
            width: 100%;
            max-width: 1140px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 15px;
            height: 69px;
        }
        .header-left img {
            height: 50px;
        }
        .header-right nav {
            display: flex;
            gap: 20px;
        }
        .header-right nav a {
            color: white;
            text-decoration: none;
            font-size: 16px;
            font-weight: bold;
        }
        .header-right nav a:hover {
            text-decoration: underline;
        }
        .progress-bar-container {
            padding: 11px 15px;
            max-width: 1140px;
            margin-left: auto;
            margin-right: auto;
        }
        .progress-bar-header {
            font-size: 16px;
            font-weight: bold;
            color: #000;
            margin-bottom: 10px;
        }
        .progress-bar {
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
            margin-top: 20px;
        }
        .step {
            text-align: center;
            flex: 1;
        }
        .step-circle {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            color: black;
            font-weight: bold;
        }
        .step-circle.active {
            background-color: #D69C4F;
        }
        .step-line {
            flex: 1;
            height: 2px;
            background-color: #ccc;
            margin: auto;
        }
        .step-line.active {
            background-color: #D69C4F;
        }
        .container {
            margin: 20px auto;
            max-width: 700px;
            padding: 0 40px 40px 40px;
        }
        .form-section {
            background-color: transparent;
            padding: 20px;
        }
        .form-label {
            font-weight: bold;
        }
        h2 {
            font-weight: bold;
            margin-bottom: 20px;
        }
        .btn-warning:hover {
            background-color: #c88942;
        }
        .form-select {
            border: none;
            border-bottom: 2px solid #000; /* Línea negra inferior */
            border-radius: 0; /* Sin bordes redondeados */
            background-color: transparent; /* Fondo transparente */
            box-shadow: none; /* Sin sombras */
            padding-left: 0; /* Ajusta el espacio interno */
            font-weight: bold;
            color: #000; /* Color del texto */
        }
        .form-select:focus {
            outline: none;
            border-bottom: 2px solid #D69C4F; /* Color dorado al enfocar */
        }
    </style>
</head>
<body>
    <!-- Encabezado -->
    <header>
        <div class="header-container">
            <div class="header-left">
                <img src="images/logo.png" alt="Logo">
            </div>
            <div class="header-right">
                <nav>
                    <a href="#">OFERTAS</a>
                    <a href="#">DESTINOS</a>
                    <a href="#">LIFE</a>
                    <a href="#">RESTAURANTES</a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Barra de Progreso -->
    <div class="progress-bar-container">
        <h1 class="progress-bar-header">Casa Andina Standard Talara</h1>
        <div class="progress-bar">
            <div class="step">
                <div class="step-circle active" id="circle-1">1</div>
                <div>Huéspedes & Habitaciones</div>
            </div>
            <div class="step-line" id="line-1"></div>
            <div class="step">
                <div class="step-circle" id="circle-2">2</div>
                <div>Selecciona Habitaciones & Tarifa</div>
            </div>
            <div class="step-line" id="line-2"></div>
            <div class="step" >
                <div class="step-circle" id="circle-3">3</div>
                <div>Fechas de Reserva Restaurante</div>
            </div>
            <div class="step-line" id="line-3"></div>
            <div class="step">
                <div class="step-circle" id="circle-4">4</div>
                <div>Monto Total</div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Sección 1 -->
        <div class="form-section" id="seccion-1">
            <h2 class="text-center">Huéspedes y habitaciones</h2>
            <form method="POST">
                <div id="habitaciones-container">
                    <!-- Primera habitación (por defecto) -->
                    <div class="row mb-3 habitacion-row" id="habitacion-1">
                        <div class="col-1 text-center align-self-center">
                            <span class="remove-habitacion d-none" style="cursor: pointer; color: #D69C4F; font-weight: bold;">✖</span>
                        </div>
                        <div class="col-11">
                            <div class="row align-items-center">
                                <div class="col-6 text-start">
                                    <label for="adultos_1" class="text-muted" style="font-weight: bold;">Habitación 1</label>
                                    <select class="form-select" id="adultos_1" name="adultos[]" required>
                                        <option value="1">1 Adulto</option>
                                        <option value="2">2 Adultos</option>
                                        <option value="3">3 Adultos</option>
                                        <option value="4">4 Adultos</option>
                                    </select>
                                </div>
                                <div class="col-6 text-start">
                                    <label for="ninos_1"></label>
                                    <select class="form-select" id="ninos_1" name="ninos[]" required>
                                        <option value="0">0 Niños</option>
                                        <option value="1">1 Niño</option>
                                        <option value="2">2 Niños</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mb-3 text-start">
                    <a href="#" class="text-decoration-none" style="color: #D69C4F; font-weight: bold;" id="add-habitacion">+ Añadir una habitación</a>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-warning" style="background-color: #D69C4F; color: white; font-weight: bold;">
                        ACTUALIZAR HUÉSPEDES Y HABITACIONES
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let habitacionCount = 1;

        document.getElementById('add-habitacion').addEventListener('click', function (e) {
            e.preventDefault();

            habitacionCount++;

            const container = document.getElementById('habitaciones-container');

            const habitacionHTML = `
                <div class="row mb-3 habitacion-row" id="habitacion-${habitacionCount}">
                    <div class="col-1 text-center align-self-center">
                        <span class="remove-habitacion" style="cursor: pointer; color: #D69C4F; font-weight: bold;">✖</span>
                    </div>
                    <div class="col-11">
                        <div class="row align-items-center">
                            <div class="col-6 text-start">
                                <label for="adultos-${habitacionCount}" class="text-muted" style="font-weight: bold;">Habitación ${habitacionCount}</label>
                                <select class="form-select" id="adultos-${habitacionCount}" name="adultos[]" required>
                                    <option value="1">1 Adulto</option>
                                    <option value="2">2 Adultos</option>
                                    <option value="3">3 Adultos</option>
                                    <option value="4">4 Adultos</option>
                                </select>
                            </div>
                            <div class="col-6 text-start">
                                <label for="ninos-${habitacionCount}"></label>
                                <select class="form-select" id="ninos-${habitacionCount}" name="ninos[]" required>
                                    <option value="0">0 Niños</option>
                                    <option value="1">1 Niño</option>
                                    <option value="2">2 Niños</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            container.insertAdjacentHTML('beforeend', habitacionHTML);

            document.querySelectorAll('.remove-habitacion').forEach(el => el.classList.remove('d-none'));
        });

        document.getElementById('habitaciones-container').addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-habitacion')) {
                const habitacionRow = e.target.closest('.habitacion-row');
                habitacionRow.remove();
                habitacionCount--;

                if (habitacionCount === 1) {
                    document.querySelector('.remove-habitacion').classList.add('d-none');
                }

                renumerarHabitaciones();
            }
        });

        function renumerarHabitaciones() {
            let index = 1;
            document.querySelectorAll('.habitacion-row').forEach(row => {
                row.id = `habitacion-${index}`;
                row.querySelector('.text-muted').textContent = `Habitación ${index}`;
                row.querySelector('.form-select').id = `adultos-${index}`;
                index++;
            });
        }
    </script>
</body>
</html>
