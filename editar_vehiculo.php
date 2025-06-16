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

// Obtener id_vehiculo de GET
if (!isset($_GET['id_vehiculo']) || !is_numeric($_GET['id_vehiculo'])) {
    header('Location: coches.php');
    exit;
}
$id_vehiculo = (int)$_GET['id_vehiculo'];

// Obtener datos del vehículo
$stmt = $conn->prepare("
    SELECT v.*, m.id_marca, mo.id_modelo, mo.nombre AS nombre_modelo
    FROM Vehiculos v
    JOIN Marcas m ON v.marca = m.id_marca
    JOIN Modelos mo ON v.modelo = mo.id_modelo
    WHERE v.id_vehiculo = ?
");
if ($stmt === false) {
    die('Error en la preparación de la consulta: ' . htmlspecialchars($conn->error));
}
$stmt->bind_param('i', $id_vehiculo);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    header('Location: coches.php');
    exit;
}
$vehiculo = $result->fetch_assoc();
$stmt->close();

// Obtener imágenes del vehículo
$stmt = $conn->prepare("SELECT id_imagen, ruta_servidor FROM Imagenes_Vehiculos WHERE id_vehiculo = ?");
if ($stmt === false) {
    die('Error en la preparación de la consulta de imágenes: ' . htmlspecialchars($conn->error));
}
$stmt->bind_param('i', $id_vehiculo);
$stmt->execute();
$imagenes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Obtener todas las marcas
$marcas = $conn->query("SELECT id_marca, nombre FROM Marcas ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);

// Procesar formulario
$errors = [];
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = 'Error de seguridad. Intenta de nuevo.';
    } else {
        // Obtener y validar datos
        $id_marca = (int)($_POST['marca'] ?? 0);
        $id_modelo = (int)($_POST['modelo'] ?? 0);
        $año = (int)($_POST['año'] ?? 0);
        $precio = (float)($_POST['precio'] ?? 0);
        $kilometraje = (int)($_POST['kilometraje'] ?? 0);
        $tipo_combustible = $_POST['tipo_combustible'] ?? '';
        $transmision = $_POST['transmision'] ?? '';
        $descripcion = trim($_POST['descripcion'] ?? '');
        $estado = $_POST['estado'] ?? '';
        $imagen_principal = $_FILES['imagen_principal'] ?? null;

        // Validaciones
        if ($id_marca <= 0) $errors[] = 'Selecciona una marca válida.';
        if ($id_modelo <= 0) $errors[] = 'Selecciona un modelo válido.';
        if ($año < 1900 || $año > date('Y') + 1) $errors[] = 'El año debe estar entre 1900 y ' . (date('Y') + 1) . '.';
        if ($precio <= 0) $errors[] = 'El precio debe ser mayor a 0.';
        if ($kilometraje < 0) $errors[] = 'El kilometraje no puede ser negativo.';
        if (!in_array($tipo_combustible, ['Gasolina', 'Diesel', 'Eléctrico', 'Híbrido'])) $errors[] = 'Tipo de combustible inválido.';
        if (!in_array($transmision, ['Manual', 'Automática'])) $errors[] = 'Transmisión inválida.';
        if (!in_array($estado, ['disponible', 'reservado'])) $errors[] = 'Estado inválido.';

        // Validar imagen principal (si se subió)
        $imagen_path = $vehiculo['imagen'];
        if ($imagen_principal && $imagen_principal['size'] > 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($imagen_principal['type'], $allowed_types)) {
                $errors[] = 'La imagen principal debe ser JPG, PNG o GIF.';
            } elseif ($imagen_principal['size'] > 2 * 1024 * 1024) {
                $errors[] = 'La imagen principal no debe exceder 2MB.';
            } else {
                $ext = pathinfo($imagen_principal['name'], PATHINFO_EXTENSION);
                $filename = 'vehiculo_' . uniqid() . '.' . $ext;
                $upload_dir = 'images/';
                $imagen_path = $upload_dir . $filename;
                if (!move_uploaded_file($imagen_principal['tmp_name'], $upload_dir . $filename)) {
                    $errors[] = 'Error al subir la imagen principal.';
                }
            }
        }

        // Procesar imágenes adicionales
        $imagenes_adicionales = $_FILES['imagenes_adicionales'] ?? null;
        $nuevas_imagenes = [];
        if ($imagenes_adicionales && $imagenes_adicionales['size'][0] > 0) {
            foreach ($imagenes_adicionales['tmp_name'] as $key => $tmp_name) {
                if ($imagenes_adicionales['size'][$key] > 0) {
                    if (!in_array($imagenes_adicionales['type'][$key], ['image/jpeg', 'image/png', 'image/gif'])) {
                        $errors[] = 'Las imágenes adicionales deben ser JPG, PNG o GIF.';
                        break;
                    } elseif ($imagenes_adicionales['size'][$key] > 2 * 1024 * 1024) {
                        $errors[] = 'Cada imagen adicional no debe exceder 2MB.';
                        break;
                    } else {
                        $ext = pathinfo($imagenes_adicionales['name'][$key], PATHINFO_EXTENSION);
                        $filename = 'vehiculo_' . uniqid() . '.' . $ext;
                        $upload_dir = 'images/';
                        if (move_uploaded_file($tmp_name, $upload_dir . $filename)) {
                            $nuevas_imagenes[] = $upload_dir . $filename;
                        } else {
                            $errors[] = 'Error al subir una imagen adicional.';
                            break;
                        }
                    }
                }
            }
        }

        // Eliminar imágenes seleccionadas
        $imagenes_eliminar = $_POST['eliminar_imagenes'] ?? [];
        foreach ($imagenes_eliminar as $id_imagen) {
            $stmt = $conn->prepare("SELECT ruta_servidor FROM Imagenes_Vehiculos WHERE id_imagen = ? AND id_vehiculo = ?");
            $stmt->bind_param('ii', $id_imagen, $id_vehiculo);
            $stmt->execute();
            $img = $stmt->get_result()->fetch_assoc();
            if ($img && file_exists($img['ruta_servidor'])) {
                unlink($img['ruta_servidor']);
            }
            $stmt = $conn->prepare("DELETE FROM Imagenes_Vehiculos WHERE id_imagen = ? AND id_vehiculo = ?");
            $stmt->bind_param('ii', $id_imagen, $id_vehiculo);
            $stmt->execute();
            $stmt->close();
        }

        // Guardar cambios si no hay errores
        if (empty($errors)) {
            $stmt = $conn->prepare("
                UPDATE Vehiculos
                SET marca = ?, modelo = ?, año = ?, precio = ?, kilometraje = ?,
                    tipo_combustible = ?, transmision = ?, descripcion = ?, imagen = ?, estado = ?
                WHERE id_vehiculo = ?
            ");
            if ($stmt === false) {
                $errors[] = 'Error en la preparación de la consulta de actualización: ' . htmlspecialchars($conn->error);
            } else {
                $stmt->bind_param('iiidississi', $id_marca, $id_modelo, $año, $precio, $kilometraje,
                    $tipo_combustible, $transmision, $descripcion, $imagen_path, $estado, $id_vehiculo);
                if ($stmt->execute()) {
                    // Insertar nuevas imágenes
                    foreach ($nuevas_imagenes as $ruta) {
                        $stmt_img = $conn->prepare("INSERT INTO Imagenes_Vehiculos (id_vehiculo, ruta_servidor) VALUES (?, ?)");
                        $stmt_img->bind_param('is', $id_vehiculo, $ruta);
                        $stmt_img->execute();
                        $stmt_img->close();
                    }
                    $success = 'Vehículo actualizado con éxito.';
                    header('Location: coches.php');
                    exit;
                } else {
                    $errors[] = 'Error al actualizar el vehículo.';
                }
                $stmt->close();
            }
        }
    }
}

