<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'administrador') {
    header('Location: index.php');
    exit();
}

$no_results = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_type']) && isset($_POST['search_term'])) {
    $search_type = $_POST['search_type'];
    $search_term = '%' . trim($_POST['search_term']) . '%';

    if ($search_type === 'marca') {
        $stmt = $conn->prepare("SELECT ma.id_marca, ma.nombre AS marca, m.id_modelo, m.nombre AS modelo, m.foto_modelo AS imagen
                                FROM Marcas ma 
                                LEFT JOIN Modelos m ON ma.id_marca = m.id_marca 
                                WHERE ma.nombre LIKE ? 
                                ORDER BY ma.nombre, m.nombre");
    } elseif ($search_type === 'modelo') {
        $stmt = $conn->prepare("SELECT ma.id_marca, ma.nombre AS marca, m.id_modelo, m.nombre AS modelo, m.foto_modelo AS imagen
                                FROM Marcas ma 
                                LEFT JOIN Modelos m ON ma.id_marca = m.id_marca 
                                WHERE m.nombre LIKE ? 
                                ORDER BY ma.nombre, m.nombre");
    } else {
        $stmt = $conn->prepare("SELECT ma.id_marca, ma.nombre AS marca, m.id_modelo, m.nombre AS modelo, m.foto_modelo AS imagen
                                FROM Marcas ma 
                                LEFT JOIN Modelos m ON ma.id_marca = m.id_marca 
                                ORDER BY ma.nombre, m.nombre");
    }

    if ($search_type === 'marca' || $search_type === 'modelo') {
        $stmt->bind_param("s", $search_term);
    }
    $stmt->execute();
    $modelos = $stmt->get_result();
    $no_results = $modelos->num_rows === 0;
    $stmt->close();
} else {
    $modelos = $conn->query("SELECT ma.id_marca, ma.nombre AS marca, m.id_modelo, m.nombre AS modelo, m.foto_modelo AS imagen
                             FROM Marcas ma 
                             LEFT JOIN Modelos m ON ma.id_marca = m.id_marca 
                             ORDER BY ma.nombre, m.nombre");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de Marcas y Modelos - CarsCuor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            padding-top: 80px; 
            background: #f5f5f5; 
        }
        .navbar {
            background: linear-gradient(45deg, #1a237e, #6a1b9a);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            padding: 1rem 0;
        }
        .navbar-brand {
            font-size: 1.8rem;
            font-weight: bold;
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
        .table-header {
            background: linear-gradient(45deg, #1a237e, #6a1b9a);
            color: #fff;
            text-align: center;
            font-weight: bold;
        }
        .table {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        .table-hover tbody tr:hover {
            background-color: #f3e5f5;
        }
        .text-center {
            text-align: center;
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
        .btn-secondary {
            background: linear-gradient(45deg, rgb(0, 21, 255), rgb(70, 29, 182));
            border: none;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .btn-secondary:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(106, 27, 154, 0.5);
            background: linear-gradient(45deg, rgb(4, 0, 255), rgb(32, 41, 141));
        }
        .btn-danger {
            background: linear-gradient(45deg, #d32f2f, #b71c1c);
            border: none;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .btn-danger:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(183, 28, 28, 0.5);
            background: linear-gradient(45deg, #b71c1c, #d32f2f);
        }
        .table-img {
            width: 100px;
            height: auto;
            object-fit: cover;
            border-radius: 4px;
        }
    </style>
</head>
<body>
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
                    <a class="nav-link" href="coches.php">Vehículos</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="configDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Configuración
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="configDropdown">
                        <li><a class="dropdown-item" href="gestion_marcas.php">Gestión de Marcas</a></li>
                        <li><a class="dropdown-item" href="gestion_modelos.php">Gestión de Modelos</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Cerrar sesión</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <h2 class="text-center mb-4">Listado de Marcas y Modelos</h2>
    <div class="row justify-content-center">
        <div class="col-md-8">
            <form method="POST" class="mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="search_type" class="form-label">Buscar por</label>
                        <select name="search_type" id="search_type" class="form-select" required>
                            <option value="marca" <?= isset($_POST['search_type']) && $_POST['search_type'] === 'marca' ? 'selected' : '' ?>>Marca</option>
                            <option value="modelo" <?= isset($_POST['search_type']) && $_POST['search_type'] === 'modelo' ? 'selected' : '' ?>>Modelo</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="search_term" class="form-label">Término de búsqueda</label>
                        <input type="text" name="search_term" id="search_term" class="form-control" value="<?= isset($_POST['search_term']) ? htmlspecialchars($_POST['search_term']) : '' ?>" required>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-secondary w-100">Buscar</button>
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-danger w-100" onclick="window.location.href='listar_todo.php'">Reset</button>
                    </div>
                </div>
            </form>

            <?php if (isset($_POST['search_term']) && $no_results): ?>
                <div class="alert alert-warning text-center">No se encontraron resultados para "<?= htmlspecialchars($_POST['search_term']) ?>" en <?= $_POST['search_type'] === 'marca' ? 'Marcas' : 'Modelos' ?>.</div>
            <?php endif; ?>

            <table class="table table-striped table-hover">
                <thead>
                    <tr class="table-header">
                        <th>ID Marca</th>
                        <th>Marca</th>
                        <th>ID Modelo</th>
                        <th>Modelo</th>
                        <th>Imagen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $modelos->fetch_assoc()): ?>
                        <tr>
                            <td class="text-center"><?= htmlspecialchars($row['id_marca']) ?></td>
                            <td><?= htmlspecialchars($row['marca']) ?></td>
                            <td class="text-center"><?= $row['id_modelo'] ? htmlspecialchars($row['id_modelo']) : '-' ?></td>
                            <td><?= $row['modelo'] ? htmlspecialchars($row['modelo']) : '-' ?></td>
                            <td class="text-center">
                                <img src="<?= $row['imagen'] ? ($row['imagen'] === 'corolla.jpg' || $row['imagen'] === 'focus.jpg' || $row['imagen'] === 'fiesta.jpg' ? 'images/' : 'Uploads/') . htmlspecialchars(basename($row['imagen'])) : 'images/default_car.jpg' ?>" alt="Modelo <?= htmlspecialchars($row['modelo'] ?? 'Sin modelo') ?>" class="table-img">
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>