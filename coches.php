<?php
session_start();
require_once 'conexion.php';

// Obtener tema del usuario
if (isset($_SESSION['id_usuario'])) {
    $stmt = $conn->prepare("SELECT theme FROM Usuarios WHERE id_usuario = ?");
    $stmt->bind_param('i', $_SESSION['id_usuario']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $_SESSION['theme'] = $row['theme'] ?? 'light';
    }
    $stmt->close();
} else {
    $_SESSION['theme'] = $_SESSION['theme'] ?? 'light';
}

// Fetch brands and models for filter dropdowns
$marcas = $conn->query("SELECT id_marca, nombre FROM Marcas ORDER BY nombre");
$modelos = $conn->query("SELECT m.id_modelo, m.id_marca, m.nombre FROM Modelos m ORDER BY m.nombre");
$modelos_array = [];
while ($row = $modelos->fetch_assoc()) {
    $modelos_array[] = [
        'id_modelo' => $row['id_modelo'],
        'id_marca' => $row['id_marca'],
        'nombre' => $row['nombre']
    ];
}

// Build vehicle query with filters
$query = "SELECT v.id_vehiculo, m.nombre AS marca, mo.nombre AS modelo, v.año, v.precio, v.kilometraje, v.tipo_combustible, v.transmision,
                 CASE WHEN ve.id_venta IS NOT NULL THEN 'vendido' ELSE v.estado END AS estado,
                 (SELECT iv.ruta_servidor FROM Imagenes_Vehiculos iv WHERE iv.id_vehiculo = v.id_vehiculo LIMIT 1) AS imagen
          FROM Vehiculos v
          JOIN Marcas m ON v.marca = m.id_marca
          JOIN Modelos mo ON v.modelo = mo.id_modelo
          LEFT JOIN Ventas ve ON v.id_vehiculo = ve.id_vehiculo";
$params = [];
$types = "";
$where_clauses = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['marca'])) {
        $where_clauses[] = "v.marca = ?";
        $params[] = $_POST['marca'];
        $types .= "i";
    }
    if (!empty($_POST['modelo'])) {
        $where_clauses[] = "v.modelo = ?";
        $params[] = $_POST['modelo'];
        $types .= "i";
    }
    if (!empty($_POST['precio_min']) && !empty($_POST['precio_max'])) {
        $where_clauses[] = "v.precio BETWEEN ? AND ?";
        $params[] = $_POST['precio_min'];
        $params[] = $_POST['precio_max'];
        $types .= "dd";
    }
    if (!empty($_POST['estado']) && in_array($_POST['estado'], ['disponible', 'reservado', 'vendido'])) {
        $where_clauses[] = "(CASE WHEN ve.id_venta IS NOT NULL THEN 'vendido' ELSE v.estado END) = ?";
        $params[] = $_POST['estado'];
        $types .= "s";
    }
}

if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$vehiculos = $result->num_rows > 0 ? $result->fetch_all(MYSQLI_ASSOC) : [];
$no_results = $result->num_rows === 0 && $_SERVER['REQUEST_METHOD'] === 'POST';
$stmt->close();

// Group vehicles by brand
$vehiculos_por_marca = [];
foreach ($vehiculos as $vehiculo) {
    $vehiculos_por_marca[$vehiculo['marca']][] = $vehiculo;
}
ksort($vehiculos_por_marca);

// Sort vehicles within each brand by estado if no state filter is applied
if (empty($_POST['estado'])) {
    foreach ($vehiculos_por_marca as &$vehiculos) {
        usort($vehiculos, function($a, $b) {
            $order = ['disponible' => 1, 'reservado' => 2, 'vendido' => 3];
            return $order[$a['estado']] <=> $order[$b['estado']];
        });
    }
    unset($vehiculos);
}
?>