// Generar token CSRF
$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;
?>

<!DOCTYPE html>
<html lang="es" data-theme="<?= htmlspecialchars($_SESSION['theme']) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Vehículo - CarsCuor</title>
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
        .form-container {
            background: linear-gradient(135deg, #ffffff, #f3e5f5);
            border: 2px solid #e1bee7;
            border-radius: 16px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
            padding: 2rem;
            margin-top: 2rem;
            transition: transform 0.3s ease;
        }
        .form-container:hover {
            transform: translateY(-5px);
        }
        .form-container h2 {
            font-family: 'Bebas Neue', sans-serif;
            color: #1a237e;
            text-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        .form-control, .form-select {
            border: 1px solid #e1bee7;
            border-radius: 8px;
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
        .image-preview-container img {
            width: 100px !important;
            height: 100px !important;
            object-fit: cover;
            border: 2px solid #6a1b9a;
            border-radius: 8px;
            margin: 0.5rem;
            transition: transform 0.3s ease;
            display: block;
        }
        .image-preview-container img:hover {
            transform: scale(1.1);
        }
        .image-preview-container {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .image-preview-item {
            text-align: center;
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
        [data-theme="dark"] .form-container {
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
        [data-theme="neon_pink"] .form-container {
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
        [data-theme="neon_blue"] .form-container {
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

    <!-- Formulario de edición -->
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="form-container">
                    <h2 class="text-center mb-4">Editar Vehículo</h2>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="marca" class="form-label">Marca</label>
                                <select id="marca" name="marca" class="form-select" required>
                                    <option value="">Selecciona una marca</option>
                                    <?php foreach ($marcas as $marca): ?>
                                        <option value="<?= $marca['id_marca'] ?>" <?= $marca['id_marca'] == $vehiculo['marca'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($marca['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="modelo" class="form-label">Modelo</label>
                                <select id="modelo" name="modelo" class="form-select" required>
                                    <option value="<?= $vehiculo['modelo'] ?>"><?= htmlspecialchars($vehiculo['nombre_modelo']) ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="año" class="form-label">Año</label>
                                <input type="number" id="año" name="año" class="form-control" value="<?= htmlspecialchars($vehiculo['año']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="precio" class="form-label">Precio (€)</label>
                                <input type="number" id="precio" name="precio" class="form-control" step="0.01" value="<?= htmlspecialchars($vehiculo['precio']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="kilometraje" class="form-label">Kilometraje (km)</label>
                                <input type="number" id="kilometraje" name="kilometraje" class="form-control" value="<?= htmlspecialchars($vehiculo['kilometraje']) ?>" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="tipo_combustible" class="form-label">Tipo de Combustible</label>
                                <select id="tipo_combustible" name="tipo_combustible" class="form-select" required>
                                    <option value="Gasolina" <?= $vehiculo['tipo_combustible'] === 'Gasolina' ? 'selected' : '' ?>>Gasolina</option>
                                    <option value="Diesel" <?= $vehiculo['tipo_combustible'] === 'Diesel' ? 'selected' : '' ?>>Diesel</option>
                                    <option value="Eléctrico" <?= $vehiculo['tipo_combustible'] === 'Eléctrico' ? 'selected' : '' ?>>Eléctrico</option>
                                    <option value="Híbrido" <?= $vehiculo['tipo_combustible'] === 'Híbrido' ? 'selected' : '' ?>>Híbrido</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="transmision" class="form-label">Transmisión</label>
                                <select id="transmision" name="transmision" class="form-select" required>
                                    <option value="Manual" <?= $vehiculo['transmision'] === 'Manual' ? 'selected' : '' ?>>Manual</option>
                                    <option value="Automática" <?= $vehiculo['transmision'] === 'Automática' ? 'selected' : '' ?>>Automática</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea id="descripcion" name="descripcion" class="form-control" rows="4"><?= htmlspecialchars($vehiculo['descripcion'] ?? '') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="estado" class="form-label">Estado</label>
                            <select id="estado" name="estado" class="form-select" required>
                                <option value="disponible" <?= $vehiculo['estado'] === 'disponible' ? 'selected' : '' ?>>Disponible</option>
                                <option value="reservado" <?= $vehiculo['estado'] === 'reservado' ? 'selected' : '' ?>>Reservado</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="imagen_principal" class="form-label">Imagen Principal (opcional)</label>
                            <input type="file" id="imagen_principal" name="imagen_principal" class="form-control" accept="image/jpeg,image/png,image/gif">
                            <?php if ($vehiculo['imagen']): ?>
                                <div class="image-preview-container mt-2">
                                    <div class="image-preview-item">
                                        <img src="<?= htmlspecialchars($vehiculo['imagen']) ?>" alt="Imagen Principal">
                                        <div>Imagen Principal Actual</div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Imágenes Adicionales</label>
                            <div class="image-preview-container">
                                <?php foreach ($imagenes as $imagen): ?>
                                    <div class="image-preview-item">
                                        <img src="<?= htmlspecialchars($imagen['ruta_servidor']) ?>" alt="Imagen Vehículo">
                                        <div>
                                            <input type="checkbox" name="eliminar_imagenes[]" value="<?= $imagen['id_imagen'] ?>">
                                            <label>Eliminar</label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <input type="file" name="imagenes_adicionales[]" class="form-control" accept="image/jpeg,image/png,image/gif" multiple>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                            <a href="coches.php" class="btn btn-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
    <script src="modoOscuro.js"></script>
    <script>
        document.getElementById('marca').addEventListener('change', function() {
            const idMarca = this.value;
            const modeloSelect = document.getElementById('modelo');
            modeloSelect.innerHTML = '<option value="">Cargando modelos...</option>';

            if (idMarca) {
                fetch(`get_modelos.php?id_marca=${idMarca}`)
                    .then(response => response.json())
                    .then(data => {
                        modeloSelect.innerHTML = '<option value="">Selecciona un modelo</option>';
                        data.forEach(modelo => {
                            const option = document.createElement('option');
                            option.value = modelo.id_modelo;
                            option.textContent = modelo.nombre;
                            modeloSelect.appendChild(option);
                        });
                    })
                    .catch(error => {
                        console.error('Error al cargar modelos:', error);
                        modeloSelect.innerHTML = '<option value="">Error al cargar modelos</option>';
                    });
            } else {
                modeloSelect.innerHTML = '<option value="">Selecciona una marca primero</option>';
            }
        });
    </script>
</body>
</html>