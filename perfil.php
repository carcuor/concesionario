<?php
session_start();
require_once 'conexion.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}

// Obtener datos del usuario
$stmt = $conn->prepare("SELECT nombre, email, telefono, direccion, avatar, theme FROM Usuarios WHERE id_usuario = ?");
if ($stmt === false) {
    die('Error en la preparación de la consulta: ' . htmlspecialchars($conn->error));
}
$stmt->bind_param('i', $_SESSION['id_usuario']);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die('Error: Usuario no encontrado.');
}
$user = $result->fetch_assoc();
$_SESSION['theme'] = $user['theme'] ?? 'light'; // Actualizar tema en sesión
$stmt->close();

// Obtener historial de compras
$stmt = $conn->prepare("
    SELECT v.fecha_venta, v.precio_final, ve.id_vehiculo, m.nombre AS marca, mo.nombre AS modelo
    FROM Ventas v
    JOIN Vehiculos ve ON v.id_vehiculo = ve.id_vehiculo
    JOIN Marcas m ON ve.marca = m.id_marca
    JOIN Modelos mo ON ve.modelo = mo.id_modelo
    WHERE v.id_usuario = ?
");
if ($stmt === false) {
    die('Error en la preparación de la consulta de ventas: ' . htmlspecialchars($conn->error));
}
$stmt->bind_param('i', $_SESSION['id_usuario']);
$stmt->execute();
$purchases = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="es" data-theme="<?= htmlspecialchars($_SESSION['theme']) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil - CarsCuor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
    <style>
        body {
            padding-top: 80px;
            background: #f5f5f5;
            transition: background-color 0.3s ease;
            font-family: 'Arial', sans-serif;
        }
        .navbar {
            background: linear-gradient(45deg, #1a237e, #6a1b9a);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            padding: 1rem 0;
        }
        .navbar-brand {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.8rem;
            color: #fff;
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
            transition: transform 0.3s ease;
            display: flex;
            align-items: center;
        }
        .navbar-brand img {
            margin-right: 10px;
        }
        .navbar-brand:hover {
            transform: scale(1.05);
            color: #e1bee7;
        }
        .nav-link {
            color: #fff !important;
            font-size: 1.1rem;
            padding: 0.5rem 1rem;
            position: relative;
            transition: all 0.3s ease;
        }
        .nav-link:hover {
            color: #e1bee7 !important;
            transform: translateY(-2px);
        }
        .nav-link.active {
            font-weight: bold;
            color: #fff !important;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
        }
        .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 50%;
            transform: translateX(-50%);
            width: 50%;
            height: 3px;
            background: #e91e63;
            border-radius: 2px;
        }
        .dropdown-menu {
            background: #fff;
            border: none;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            border-radius: 10px;
            animation: fadeIn 0.3s ease;
            margin-top: 0.5rem;
        }
        .dropdown-item {
            color: #333;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
        }
        .dropdown-item:hover {
            background: #f3e5f5;
            color: #6a1b9a;
        }
        .dropdown-item.active {
            background: #e1bee7;
            color: #fff;
            font-weight: bold;
        }
        .navbar-toggler {
            border: none;
            padding: 0.5rem;
        }
        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(255, 255, 255, 0.8)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @media (max-width: 992px) {
            .navbar-nav {
                background: #1a237e;
                padding: 1rem;
                border-radius: 10px;
            }
            .nav-link {
                margin: 0.5rem 0;
            }
        }
        .profile-container {
            background: linear-gradient(135deg, #ffffff, #f3e5f5);
            border: 2px solid #e1bee7;
            border-radius: 16px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
            padding: 2rem;
            margin-top: 2rem;
            transition: transform 0.3s ease;
        }
        .profile-container:hover {
            transform: translateY(-5px);
        }
        .profile-container h2 {
            font-family: 'Bebas Neue', sans-serif;
            color: #1a237e;
            text-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        .profile-container img {
            border: 3px solid #6a1b9a;
            border-radius: 50%;
            max-width: 150px;
            height: auto;
            transition: transform 0.3s ease;
        }
        .profile-container img:hover {
            transform: scale(1.1);
        }
        .purchases-container {
            background: linear-gradient(135deg, #ffffff, #f3e5f5);
            border: 2px solid #e1bee7;
            border-radius: 16px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
            padding: 2rem;
            margin-top: 2rem;
            transition: transform 0.3s ease;
        }
        .purchases-container:hover {
            transform: translateY(-5px);
        }
        .purchases-container h3 {
            font-family: 'Bebas Neue', sans-serif;
            color: #1a237e;
        }
        .table th {
            background: linear-gradient(45deg, #1a237e, #6a1b9a);
            color: #fff;
            text-align: center;
        }
        .table tbody tr:hover {
            background: #f3e5f5;
            cursor: pointer;
        }
        .btn-primary {
            background: linear-gradient(45deg, #1a237e, #6a1b9a);
            border: none;
            border-radius: 50px;
            padding: 0.5rem 1.5rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .btn-primary:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(106, 27, 154, 0.5);
            background: linear-gradient(45deg, #6a1b9a, #1a237e);
        }
        #darkModeToggle {
            background: linear-gradient(45deg, #1a237e, #6a1b9a);
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        #darkModeToggle:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 15px rgba(106, 27, 154, 0.5);
        }
        /* Tema oscuro */
        [data-theme="dark"] body {
            background: #1c2526;
            color: #fff;
        }
        [data-theme="dark"] .navbar {
            background: linear-gradient(45deg, #2c3e50, #34495e);
        }
        [data-theme="dark"] .profile-container, [data-theme="dark"] .purchases-container {
            background: #2c3e50;
            border: 2px solid #e1bee7;
        }
        [data-theme="dark"] .table th {
            background: linear-gradient(45deg, #34495e, #2c3e50);
        }
        [data-theme="dark"] .table tbody tr:hover {
            background: #34495e;
        }
        /* Tema neón rosa */
        [data-theme="neon_pink"] .navbar {
            background: linear-gradient(45deg, #e91e63, #ff4081);
        }
        [data-theme="neon_pink"] .btn-primary {
            background: linear-gradient(45deg, #e91e63, #ff4081);
        }
        [data-theme="neon_pink"] .btn-primary:hover {
            background: linear-gradient(45deg, #ff4081, #e91e63);
        }
        [data-theme="neon_pink"] .profile-container, [data-theme="neon_pink"] .purchases-container {
            border: 2px solid #ff4081;
        }
        /* Tema neón azul */
        [data-theme="neon_blue"] .navbar {
            background: linear-gradient(45deg, #0288d1, #03a9f4);
        }
        [data-theme="neon_blue"] .btn-primary {
            background: linear-gradient(45deg, #0288d1, #03a9f4);
        }
        [data-theme="neon_blue"] .btn-primary:hover {
            background: linear-gradient(45deg, #03a9f4, #0288d1);
        }
        [data-theme="neon_blue"] .profile-container, [data-theme="neon_blue"] .purchases-container {
            border: 2px solid #03a9f4;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <img src="images/logo.png" alt="Logo Vehículos" style="height: 40px;">
                CarsCuor
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>" href="index.php">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'coches.php' ? 'active' : '' ?>" href="coches.php">Vehículos</a>
                    </li>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'administrador'): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle <?= in_array(basename($_SERVER['PHP_SELF']), ['gestion_marcas.php', 'gestion_modelos.php', 'listar_todo.php', 'ventas.php']) ? 'active' : '' ?>" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Configuración
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                <li><a class="dropdown-item" href="gestion_marcas.php">Gestión de Marcas</a></li>
                                <li><a class="dropdown-item" href="gestion_modelos.php">Gestión de Modelos</a></li>
                                <li><a class="dropdown-item" href="listar_todo.php">Listar Marcas y Modelos</a></li>
                                <li><a class="dropdown-item" href="ventas.php">Ventas</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['id_usuario'])): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'perfil.php' ? 'active' : '' ?>" href="perfil.php">Perfil</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Cerrar sesión</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'login.php' ? 'active' : '' ?>" href="login.php">Iniciar Sesión</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'registro.php' ? 'active' : '' ?>" href="registro.php">Registrarse</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <button id="darkModeToggle" class="btn">
                            <i class="bi <?= $_SESSION['theme'] === 'dark' ? 'bi-sun-fill' : 'bi-moon-fill' ?>"></i>
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Contenido del perfil -->
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="profile-container">
                    <h2 class="text-center mb-4">Mi Perfil</h2>
                    <div class="text-center mb-4">
                        <img src="<?= htmlspecialchars($user['avatar'] ?? 'images/default_avatar.jpg') ?>" alt="Avatar" class="img-fluid">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <strong>Nombre:</strong> <?= htmlspecialchars($user['nombre'] ?? 'No especificado') ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Email:</strong> <?= htmlspecialchars($user['email'] ?? 'No especificado') ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Teléfono:</strong> <?= htmlspecialchars($user['telefono'] ?? 'No especificado') ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Dirección:</strong> <?= htmlspecialchars($user['direccion'] ?? 'No especificado') ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Tema:</strong> <?= htmlspecialchars($user['theme'] ?? 'light') ?>
                        </div>
                    </div>
                    <div class="text-center mt-4">
                        <a href="editar_usuario.php" class="btn btn-primary">Editar Perfil</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Historial de compras -->
        <div class="row justify-content-center mt-4">
            <div class="col-md-10">
                <div class="purchases-container">
                    <h3 class="text-center mb-4">Historial de Compras</h3>
                    <?php if (empty($purchases)): ?>
                        <p class="text-center">No tienes compras registradas.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Marca</th>
                                        <th>Modelo</th>
                                        <th>Precio Final</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($purchases as $purchase): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($purchase['fecha_venta']) ?></td>
                                            <td><?= htmlspecialchars($purchase['marca']) ?></td>
                                            <td><?= htmlspecialchars($purchase['modelo']) ?></td>
                                            <td><?= number_format($purchase['precio_final'], 2) ?> €</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
    <script src="modoOscuro.js"></script>
</body>
</html>