<?php
session_start();
require_once 'conexion.php';

// Initialize variables
$vehiculo = null;
$errors = [];
$success = '';
$action_message = '';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: coches.php');
    exit;
}

$id_vehiculo = (int)$_GET['id'];

// Fetch vehicle details
$query = "SELECT v.id_vehiculo, m.nombre AS marca, mo.nombre AS modelo, v.año, v.precio, v.kilometraje, v.tipo_combustible, v.transmision,
                 CASE WHEN ve.id_venta IS NOT NULL THEN 'vendido' ELSE v.estado END AS estado,
                 (SELECT iv.ruta_servidor FROM Imagenes_Vehiculos iv WHERE iv.id_vehiculo = v.id_vehiculo LIMIT 1) AS imagen
          FROM Vehiculos v
          JOIN Marcas m ON v.marca = m.id_marca
          JOIN Modelos mo ON v.modelo = mo.id_modelo
          LEFT JOIN Ventas ve ON v.id_vehiculo = ve.id_vehiculo
          WHERE v.id_vehiculo = ?";
$stmt = $conn->prepare($query);
if ($stmt === false) {
    die('Prepare failed: ' . htmlspecialchars($conn->error));
}
$stmt->bind_param('i', $id_vehiculo);
$stmt->execute();
$result = $stmt->get_result();
$vehiculo = $result->num_rows > 0 ? $result->fetch_assoc() : null;
$stmt->close();

if (!$vehiculo) {
    header('Location: coches.php');
    exit;
}