<!DOCTYPE html>
<html lang="es" data-theme="<?= htmlspecialchars($_SESSION['theme']) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehículos - CarsCuor</title>
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
            height: 40px;
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
        .card {
            background: linear-gradient(135deg, #f3e5f5, #ffffff);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
            border-radius: 16px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        .card-img-top {
            border-top-left-radius: 16px;
            border-top-right-radius: 16px;
            height: 200px;
            object-fit: cover;
        }
        .card-body {
            padding: 1.5rem;
        }
        .card-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.5rem;
            color: #1a237e;
        }
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #e1bee7;
            transition: border-color 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #6a1b9a;
            box-shadow: 0 0 8px rgba(106, 27, 154, 0.3);
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
        .btn-secondary {
            background: linear-gradient(45deg, #5e35b1, #7e57c2);
            border: none;
            border-radius: 50px;
            padding: 0.5rem 1.5rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .btn-secondary:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(94, 53, 177, 0.5);
            background: linear-gradient(45deg, #7e57c2, #5e35b1);
        }
        .btn-danger {
            background: linear-gradient(45deg, #d32f2f, #f44336);
            border: none;
            border-radius: 50px;
            padding: 0.5rem 1rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .btn-danger:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(211, 47, 47, 0.5);
            background: linear-gradient(45deg, #f44336, #d32f2f);
        }
        .btn-success {
            background: linear-gradient(45deg, #2e7d32, #4caf50);
            border: none;
            border-radius: 50px;
            padding: 0.5rem 1rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .btn-success:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.5);
            background: linear-gradient(45deg, #4caf50, #2e7d32);
        }
        .brand-header {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 2rem;
            color: #1a237e;
            margin-top: 2rem;
            margin-bottom: 1rem;
            border-bottom: 2px solid #e1bee7;
            padding-bottom: 0.5rem;
        }
        .badge {
            font-size: 0.9rem;
            padding: 0.4em 0.8em;
            border-radius: 8px;
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
        [data-theme="dark"] .card {
            background: #2c3e50;
            border: 2px solid #e1bee7;
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
        [data-theme="neon_pink"] .card {
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
        [data-theme="neon_blue"] .card {
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

    <!-- Contenido -->
    <div class="container mt-5 pt-5">
        <h2 class="text-center mb-4" style="font-family: 'Bebas Neue', sans-serif;">Nuestros Vehículos</h2>

        <!-- Filtros -->
        <div class="row justify-content-center">
            <div class="col-md-10">
                <form method="POST" class="mb-4">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="marca" class="form-label">Marca</label>
                            <select name="marca" id="marca" class="form-select">
                                <option value="">Todas</option>
                                <?php 
                                $marcas->data_seek(0);
                                while ($row = $marcas->fetch_assoc()): ?>
                                    <option value="<?= $row['id_marca'] ?>" <?= isset($_POST['marca']) && $_POST['marca'] == $row['id_marca'] ? 'selected' : '' ?>><?= htmlspecialchars($row['nombre']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="modelo" class="form-label">Modelo</label>
                            <select name="modelo" id="modelo" class="form-select">
                                <option value="">Todos</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="estado" class="form-label">Estado</label>
                            <select name="estado" id="estado" class="form-select">
                                <option value="">Todos</option>
                                <option value="disponible" <?= isset($_POST['estado']) && $_POST['estado'] === 'disponible' ? 'selected' : '' ?>>Disponible</option>
                                <option value="reservado" <?= isset($_POST['estado']) && $_POST['estado'] === 'reservado' ? 'selected' : '' ?>>Reservado</option>
                                <option value="vendido" <?= isset($_POST['estado']) && $_POST['estado'] === 'vendido' ? 'selected' : '' ?>>Vendido</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="precio_min" class="form-label">Precio Mínimo (€)</label>
                            <input type="number" name="precio_min" id="precio_min" class="form-control" value="<?= isset($_POST['precio_min']) ? htmlspecialchars($_POST['precio_min']) : '' ?>" min="0">
                        </div>
                        <div class="col-md-3">
                            <label for="precio_max" class="form-label">Precio Máximo (€)</label>
                            <input type="number" name="precio_max" id="precio_max" class="form-control" value="<?= isset($_POST['precio_max']) ? htmlspecialchars($_POST['precio_max']) : '' ?>" min="0">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-danger w-100" onclick="window.location.href='coches.php'">Reset</button>
                        </div>
                    </div>
                </form>

                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'administrador'): ?>
                    <div class="text-center mb-4">
                        <a href="añadir_vehiculo.php" class="btn btn-success">Añadir Vehículo</a>
                    </div>
                <?php endif; ?>

                <?php if ($no_results): ?>
                    <div class="alert alert-warning text-center">No se encontraron vehículos que coincidan con los filtros seleccionados.</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Lista de vehículos -->
        <div class="mt-4">
            <?php if (!empty($vehiculos_por_marca)): ?>
                <?php foreach ($vehiculos_por_marca as $marca => $vehiculos): ?>
                    <h3 class="brand-header"><?= htmlspecialchars($marca) ?></h3>
                    <div class="row mt-4">
                        <?php foreach ($vehiculos as $vehiculo): ?>
                            <div class="col-md-4 mb-4">
                                <div class="card">
                                    <img src="<?= $vehiculo['imagen'] ? (strpos($vehiculo['imagen'], 'C:/') === 0 ? 'images/' . htmlspecialchars(basename($vehiculo['imagen'])) : htmlspecialchars($vehiculo['imagen'])) : 'images/default_car.jpg' ?>" class="card-img-top" alt="<?= htmlspecialchars($vehiculo['modelo']) ?>">
                                    <div class="card-body">
                                        <h5 class="card-title"><?= htmlspecialchars($vehiculo['marca'] . ' ' . $vehiculo['modelo']) ?></h5>
                                        <p class="card-text">Año: <?= htmlspecialchars($vehiculo['año']) ?></p>
                                        <p class="card-text">Kilometraje: <?= htmlspecialchars($vehiculo['kilometraje']) ?> km</p>
                                        <p class="card-text">Precio: <?= number_format($vehiculo['precio'], 2) ?> €</p>
                                        <p class="card-text">Combustible: <?= htmlspecialchars($vehiculo['tipo_combustible']) ?></p>
                                        <p class="card-text">Transmisión: <?= htmlspecialchars($vehiculo['transmision']) ?></p>
                                        <p class="card-text">
                                            Estado: <span class="badge <?= $vehiculo['estado'] === 'disponible' ? 'bg-success' : ($vehiculo['estado'] === 'reservado' ? 'bg-warning' : 'bg-danger') ?>">
                                                <?= htmlspecialchars(ucfirst($vehiculo['estado'])) ?>
                                            </span>
                                        </p>
                                        <a href="comprar_vehiculo.php?id=<?= htmlspecialchars($vehiculo['id_vehiculo']) ?>" class="btn btn-primary w-100 <?= $vehiculo['estado'] !== 'disponible' ? 'disabled' : '' ?>">Comprar</a>
                                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'administrador'): ?>
                                            <a href="editar_vehiculo.php?id_vehiculo=<?= htmlspecialchars($vehiculo['id_vehiculo']) ?>" class="btn btn-secondary w-100 mt-2">Editar Vehículo</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center">No hay vehículos disponibles en este momento.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
    <script src="modoOscuro.js"></script>
    <script>
        const modelos = <?= json_encode($modelos_array) ?>;
        function updateModelos() {
            const marcaId = document.getElementById('marca').value;
            const modeloSelect = document.getElementById('modelo');
            modeloSelect.innerHTML = '<option value="">Todos</option>';
            if (marcaId) {
                const modelosFiltrados = modelos.filter(modelo => modelo.id_marca == marcaId);
                modelosFiltrados.forEach(modelo => {
                    const option = document.createElement('option');
                    option.value = modelo.id_modelo;
                    option.textContent = modelo.nombre;
                    modeloSelect.appendChild(option);
                });
            }
        }
        document.getElementById('marca').addEventListener('change', updateModelos);
        updateModelos();
    </script>
</body>
</html>