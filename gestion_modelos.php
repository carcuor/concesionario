<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'administrador') {
    header('Location: index.php');
    exit();
}

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Añadir modelo
    if (isset($_POST['nuevo_modelo']) && isset($_POST['marca_id'])) {
        $nombre_modelo = trim($_POST['nuevo_modelo']);
        $id_marca = intval($_POST['marca_id']);
        $foto_modelo = null;

        if (!empty($nombre_modelo) && $id_marca > 0) {
            $check = $conn->prepare("SELECT COUNT(*) FROM Modelos WHERE nombre = ? AND id_marca = ?");
            $check->bind_param("si", $nombre_modelo, $id_marca);
            $check->execute();
            $check->bind_result($existe_modelo);
            $check->fetch();
            $check->close();

            if ($existe_modelo > 0) {
                $error = "Ese modelo ya existe para la marca seleccionada.";
            } else {
                // Subir imagen
                if (isset($_FILES['foto_modelo']) && $_FILES['foto_modelo']['error'] === UPLOAD_ERR_OK) {
                    $nombre_archivo = basename($_FILES['foto_modelo']['name']);
                    $extension = strtolower(pathinfo($nombre_archivo, PATHINFO_EXTENSION));
                    $permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                    if (in_array($extension, $permitidas)) {
                        $nuevo_nombre = uniqid('modelo_') . '.' . $extension;
                        $ruta_final = 'Uploads/' . $nuevo_nombre;

                        if (!file_exists('Uploads')) mkdir('Uploads', 0755, true);

                        if (move_uploaded_file($_FILES['foto_modelo']['tmp_name'], $ruta_final)) {
                            $foto_modelo = $ruta_final;
                        } else {
                            $error = "Error al mover la imagen subida.";
                        }
                    } else {
                        $error = "Tipo de archivo no permitido.";
                    }
                }

                if ($error === '') {
                    $res = $conn->query("SELECT MAX(id_modelo) AS max_id FROM Modelos");
                    $row = $res->fetch_assoc();
                    $nuevo_id_modelo = $row['max_id'] + 1;

                    $stmt = $conn->prepare("INSERT INTO Modelos (id_modelo, id_marca, nombre, foto_modelo) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("iiss", $nuevo_id_modelo, $id_marca, $nombre_modelo, $foto_modelo);
                    if ($stmt->execute()) {
                        $mensaje = "Modelo añadido correctamente.";
                    } else {
                        $error = "Error al insertar el modelo.";
                    }
                    $stmt->close();
                }
            }
        }
    }

    // Editar modelo
    if (isset($_POST['editar_modelo']) && isset($_POST['id_modelo']) && isset($_POST['editar_marca_id'])) {
        $id_modelo = intval($_POST['id_modelo']);
        $nombre_modelo = trim($_POST['editar_modelo']);
        $id_marca = intval($_POST['editar_marca_id']);
        $foto_modelo = null;

        if (!empty($nombre_modelo) && $id_marca > 0) {
            $check = $conn->prepare("SELECT COUNT(*) FROM Modelos WHERE nombre = ? AND id_marca = ? AND id_modelo != ?");
            $check->bind_param("sii", $nombre_modelo, $id_marca, $id_modelo);
            $check->execute();
            $check->bind_result($existe_modelo);
            $check->fetch();
            $check->close();

            if ($existe_modelo > 0) {
                $error = "Ese modelo ya existe para la marca seleccionada.";
            } else {
                $conn->begin_transaction();
                try {
                    // Obtener la foto actual
                    $stmt = $conn->prepare("SELECT foto_modelo FROM Modelos WHERE id_modelo = ?");
                    $stmt->bind_param("i", $id_modelo);
                    $stmt->execute();
                    $stmt->bind_result($foto_actual);
                    $stmt->fetch();
                    $stmt->close();

                    // Subir nueva imagen si se proporcionó
                    if (isset($_FILES['editar_foto_modelo']) && $_FILES['editar_foto_modelo']['error'] === UPLOAD_ERR_OK) {
                        $nombre_archivo = basename($_FILES['editar_foto_modelo']['name']);
                        $extension = strtolower(pathinfo($nombre_archivo, PATHINFO_EXTENSION));
                        $permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                        if (in_array($extension, $permitidas)) {
                            $nuevo_nombre = uniqid('modelo_') . '.' . $extension;
                            $ruta_final = 'Uploads/' . $nuevo_nombre;

                            if (!file_exists('Uploads')) mkdir('Uploads', 0755, true);

                            if (move_uploaded_file($_FILES['editar_foto_modelo']['tmp_name'], $ruta_final)) {
                                $foto_modelo = $ruta_final;
                                // Eliminar la imagen anterior si existe
                                if ($foto_actual && file_exists($foto_actual)) {
                                    unlink($foto_actual);
                                }
                            } else {
                                throw new Exception("Error al mover la imagen subida.");
                            }
                        } else {
                            throw new Exception("Tipo de archivo no permitido.");
                        }
                    } else {
                        $foto_modelo = $foto_actual; // Mantener la foto actual
                    }

                    // Actualizar el modelo
                    $stmt = $conn->prepare("UPDATE Modelos SET id_marca = ?, nombre = ?, foto_modelo = ? WHERE id_modelo = ?");
                    $stmt->bind_param("issi", $id_marca, $nombre_modelo, $foto_modelo, $id_modelo);
                    if ($stmt->execute()) {
                        $conn->commit();
                        $mensaje = "Modelo actualizado correctamente.";
                    } else {
                        throw new Exception("Error al actualizar el modelo.");
                    }
                    $stmt->close();
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = $e->getMessage();
                }
            }
        } else {
            $error = "El nombre del modelo y la marca no pueden estar vacíos.";
        }
    }

    // Eliminar modelo
    if (isset($_POST['eliminar_modelo'])) {
        $id_modelo = intval($_POST['eliminar_modelo']);
        
        // Iniciar transacción
        $conn->begin_transaction();
        try {
            // Obtener vehículos asociados
            $stmt = $conn->prepare("SELECT id_vehiculo, imagen FROM Vehiculos WHERE modelo = ?");
            $stmt->bind_param("i", $id_modelo);
            $stmt->execute();
            $vehiculos_result = $stmt->get_result();
            $vehiculos = $vehiculos_result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            foreach ($vehiculos as $vehiculo) {
                $id_vehiculo = $vehiculo['id_vehiculo'];

                // Eliminar registros dependientes en Imagenes_Vehiculos
                $stmt = $conn->prepare("SELECT ruta_servidor FROM Imagenes_Vehiculos WHERE id_vehiculo = ?");
                $stmt->bind_param("i", $id_vehiculo);
                $stmt->execute();
                $imagenes_result = $stmt->get_result();
                while ($imagen = $imagenes_result->fetch_assoc()) {
                    if ($imagen['ruta_servidor'] && file_exists($imagen['ruta_servidor'])) {
                        unlink($imagen['ruta_servidor']);
                    }
                }
                $stmt->close();

                $stmt = $conn->prepare("DELETE FROM Imagenes_Vehiculos WHERE id_vehiculo = ?");
                $stmt->bind_param("i", $id_vehiculo);
                $stmt->execute();
                $stmt->close();

                // Eliminar registros dependientes en Reservas
                $stmt = $conn->prepare("DELETE FROM Reservas WHERE id_vehiculo = ?");
                $stmt->bind_param("i", $id_vehiculo);
                $stmt->execute();
                $stmt->close();

                // Eliminar registros dependientes en Pruebas_Manejo
                $stmt = $conn->prepare("DELETE FROM Pruebas_Manejo WHERE id_vehiculo = ?");
                $stmt->bind_param("i", $id_vehiculo);
                $stmt->execute();
                $stmt->close();

                // Eliminar registros dependientes en Ventas
                $stmt = $conn->prepare("DELETE FROM Ventas WHERE id_vehiculo = ?");
                $stmt->bind_param("i", $id_vehiculo);
                $stmt->execute();
                $stmt->close();

                // Eliminar imagen del vehículo
                if ($vehiculo['imagen'] && file_exists($vehiculo['imagen'])) {
                    unlink($vehiculo['imagen']);
                }
            }

            // Eliminar vehículos
            $stmt = $conn->prepare("DELETE FROM Vehiculos WHERE modelo = ?");
            $stmt->bind_param("i", $id_modelo);
            $stmt->execute();
            $stmt->close();

            // Obtener y eliminar la imagen del modelo
            $stmt = $conn->prepare("SELECT foto_modelo FROM Modelos WHERE id_modelo = ?");
            $stmt->bind_param("i", $id_modelo);
            $stmt->execute();
            $stmt->bind_result($foto_modelo);
            $stmt->fetch();
            $stmt->close();

            // Eliminar el modelo
            $stmt = $conn->prepare("DELETE FROM Modelos WHERE id_modelo = ?");
            $stmt->bind_param("i", $id_modelo);
            if ($stmt->execute()) {
                // Eliminar la imagen del modelo si existe
                if ($foto_modelo && file_exists($foto_modelo)) {
                    unlink($foto_modelo);
                }
                $conn->commit();
                $mensaje = "Modelo y todos sus vehículos asociados eliminados con éxito.";
            } else {
                throw new Exception("Error al eliminar el modelo.");
            }
            $stmt->close();
        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}

$marcas = $conn->query("SELECT id_marca, nombre FROM Marcas ORDER BY nombre ASC");
$modelos = $conn->query("SELECT m.id_modelo, m.id_marca, m.nombre AS modelo, ma.nombre AS marca 
                         FROM Modelos m 
                         JOIN Marcas ma ON m.id_marca = ma.id_marca 
                         ORDER BY ma.nombre, m.nombre");
$modelos_array = [];
while ($row = $modelos->fetch_assoc()) {
    $modelos_array[] = [
        'id_modelo' => $row['id_modelo'],
        'id_marca' => $row['id_marca'],
        'modelo' => $row['modelo'],
        'marca' => $row['marca']
    ];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Modelos - CarsCuor</title>
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
        .card {
            background: linear-gradient(135deg, #f3e5f5, #ffffff);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
            border-radius: 16px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
        }
        .card-edit {
            background: linear-gradient(135deg, #bbdefb, #e3f2fd);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
            border-radius: 16px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
        }
        .card:hover, .card-edit:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        .card-header.success {
            background: linear-gradient(45deg, #1a237e, #6a1b9a);
            color: #fff;
            text-align: center;
            border-radius: 16px 16px 0 0;
            font-weight: bold;
            text-shadow: 0 0 5px rgba(255, 255, 255, 0.3);
        }
        .card-header.edit {
            background: linear-gradient(45deg, #1565c0, #0288d1);
            color: #fff;
            text-align: center;
            border-radius: 16px 16px 0 0;
            font-weight: bold;
            text-shadow: 0 0 5px rgba(255, 255, 255, 0.3);
        }
        .card-header.danger {
            background: linear-gradient(45deg, #d32f2f, #b71c1c);
            color: #fff;
            text-align: center;
            border-radius: 16px 16px 0 0;
            font-weight: bold;
            text-shadow: 0 0 5px rgba(255, 255, 255, 0.3);
        }
        .card-body {
            padding: 1.5rem;
        }
        .btn-success {
            background: linear-gradient(45deg, #1a237e, #6a1b9a);
            border: none;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .btn-success:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(106, 27, 154, 0.5);
            background: linear-gradient(45deg, #6a1b9a, #1a237e);
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

            .btn-secondary {
            background: linear-gradient(45deg,rgb(0, 21, 255),rgb(70, 29, 182));
            border: none;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .btn-secondary:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(106, 27, 154, 0.5);
            background: linear-gradient(45deg,rgb(4, 0, 255),rgb(32, 41, 141));
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
                        <li><a class="dropdown-item active" href="gestion_modelos.php">Gestión de Modelos</a></li>
                        <li><a class="dropdown-item" href="listar_todo.php">Listar Marcas y Modelos</a></li>
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
    <h2 class="text-center mb-4">Gestión de Modelos</h2>

    <?php if ($mensaje): ?>
        <div class="alert alert-success text-center"><?= htmlspecialchars($mensaje) ?></div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-md-6 mb-4">
            <!-- Añadir Modelo -->
            <div class="card mb-4">
                <div class="card-header success">Añadir Nuevo Modelo</div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="marca_id" class="form-label">Selecciona una Marca</label>
                            <select name="marca_id" id="marca_id" class="form-select" required>
                                <option value="">-- Selecciona Marca --</option>
                                <?php 
                                $marcas->data_seek(0);
                                while ($row = $marcas->fetch_assoc()): ?>
                                    <option value="<?= $row['id_marca'] ?>"><?= htmlspecialchars($row['nombre']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="nuevo_modelo" class="form-label">Nombre del Modelo</label>
                            <input type="text" name="nuevo_modelo" id="nuevo_modelo" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="foto_modelo" class="form-label">Foto del Modelo</label>
                            <input type="file" name="foto_modelo" id="foto_modelo" class="form-control" accept="image/*">
                        </div>
                        <button type="submit" class="btn btn-success w-100">Añadir Modelo</button>
                    </form>
                </div>
            </div>

            <!-- Editar Modelo -->
            <div class="card-edit mb-4">
                <div class="card-header edit">Editar Modelo</div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="editar_marca_id" class="form-label">Selecciona una Marca</label>
                            <select name="editar_marca_id" id="editar_marca_id" class="form-select" required onchange="updateModelos()">
                                <option value="">-- Selecciona Marca --</option>
                                <?php 
                                $marcas->data_seek(0);
                                while ($row = $marcas->fetch_assoc()): ?>
                                    <option value="<?= $row['id_marca'] ?>"><?= htmlspecialchars($row['nombre']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="id_modelo" class="form-label">Selecciona un Modelo</label>
                            <select name="id_modelo" id="id_modelo" class="form-select" required>
                                <option value="">-- Selecciona Modelo --</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editar_modelo" class="form-label">Nuevo Nombre del Modelo</label>
                            <input type="text" name="editar_modelo" id="editar_modelo" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="editar_foto_modelo" class="form-label">Nueva Foto del Modelo (Opcional)</label>
                            <input type="file" name="editar_foto_modelo" id="editar_foto_modelo" class="form-control" accept="image/*">
                        </div>
                        <button type="submit" class="btn btn-secondary w-100">Actualizar Modelo</button>
                    </form>
                </div>
            </div>

            <!-- Eliminar Modelo -->
            <div class="card">
                <div class="card-header danger">Eliminar Modelo</div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="eliminar_modelo" class="form-label">Selecciona un Modelo</label>
                            <select name="eliminar_modelo" id="eliminar_modelo" class="form-select" required>
                                <option value="">-- Selecciona Modelo --</option>
                                <?php 
                                $modelos->data_seek(0);
                                while ($row = $modelos->fetch_assoc()): ?>
                                    <option value="<?= $row['id_modelo'] ?>"><?= htmlspecialchars($row['marca'] . ' - ' . $row['modelo']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-danger w-100" onclick="return confirm('¿Estás seguro de que deseas eliminar este modelo? Esto eliminará todos los vehículos asociados.');">Eliminar Modelo</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const modelos = <?= json_encode($modelos_array) ?>;

    function updateModelos() {
        const marcaId = document.getElementById('editar_marca_id').value;
        const modeloSelect = document.getElementById('id_modelo');
        
        // Limpiar el dropdown de modelos
        modeloSelect.innerHTML = '<option value="">-- Selecciona Modelo --</option>';
        
        if (marcaId) {
            // Filtrar modelos por id_marca
            const modelosFiltrados = modelos.filter(modelo => modelo.id_marca == marcaId);
            
            // Agregar opciones al dropdown
            modelosFiltrados.forEach(modelo => {
                const option = document.createElement('option');
                option.value = modelo.id_modelo;
                option.textContent = modelo.modelo;
                modeloSelect.appendChild(option);
            });
        }
    }
</script>
</body>
</html>