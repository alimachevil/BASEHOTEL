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
    $_SESSION['location'] = $_POST['location'];
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
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">


    <style>
    body {
        font-family: 'Lato', 'Roboto', sans-serif !important;
        background-color: #f5f5f5;
    }
    .navbar {
        background-color: #2A2A2A;
    }
    .navbar .navbar-nav .nav-link {
        color: white;
        font-weight: 500;
    }
    .navbar .navbar-nav .nav-link:hover {
        color: #FF6F00;
    }
    .carousel-item img {
        height: 600px;
        object-fit: cover;
    }
    .reservation-form {
        margin-top: -80px;
        background-color: #f9f9f9;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        z-index: 10;
        position: relative;
    }
    .services .card {
        border: none;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    footer {
        background-color: #2A2A2A;
        color: white;
        padding: 20px;
    }
</style>

</head>
<body>
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
        .navbar {
            max-width: 1140px;
            width: 100%;
            height: 69px;
        }
        .navbar-brand {
            height: 50px;
            display: flex;
            align-items: center;
        }
        .navbar-nav {
            margin-left: auto;
            display: flex;
            gap: 20px;
        }
        .nav-link {
            color: white;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
        }
        .nav-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>


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
        .navbar {
            max-width: 1140px;
            width: 100%;
            height: 69px;
        }
        .navbar-brand {
            height: 50px;
            display: flex;
            align-items: center;
        }
        .navbar-nav {
            margin-left: auto;
            display: flex;
            gap: 20px;
        }
        .nav-link {
            color: white;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
        }
        .nav-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>


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
        .navbar {
            max-width: 1140px;
            width: 100%;
            height: 69px;
        }
        .navbar-brand img {
            height: 50px; /* Ajusta la altura del logo */
        }
        .navbar-nav {
            margin-left: auto;
            display: flex;
            gap: 20px;
        }
        .nav-link {
            color: white;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
        }
        .nav-link:hover {
            text-decoration: underline;
        }
        .btn-warning {
            background-color: #D69C4F;
            color: white;
            border: none;
            padding: 8px 16px 8px 16px;
            margin: 0.8px;
            cursor: pointer;
        }
        .btn-warning:hover {
            background-color: #c88942;
        }
    </style>
</head>
<body>
    <!-- Encabezado -->
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container-fluid">
                <a class="navbar-brand" href="#">
                    <img src="images/logo.png" alt="Logo Casa Andina"> <!-- Cambia el 'path/to/logo.png' por la ruta de tu logo -->
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="#">Ofertas</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Destinos y Hoteles</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Life</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Restaurantes</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Corporativo</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
</body>
</html>



    <!-- Carousel -->
    <div id="hotelCarousel" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner">
            <div class="carousel-item active">
                <a href="#reserva-estadia"><img src="https://s3.us-east-1.amazonaws.com/ca-webprod/Ambientes/fe7469e0-9920-4f67-9cb7-58b28bb05cb4.jpg" class="d-block w-100" alt="Habitación 1"></a>
            </div>
            <div class="carousel-item">
                <a href="#reserva-estadia"><img src="https://s3.us-east-1.amazonaws.com/ca-webprod/Ambientes/85ab2ef3-f0b0-492c-a08d-e5ae2aed57c4.jpg" class="d-block w-100" alt="Habitación 2"></a>
            </div>
            <div class="carousel-item">
                <a href="#reserva-estadia"><img src="https://s3.us-east-1.amazonaws.com/ca-webprod/Ambientes/0be34178-1fec-4c47-aa2d-cecc13e8f77b.jpg" class="d-block w-100" alt="Piscina del Hotel"></a>
            </div>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#hotelCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Anterior</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#hotelCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Siguiente</span>
        </button>
    </div>

    <!-- Formulario de Reserva -->
    <div id="reserva-estadia" class="container reservation-form">
        <h2 class="text-center mb-4">¡Reserva tu estadía!</h2>
        <form action="index.php" method="POST">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="check_in" class="form-label">Fecha Check-in</label>
                    <input type="date" class="form-control" id="check_in" name="check_in" required>
                </div>
                <div class="col-md-6">
                    <label for="check_out" class="form-label">Fecha Check-out</label>
                    <input type="date" class="form-control" id="check_out" name="check_out" required>
                </div>
                <div class="col-md-12">
                    <label for="location" class="form-label">Elige la sede</label>
                    <select class="form-select" id="location" name="location">
                        <option value="" disabled selected>Selecciona una opción</option>
                        <option value="2">Lima</option>
                        <option value="4">Cusco</option>
                        <option value="1">Arequipa</option>
                        <option value="8">Tacna</option>
                    </select>
                </div>
            </div>
            <div class="text-center mt-4">
                <button type="submit" class="btn btn-warning btn-lg">Buscar Disponibilidad</button>
            </div>
        </form>
    </div>

    <!-- Servicios Destacados -->
    <section class="py-5 services">
        <div class="container">
            <h2 class="text-center mb-4">Nuestros Servicios</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card">
                        <img src="https://s3.us-east-1.amazonaws.com/ca-webprod/Ambientes/a1140a78-c8b7-4fa0-969e-7d6514bbe12e.jpg" class="card-img-top" alt="Spa">
                        <div class="card-body">
                            <h5 class="card-title">Spa y Relax</h5>
                            <p class="card-text">Disfruta de tratamientos relajantes en nuestro spa exclusivo.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <img src="https://s3.us-east-1.amazonaws.com/ca-webprod/Ambientes/8cf07adc-5ef0-44ad-be27-a30ec750ce3e.jpg" class="card-img-top" alt="Restaurante">
                        <div class="card-body">
                            <h5 class="card-title">Restaurante Gourmet</h5>
                            <p class="card-text">Saborea los mejores platillos preparados por chefs de clase mundial.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <img src="https://s3.us-east-1.amazonaws.com/ca-webprod/Ambientes/1331c77b-eb30-4b4f-8114-2c694fbde841.jpg" class="card-img-top" alt="Salón de eventos">
                        <div class="card-body">
                            <h5 class="card-title">Bartender</h5>
                            <p class="card-text">Toma las mejores bebidas de la ciudad con nuestro servicio de bar.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

<!-- Sección: Más Servicios -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-4">Más Servicios</h2>
        <div class="row text-center g-4">
            <div class="col-md-2">
                <div class="p-3">
                    <i class="bi bi-universal-access fs-1 text-warning"></i>
                    <p class="mt-2">Acceso Adaptado</p>
                </div>
            </div>
            <div class="col-md-2">
                <div class="p-3">
                    <i class="bi bi-wind fs-1 text-warning"></i>
                    <p class="mt-2">Aire Acondicionado</p>
                </div>
            </div>
            <div class="col-md-2">
                <div class="p-3">
                    <i class="bi bi-cup-hot fs-1 text-warning"></i>
                    <p class="mt-2">Bar/Lounge</p>
                </div>
            </div>
            <div class="col-md-2">
                <div class="p-3">
                    <i class="bi bi-building fs-1 text-warning"></i>
                    <p class="mt-2">Ascensores</p>
                </div>
            </div>
            <div class="col-md-2">
                <div class="p-3">
                    <i class="bi bi-translate fs-1 text-warning"></i>
                    <p class="mt-2">Personal Multilingüe</p>
                </div>
            </div>
            <div class="col-md-2">
                <div class="p-3">
                    <i class="bi bi-shield-check fs-1 text-warning"></i>
                    <p class="mt-2">Seguridad 24h</p>
                </div>
            </div>
        </div>
    </div>
</section>



<!-- Galería de Fotos -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-4">Galería de Fotos</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <img src="https://s3.us-east-1.amazonaws.com/ca-webprod/Ambientes/3505ce84-0f3f-4423-b2a3-62277082ef2f.jpg" class="img-fluid rounded" alt="Restaurante">
            </div>
            <div class="col-md-4">
                <img src="	https://s3.us-east-1.amazonaws.com/ca-webprod/Ambientes/f129b552-6877-43b2-a010-b1b1bb87be78.jpg" class="img-fluid rounded" alt="Bar">
            </div>
            <div class="col-md-4">
                <img src="https://s3.us-east-1.amazonaws.com/ca-webprod/Ambientes/d0ee5b05-5b44-4b3c-9ab2-32f9b4d6b4f7.jpg" class="img-fluid rounded" alt="Piscina">
            </div>
        </div>
    </div>
</section>

<!-- Ofertas -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-4">Ofertas Especiales</h2>
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body text-center">
                        <h5 class="card-title">Black Days</h5>
                        <p class="card-text">35% de descuento</p>
                        <p><strong>S/ 371</strong> <del>S/ 572</del></p>
                        <a href="#reserva-estadia" class="btn btn-warning">Reservar</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body text-center">
                        <h5 class="card-title">Desayuno Buffet</h5>
                        <p class="card-text">2x S/ 84</p>
                        <p>Lunes a domingo de 7:00 a.m. a 10:00 a.m.</p>
                        <a href="#reserva-estadia" class="btn btn-warning">Reservar</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonios Mejorados -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-4">Lo que dicen nuestros huéspedes</h2>
        <div class="row g-4">
            <!-- Testimonio 1 -->
            <div class="col-md-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <p class="card-text text-muted">
                            <i class="bi bi-quote fs-2 text-warning"></i> 
                            "Linda estadía en este lindo hotel. ¡Increíble servicio y excelente ubicación!"
                        </p>
                        <div class="d-flex align-items-center mt-3">
                            <img src="https://s3.us-east-1.amazonaws.com/ca-webprod/pais/CL.svg" alt="User" class="rounded-circle me-3" style="width: 50px; height: 50px;">
                            <div>
                                <h6 class="mb-0">Stephanie G.</h6>
                                <small class="text-muted">19 nov, 2024</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Testimonio 2 -->
            <div class="col-md-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <p class="card-text text-muted">
                            <i class="bi bi-quote fs-2 text-warning"></i> 
                            "El trato fue delicioso, la comida muy buena y la atención excelente."
                        </p>
                        <div class="d-flex align-items-center mt-3">
                            <img src="	https://s3.us-east-1.amazonaws.com/ca-webprod/pais/PE.svg" alt="User" class="rounded-circle me-3" style="width: 50px; height: 50px;">
                            <div>
                                <h6 class="mb-0">Lorena G.</h6>
                                <small class="text-muted">11 nov, 2024</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Testimonio 3 -->
            <div class="col-md-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <p class="card-text text-muted">
                            <i class="bi bi-quote fs-2 text-warning"></i> 
                            "La comida estuvo muy rica, y la atención del personal fue excepcional."
                        </p>
                        <div class="d-flex align-items-center mt-3">
                            <img src="https://s3.us-east-1.amazonaws.com/ca-webprod/pais/NL.svg" alt="User" class="rounded-circle me-3" style="width: 50px; height: 50px;">
                            <div>
                                <h6 class="mb-0">Junior G.</h6>
                                <small class="text-muted">3 nov, 2024</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

    <!-- Footer -->
    <footer class="text-center">
        <div class="container">
            <p>&copy; 2024 Vista Andina. Todos los derechos reservados por mi.</p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
