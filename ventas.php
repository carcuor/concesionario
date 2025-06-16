<?php
session_start();
require_once 'conexion.php';

// Verificar si el usuario es administrador
if (!isset($_SESSION['id_usuario']) || $_SESSION['role'] !== 'administrador') {
    header('Location: login.php');
    exit;
}

// Obtener tema del usuario
$stmt = $conn->prepare("SELECT theme FROM Usuarios WHERE id_usuario = ?");
$stmt->bind_param('i', $_SESSION['id_usuario']);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $_SESSION['theme'] = $row['theme'] ?? 'light';
}
$stmt->close();

// Obtener todas las ventas
$stmt = $conn->prepare("
    SELECT v.id_venta, u.nombre AS cliente, m.nombre AS marca, mo.nombre AS modelo, v.precio_final, v.fecha_venta
    FROM Ventas v
    JOIN Usuarios u ON v.id_usuario = u.id_usuario
    JOIN Vehiculos ve ON v.id_vehiculo = ve.id_vehiculo
    JOIN Marcas m ON ve.marca = m.id_marca
    JOIN Modelos mo ON ve.modelo = mo.id_modelo
    ORDER BY v.fecha_venta DESC
");
if ($stmt === false) {
    die('Error en la preparación de la consulta: ' . htmlspecialchars($conn->error));
}
$stmt->execute();
$ventas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Datos para gráficas
// Ventas por mes (último año)
$ventas_por_mes = array_fill(0, 12, 0);
$ingresos_por_mes = array_fill(0, 12, 0);
$meses = [];
$current_year = date('Y');
for ($i = 11; $i >= 0; $i--) {
    $meses[] = date('M Y', strtotime("-$i months"));
}

$stmt = $conn->prepare("
    SELECT MONTH(fecha_venta) AS mes, YEAR(fecha_venta) AS año, COUNT(*) AS cantidad, SUM(precio_final) AS ingresos
    FROM Ventas
    WHERE fecha_venta >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY YEAR(fecha_venta), MONTH(fecha_venta)
    ORDER BY año, mes
");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    if ($row['año'] == $current_year || $row['año'] == $current_year - 1) {
        $index = (12 - (date('n') - $row['mes'] + ($current_year - $row['año']) * 12)) % 12;
        $ventas_por_mes[$index] = $row['cantidad'];
        $ingresos_por_mes[$index] = $row['ingresos'];
    }
}
$stmt->close();

// Ventas por marca
$ventas_por_marca = [];
$stmt = $conn->prepare("
    SELECT m.nombre AS marca, COUNT(*) AS cantidad
    FROM Ventas v
    JOIN Vehiculos ve ON v.id_vehiculo = ve.id_vehiculo
    JOIN Marcas m ON ve.marca = m.id_marca
    GROUP BY m.nombre
");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $ventas_por_marca[$row['marca']] = $row['cantidad'];
}
$stmt->close();

// Generar token CSRF (por consistencia)
$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;
?>

