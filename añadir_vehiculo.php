<?php
session_start();
require_once 'conexion.php';

// Attempt to increase PHP upload limits
ini_set('upload_max_filesize', '20M');
ini_set('post_max_size', '20M');

// Restrict access to administrators
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'administrador') {
    header('Location: index.php');
    exit();
}

// Fetch brands and models for dropdowns
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

// Initialize variables
$errors = [];
$success = false;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    $marca = isset($_POST['marca']) ? intval($_POST['marca']) : 0;
    $modelo = isset($_POST['modelo']) ? intval($_POST['modelo']) : 0;
    $año = isset($_POST['año']) ? intval($_POST['año']) : 0;
    $precio = isset($_POST['precio']) ? floatval($_POST['precio']) : 0;
    $kilometraje = isset($_POST['kilometraje']) ? intval($_POST['kilometraje']) : 0;
    $tipo_combustible = isset($_POST['tipo_combustible']) ? trim($_POST['tipo_combustible']) : '';
    $transmision = isset($_POST['transmision']) ? trim($_POST['transmision']) : '';
    $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
    $estado = isset($_POST['estado']) ? trim($_POST['estado']) : '';

    if ($marca <= 0) $errors[] = "Debe seleccionar una marca.";
    if ($modelo <= 0) $errors[] = "Debe seleccionar un modelo.";
    if ($año < 1900 || $año > 2025) $errors[] = "El año debe estar entre 1900 y 2025.";
    if ($precio <= 0) $errors[] = "El precio debe ser mayor que 0.";
    if ($kilometraje < 0) $errors[] = "El kilometraje no puede ser negativo.";
    if (!in_array($tipo_combustible, ['Gasolina', 'Diesel', 'Eléctrico', 'Híbrido'])) $errors[] = "Tipo de combustible no válido.";
    if (!in_array($transmision, ['Manual', 'Automática'])) $errors[] = "Transmisión no válida.";
    if (!in_array($estado, ['disponible', 'vendido', 'reservado'])) $errors[] = "Estado no válido.";

    // Handle image upload
    $imagen_path = null;
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $max_size = 20 * 1024 * 1024; // 20MB
        $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
        $file_size = $_FILES['imagen']['size'];
        $upload_dir = 'images/';

        // Check upload errors
        if ($_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
            if ($_FILES['imagen']['error'] === UPLOAD_ERR_INI_SIZE) {
                $errors[] = "El archivo excede el tamaño máximo permitido por el servidor.";
            } else {
                $errors[] = "Error al subir el archivo (código: {$_FILES['imagen']['error']}).";
            }
        }
        // Check directory
        elseif (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
            $errors[] = "No se pudo crear el directorio images/.";
        }
        elseif (!is_writable($upload_dir)) {
            $errors[] = "El directorio images/ no es escribible. Asegúrese de que tiene permisos de escritura (e.g., chmod 755 images/).";
        }
        // Check file size
        elseif ($file_size > $max_size) {
            $errors[] = "La imagen no debe superar los 20MB.";
        }
        // Check extension
        elseif (!in_array($ext, $allowed_extensions)) {
            $errors[] = "El archivo debe ser una imagen (JPEG, PNG, GIF).";
        }
        // Verify it's an image
        elseif (!getimagesize($_FILES['imagen']['tmp_name'])) {
            $errors[] = "El archivo no es una imagen válida.";
        }
        else {
            $filename = uniqid('vehiculo_') . '.' . $ext;
            $imagen_path = $upload_dir . $filename;
            if (!move_uploaded_file($_FILES['imagen']['tmp_name'], $imagen_path)) {
                $errors[] = "Error al mover la imagen al servidor.";
                $imagen_path = null;
            }
        }
    }

    // If no errors, insert into database
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO Vehiculos (marca, modelo, año, precio, kilometraje, tipo_combustible, transmision, descripcion, estado, imagen) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiidissdss", $marca, $modelo, $año, $precio, $kilometraje, $tipo_combustible, $transmision, $descripcion, $estado, $imagen_path);
        $success = $stmt->execute();
        $vehiculo_id = $stmt->insert_id;
        $stmt->close();

        // Insert image if uploaded
        if ($success && $imagen_path) {
            $stmt = $conn->prepare("INSERT INTO Imagenes_Vehiculos (id_vehiculo, ruta_servidor) VALUES (?, ?)");
            $stmt->bind_param("is", $vehiculo_id, $imagen_path);
            if (!$stmt->execute()) {
                $errors[] = "Error al guardar la imagen: " . $stmt->error;
            }
            $stmt->close();
        }

        if (!$success) {
            $errors[] = "Error al añadir el vehículo: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Añadir Vehículo - CarsCuor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
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
        .form-control, .form-select, .form-control-file {
            border-radius: 8px;
            border: 1px solid #e1bee7;
            transition: border-color 0.3s ease;
        }
        .form-control:focus, .form-select:focus, .form-control-file:focus {
            border-color: #6a1b9a;
            box-shadow: 0 0 8px rgba(106, 27, 154, 0.3);
        }
        .btn-success {
            background: linear-gradient(45deg, #2e7d32, #4caf50);
            border: none;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .btn-success:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.5);
            background: linear-gradient(45deg, #4caf50, #2e7d32);
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
                    <a class="nav-link" href="index.php">Inicio</a>
                </li>
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

<div class="container mt-5 pt-5">
    <h2 class="text-center mb-4" style="font-family: 'Bebas Neue', sans-serif;">Añadir Vehículo</h2>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <?php if ($success): ?>
                <div class="alert alert-success text-center">Vehículo añadido correctamente.</div>
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
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="marca" class="form-label">Marca</label>
                        <select name="marca" id="marca" class="form-select" required>
                            <option value="">Seleccione una marca</option>
                            <?php 
                            $marcas->data_seek(0);
                            while ($row = $marcas->fetch_assoc()): ?>
                                <option value="<?= $row['id_marca'] ?>" <?= isset($_POST['marca']) && $_POST['marca'] == $row['id_marca'] ? 'selected' : '' ?>><?= htmlspecialchars($row['nombre']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="modelo" class="form-label">Modelo</label>
                        <select name="modelo" id="modelo" class="form-select" required>
                            <option value="">Seleccione un modelo</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="año" class="form-label">Año</label>
                        <input type="number" name="año" id="año" class="form-control" value="<?= isset($_POST['año']) ? htmlspecialchars($_POST['año']) : '' ?>" min="1900" max="2025" required>
                    </div>
                    <div class="col-md-6">
                        <label for="precio" class="form-label">Precio (€)</label>
                        <input type="number" name="precio" id="precio" class="form-control" value="<?= isset($_POST['precio']) ? htmlspecialchars($_POST['precio']) : '' ?>" min="0" step="0.01" required>
                    </div>
                    <div class="col-md-6">
                        <label for="kilometraje" class="form-label">Kilometraje (km)</label>
                        <input type="number" name="kilometraje" id="kilometraje" class="form-control" value="<?= isset($_POST['kilometraje']) ? htmlspecialchars($_POST['kilometraje']) : '' ?>" min="0" required>
                    </div>
                    <div class="col-md-6">
                        <label for="tipo_combustible" class="form-label">Tipo de Combustible</label>
                        <select name="tipo_combustible" id="tipo_combustible" class="form-select" required>
                            <option value="">Seleccione</option>
                            <option value="Gasolina" <?= isset($_POST['tipo_combustible']) && $_POST['tipo_combustible'] === 'Gasolina' ? 'selected' : '' ?>>Gasolina</option>
                            <option value="Diesel" <?= isset($_POST['tipo_combustible']) && $_POST['tipo_combustible'] === 'Diesel' ? 'selected' : '' ?>>Diesel</option>
                            <option value="Eléctrico" <?= isset($_POST['tipo_combustible']) && $_POST['tipo_combustible'] === 'Eléctrico' ? 'selected' : '' ?>>Eléctrico</option>
                            <option value="Híbrido" <?= isset($_POST['tipo_combustible']) && $_POST['tipo_combustible'] === 'Híbrido' ? 'selected' : '' ?>>Híbrido</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="transmision" class="form-label">Transmisión</label>
                        <select name="transmision" id="transmision" class="form-select" required>
                            <option value="">Seleccione</option>
                            <option value="Manual" <?= isset($_POST['transmision']) && $_POST['transmision'] === 'Manual' ? 'selected' : '' ?>>Manual</option>
                            <option value="Automática" <?= isset($_POST['transmision']) && $_POST['transmision'] === 'Automática' ? 'selected' : '' ?>>Automática</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="estado" class="form-label">Estado</label>
                        <select name="estado" id="estado" class="form-select" required>
                            <option value="">Seleccione</option>
                            <option value="disponible" <?= isset($_POST['estado']) && $_POST['estado'] === 'disponible' ? 'selected' : '' ?>>Disponible</option>
                            <option value="vendido" <?= isset($_POST['estado']) && $_POST['estado'] === 'vendido' ? 'selected' : '' ?>>Vendido</option>
                            <option value="reservado" <?= isset($_POST['estado']) && $_POST['estado'] === 'reservado' ? 'selected' : '' ?>>Reservado</option>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea name="descripcion" id="descripcion" class="form-control" rows="4"><?= isset($_POST['descripcion']) ? htmlspecialchars($_POST['descripcion']) : '' ?></textarea>
                    </div>
                    <div class="col-md-12">
                        <label for="imagen" class="form-label">Imagen (opcional)</label>
                        <input type="file" name="imagen" id="imagen" class="form-control form-control-file" accept="image/*">
                    </div>
                    <div class="col-md-6">
                        <button type="submit" class="btn btn-success w-100">Añadir Vehículo</button>
                    </div>
                    <div class="col-md-6">
                        <a href="coches.php" class="btn btn-danger w-100">Cancelar</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const modelos = <?= json_encode($modelos_array) ?>;
    function updateModelos() {
        const marcaId = document.getElementById('marca').value;
        const modeloSelect = document.getElementById('modelo');
        modeloSelect.innerHTML = '<option value="">Seleccione un modelo</option>';
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