// Handle purchase/reservation form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $metodo_pago = trim($_POST['metodo_pago'] ?? '');
    $accion = trim($_POST['accion'] ?? '');

    // Validation
    if (empty($nombre)) {
        $errors[] = 'El nombre es obligatorio.';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El email no es válido.';
    }
    if (empty($telefono)) {
        $errors[] = 'El teléfono es obligatorio.';
    }
    if (empty($direccion)) {
        $errors[] = 'La dirección es obligatoria.';
    }
    if (!in_array($metodo_pago, ['tarjeta', 'transferencia'])) {
        $errors[] = 'Seleccione un método de pago válido.';
    }
    if (!in_array($accion, ['comprar', 'reservar'])) {
        $errors[] = 'Seleccione una acción válida.';
    }
    if ($metodo_pago === 'tarjeta') {
        $card_number = trim($_POST['card_number'] ?? '');
        $card_expiry = trim($_POST['card_expiry'] ?? '');
        $card_cvv = trim($_POST['card_cvv'] ?? '');
        if (!preg_match('/^\d{16}$/', $card_number)) {
            $errors[] = 'El número de tarjeta debe tener 16 dígitos.';
        }
        if (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $card_expiry)) {
            $errors[] = 'La fecha de expiración debe estar en formato MM/AA.';
        }
        if (!preg_match('/^\d{3,4}$/', $card_cvv)) {
            $errors[] = 'El CVV debe tener 3 o 4 dígitos.';
        }
    }

    if ($vehiculo['estado'] === 'vendido') {
        $errors[] = 'Este vehículo ya ha sido vendido.';
    }

    if (empty($errors)) {
        // Check if user exists
        $stmt = $conn->prepare("SELECT id_usuario FROM Usuarios WHERE email = ?");
        if ($stmt === false) {
            die('Prepare failed: ' . htmlspecialchars($conn->error));
        }
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $id_usuario = $result->num_rows > 0 ? $result->fetch_assoc()['id_usuario'] : null;
        $stmt->close();

        if (!$id_usuario) {
            // Insert new user (as cliente)
            $contrasena = password_hash('default123', PASSWORD_DEFAULT);
            $rol = 'cliente';
            $stmt = $conn->prepare("INSERT INTO Usuarios (nombre, email, contraseña, rol) VALUES (?, ?, ?, ?)");
            if ($stmt === false) {
                die('Prepare failed: ' . htmlspecialchars($conn->error));
            }
            $stmt->bind_param('ssss', $nombre, $email, $contrasena, $rol);
            $stmt->execute();
            $id_usuario = $conn->insert_id;
            $stmt->close();
        }

        if ($accion === 'comprar') {
            // Record sale
            $fecha_venta = date('Y-m-d H:i:s');
            $precio_final = $vehiculo['precio'];
            $stmt = $conn->prepare("INSERT INTO Ventas (id_usuario, id_vehiculo, fecha_venta, precio_final) VALUES (?, ?, ?, ?)");
            if ($stmt === false) {
                die('Prepare failed: ' . htmlspecialchars($conn->error));
            }
            $stmt->bind_param('iisd', $id_usuario, $id_vehiculo, $fecha_venta, $precio_final);
            $stmt->execute();
            $stmt->close();

            // Update vehicle status to vendido
            $stmt = $conn->prepare("UPDATE Vehiculos SET estado = 'vendido' WHERE id_vehiculo = ?");
            if ($stmt === false) {
                die('Prepare failed: ' . htmlspecialchars($conn->error));
            }
            $stmt->bind_param('i', $id_vehiculo);
            $stmt->execute();
            $stmt->close();

            $success = '¡Compra realizada con éxito! Gracias por su compra.';
            $action_message = '¡Compra Confirmada!';
        } else {
            // Record reservation
            $fecha_reserva = date('Y-m-d H:i:s');
            $stmt = $conn->prepare("INSERT INTO Reservas (id_usuario, id_vehiculo, fecha_reserva) VALUES (?, ?, ?)");
            if ($stmt === false) {
                die('Prepare failed: ' . htmlspecialchars($conn->error));
            }
            $stmt->bind_param('iis', $id_usuario, $id_vehiculo, $fecha_reserva);
            $stmt->execute();
            $stmt->close();

            // Update vehicle status to reservado
            $stmt = $conn->prepare("UPDATE Vehiculos SET estado = 'reservado' WHERE id_vehiculo = ?");
            if ($stmt === false) {
                die('Prepare failed: ' . htmlspecialchars($conn->error));
            }
            $stmt->bind_param('i', $id_vehiculo);
            $stmt->execute();
            $stmt->close();

            $success = '¡Reserva realizada con éxito! El vehículo ha sido reservado.';
            $action_message = '¡Reserva Confirmada!';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprar Vehículo - CarsCuor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        .vehicle-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 3rem;
            color: #1a237e;
            margin-bottom: 1rem;
        }
        .vehicle-img {
            max-height: 400px;
            object-fit: cover;
            border-radius: 16px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }
        .vehicle-details {
            font-size: 1.2rem;
            color: #333;
        }
        .vehicle-details p {
            margin: 0.5rem 0;
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
            background: linear-gradient(45deg, #1565c0, #0288d1);
            border: none;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .btn-primary:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(2, 136, 209, 0.5);
            background: linear-gradient(45deg, #0288d1, #1565c0);
        }
        .badge {
            font-size: 1rem;
            padding: 0.5em 1em;
            border-radius: 8px;
        }
        #card-details { display: none; }
        .form-control.card-field { margin-bottom: 1rem; }
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
                    <a class="nav-link active" href="coches.php">Vehículos</a>
                </li>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'administrador'): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="configDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Configuración
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="configDropdown">
                            <li><a class="dropdown-item" href="gestion_marcas.php">Gestión de Marcas</a></li>
                            <li><a class="dropdown-item" href="gestion_modelos.php">Gestión de Modelos</a></li>
                            <li><a class="dropdown-item" href="listar_todo.php">Listar Marcas y Modelos</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
                <?php if (isset($_SESSION['role'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Cerrar sesión</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-5 pt-5">
    <h2 class="text-center mb-4" style="font-family: 'Bebas Neue', sans-serif;">Comprar o Reservar Vehículo</h2>

    <?php if ($success): ?>
        <script>
            Swal.fire({
                title: '<?= htmlspecialchars($action_message) ?>',
                text: '<?= htmlspecialchars($success) ?>',
                icon: 'success',
                confirmButtonText: 'Volver a Vehículos'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'coches.php';
                }
            });
        </script>
    <?php else: ?>
        <div class="row">
            <div class="col-md-6">
                <img src="<?= $vehiculo['imagen'] ? htmlspecialchars($vehiculo['imagen']) : 'images/default_car.jpg' ?>" class="vehicle-img w-100" alt="<?= htmlspecialchars($vehiculo['modelo']) ?>">
            </div>
            <div class="col-md-6">
                <h3 class="vehicle-title"><?= htmlspecialchars($vehiculo['marca'] . ' ' . $vehiculo['modelo']) ?></h3>
                <div class="vehicle-details">
                    <p><strong>Año:</strong> <?= htmlspecialchars($vehiculo['año']) ?></p>
                    <p><strong>Precio:</strong> <?= htmlspecialchars($vehiculo['precio']) ?> €</p>
                    <p><strong>Kilometraje:</strong> <?= htmlspecialchars($vehiculo['kilometraje']) ?> km</p>
                    <p><strong>Combustible:</strong> <?= htmlspecialchars($vehiculo['tipo_combustible']) ?></p>
                    <p><strong>Transmisión:</strong> <?= htmlspecialchars($vehiculo['transmision']) ?></p>
                    <p>
                        <strong>Estado:</strong> 
                        <span class="badge <?= $vehiculo['estado'] === 'disponible' ? 'bg-success' : ($vehiculo['estado'] === 'reservado' ? 'bg-warning' : 'bg-danger') ?>">
                            <?= htmlspecialchars(ucfirst($vehiculo['estado'])) ?>
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger mt-4">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($vehiculo['estado'] !== 'vendido'): ?>
            <div class="mt-5">
                <h4 class="mb-4" style="font-family: 'Bebas Neue', sans-serif;">Formulario de Compra o Reserva</h4>
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="nombre" class="form-label">Nombre Completo</label>
                            <input type="text" name="nombre" id="nombre" class="form-control" value="<?= isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : '' ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" name="email" id="email" class="form-control" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input type="tel" name="telefono" id="telefono" class="form-control" value="<?= isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : '' ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="direccion" class="form-label">Dirección</label>
                            <input type="text" name="direccion" id="direccion" class="form-control" value="<?= isset($_POST['direccion']) ? htmlspecialchars($_POST['direccion']) : '' ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="metodo_pago" class="form-label">Método de Pago</label>
                            <select name="metodo_pago" id="metodo_pago" class="form-select" required>
                                <option value="">Seleccione</option>
                                <option value="tarjeta" <?= isset($_POST['metodo_pago']) && $_POST['metodo_pago'] === 'tarjeta' ? 'selected' : '' ?>>Tarjeta de Crédito</option>
                                <option value="transferencia" <?= isset($_POST['metodo_pago']) && $_POST['metodo_pago'] === 'transferencia' ? 'selected' : '' ?>>Transferencia Bancaria</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="accion" class="form-label">Acción</label>
                            <select name="accion" id="accion" class="form-select" required>
                                <option value="">Seleccione</option>
                                <option value="comprar" <?= isset($_POST['accion']) && $_POST['accion'] === 'comprar' ? 'selected' : '' ?>>Comprar</option>
                                <option value="reservar" <?= isset($_POST['accion']) && $_POST['accion'] === 'reservar' ? 'selected' : '' ?>>Reservar</option>
                            </select>
                        </div>
                        <div class="col-md-12" id="card-details">
                            <label for="card_number" class="form-label">Número de Tarjeta</label>
                            <input type="text" name="card_number" id="card_number" class="form-control card-field" value="<?= isset($_POST['card_number']) ? htmlspecialchars($_POST['card_number']) : '' ?>" placeholder="1234 5678 9012 3456">
                            <label for="card_expiry" class="form-label">Fecha de Expiración (MM/AA)</label>
                            <input type="text" name="card_expiry" id="card_expiry" class="form-control card-field" value="<?= isset($_POST['card_expiry']) ? htmlspecialchars($_POST['card_expiry']) : '' ?>" placeholder="MM/AA">
                            <label for="card_cvv" class="form-label">CVV</label>
                            <input type="text" name="card_cvv" id="card_cvv" class="form-control card-field" value="<?= isset($_POST['card_cvv']) ? htmlspecialchars($_POST['card_cvv']) : '' ?>" placeholder="123">
                        </div>
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary w-100 mt-3">Confirmar</button>
                        </div>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="alert alert-warning mt-4 text-center">
                Este vehículo ya ha sido vendido.
            </div>
            <div class="text-center">
                <a href="coches.php" class="btn btn-primary">Volver a Vehículos</a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Toggle card details visibility
    document.getElementById('metodo_pago').addEventListener('change', function() {
        document.getElementById('card-details').style.display = this.value === 'tarjeta' ? 'block' : 'none';
    });

    // Client-side validation for card details
    document.querySelector('form').addEventListener('submit', function(e) {
        if (document.getElementById('metodo_pago').value === 'tarjeta') {
            const cardNumber = document.getElementById('card_number').value;
            const cardExpiry = document.getElementById('card_expiry').value;
            const cardCvv = document.getElementById('card_cvv').value;
            if (!/^\d{16}$/.test(cardNumber)) {
                e.preventDefault();
                Swal.fire({
                    title: 'Error',
                    text: 'El número de tarjeta debe tener 16 dígitos.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
                return;
            }
            if (!/^(0[1-9]|1[0-2])\/\d{2}$/.test(cardExpiry)) {
                e.preventDefault();
                Swal.fire({
                    title: 'Error',
                    text: 'La fecha de expiración debe estar en formato MM/AA.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
                return;
            }
            if (!/^\d{3,4}$/.test(cardCvv)) {
                e.preventDefault();
                Swal.fire({
                    title: 'Error',
                    text: 'El CVV debe tener 3 o 4 dígitos.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
                return;
            }
        }
    });

    // Initialize card details visibility
    if (document.getElementById('metodo_pago').value === 'tarjeta') {
        document.getElementById('card-details').style.display = 'block';
    }
</script>
</body>
</html>