<!DOCTYPE html>
<html lang="es" data-theme="<?= htmlspecialchars($_SESSION['theme']) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ventas - CarsCuor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
        .sales-container {
            background: linear-gradient(135deg, #ffffff, #f3e5f5);
            border: 2px solid #e1bee7;
            border-radius: 16px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
            padding: 2rem;
            margin-top: 2rem;
            transition: transform 0.3s ease;
        }
        .sales-container:hover {
            transform: translateY(-5px);
        }
        .sales-container h2 {
            font-family: 'Bebas Neue', sans-serif;
            color: #1a237e;
            text-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }
        .table {
            background: #fff;
            border: 1px solid #e1bee7;
        }
        .table th {
            background: linear-gradient(45deg, #1a237e, #6a1b9a);
            color: #fff;
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.2rem;
        }
        .table td {
            vertical-align: middle;
        }
        .chart-container {
            background: #fff;
            border: 2px solid #e1bee7;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 2rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
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
        [data-theme="dark"] .sales-container {
            background: #2c3e50;
            border: 2px solid #e1bee7;
        }
        [data-theme="dark"] .table {
            background: #34495e;
            color: #fff;
        }
        [data-theme="dark"] .chart-container {
            background: #34495e;
            border: 2px solid #e1bee7;
        }
        /* Tema neón rosa */
        [data-theme="neon_pink"] .navbar {
            background: linear-gradient(45deg, #e91e63, #ff4081);
        }
        [data-theme="neon_pink"] .sales-container {
            border: 2px solid #ff4081;
        }
        [data-theme="neon_pink"] .table th {
            background: linear-gradient(45deg, #e91e63, #ff4081);
        }
        [data-theme="neon_pink"] .chart-container {
            border: 2px solid #ff4081;
        }
        /* Tema neón azul */
        [data-theme="neon_blue"] .navbar {
            background: linear-gradient(45deg, #0288d1, #03a9f4);
        }
        [data-theme="neon_blue"] .sales-container {
            border: 2px solid #03a9f4;
        }
        [data-theme="neon_blue"] .table th {
            background: linear-gradient(45deg, #0288d1, #03a9f4);
        }
        [data-theme="neon_blue"] .chart-container {
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
                            <a class="nav-link dropdown-toggle <?= in_array(basename($_SERVER['PHP_SELF']), ['gestion_marcas.php', 'gestion_modelos.php', 'listar_todo.php', 'ventas.php', 'editar_vehiculo.php']) ? 'active' : '' ?>" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
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

    <!-- Contenido de ventas -->
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="sales-container">
                    <h2 class="text-center mb-4">Panel de Ventas</h2>
                    <!-- Gráficas -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="chart-container">
                                <canvas id="ventasPorMes"></canvas>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="chart-container">
                                <canvas id="ventasPorMarca"></canvas>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="chart-container">
                                <canvas id="ingresosPorMes"></canvas>
                            </div>
                        </div>
                    </div>
                    <!-- Tabla de ventas -->
                    <h3 class="mb-3">Listado de Ventas</h3>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID Venta</th>
                                    <th>Cliente</th>
                                    <th>Marca</th>
                                    <th>Modelo</th>
                                    <th>Precio (€)</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($ventas)): ?>
                                    <tr><td colspan="6" class="text-center">No hay ventas registradas.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($ventas as $venta): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($venta['id_venta']) ?></td>
                                            <td><?= htmlspecialchars($venta['cliente']) ?></td>
                                            <td><?= htmlspecialchars($venta['marca']) ?></td>
                                            <td><?= htmlspecialchars($venta['modelo']) ?></td>
                                            <td><?= number_format($venta['precio_final'], 2, ',', '.') ?></td>
                                            <td><?= date('d/m/Y H:i', strtotime($venta['fecha_venta'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
    <script src="modoOscuro.js"></script>
    <script>
        // Gráfico de ventas por mes (barras)
        const ctxVentasMes = document.getElementById('ventasPorMes').getContext('2d');
        new Chart(ctxVentasMes, {
            type: 'bar',
            data: {
                labels: <?= json_encode($meses) ?>,
                datasets: [{
                    label: 'Ventas por Mes',
                    data: <?= json_encode($ventas_por_mes) ?>,
                    backgroundColor: 'rgba(106, 27, 154, 0.6)',
                    borderColor: '#6a1b9a',
                    borderWidth: 2
                }]
            },
            options: {
                plugins: { legend: { labels: { color: '#1a237e' } } },
                scales: {
                    x: { ticks: { color: '#1a237e' } },
                    y: { ticks: { color: '#1a237e' }, beginAtZero: true }
                }
            }
        });

        // Gráfico de ventas por marca (tarta)
        const ctxVentasMarca = document.getElementById('ventasPorMarca').getContext('2d');
        new Chart(ctxVentasMarca, {
            type: 'pie',
            data: {
                labels: <?= json_encode(array_keys($ventas_por_marca)) ?>,
                datasets: [{
                    label: 'Ventas por Marca',
                    data: <?= json_encode(array_values($ventas_por_marca)) ?>,
                    backgroundColor: ['#e91e63', '#6a1b9a', '#1a237e', '#0288d1', '#ff4081'],
                    borderColor: '#fff',
                    borderWidth: 1
                }]
            },
            options: {
                plugins: { legend: { labels: { color: '#1a237e' } } }
            }
        });

        // Gráfico de ingresos por mes (líneas)
        const ctxIngresosMes = document.getElementById('ingresosPorMes').getContext('2d');
        new Chart(ctxIngresosMes, {
            type: 'line',
            data: {
                labels: <?= json_encode($meses) ?>,
                datasets: [{
                    label: 'Ingresos por Mes (€)',
                    data: <?= json_encode($ingresos_por_mes) ?>,
                    backgroundColor: 'rgba(233, 30, 99, 0.2)',
                    borderColor: '#e91e63',
                    borderWidth: 2,
                    fill: true
                }]
            },
            options: {
                plugins: { legend: { labels: { color: '#1a237e' } } },
                scales: {
                    x: { ticks: { color: '#1a237e' } },
                    y: { ticks: { color: '#1a237e' }, beginAtZero: true }
                }
            }
        });
    </script>
</body>
</html>