<?php
session_start();
require_once 'conexion.php';

// Restrict to logged-in users
if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}

$id_usuario = $_SESSION['id_usuario'];
$errors = [];
$success = '';

// Fetch user details
$stmt = $conn->prepare("SELECT nombre, email, telefono, direccion, avatar, theme FROM Usuarios WHERE id_usuario = ?");
$stmt->bind_param('i', $id_usuario);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $theme = trim($_POST['theme'] ?? 'light');
    $csrf_token = $_POST['csrf_token'] ?? '';

    // Validate CSRF token
    if (!isset($_SESSION['csrf_token']) || $csrf_token !== $_SESSION['csrf_token']) {
        $errors[] = 'Token CSRF inválido.';
    }

    // Validation
    if (empty($nombre)) {
        $errors[] = 'El nombre es obligatorio.';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El email no es válido.';
    }
    if (!empty($password) && strlen($password) < 8) {
        $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
    }
    if (!in_array($theme, ['light', 'dark', 'neon_pink', 'neon_blue'])) {
        $errors[] = 'Tema inválido.';
    }

    // Handle profile picture upload
    $avatar = $user['avatar'];
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['avatar'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        if (!in_array($file['type'], $allowed_types)) {
            $errors[] = 'Solo se permiten imágenes JPEG, PNG o GIF.';
        } elseif ($file['size'] > $max_size) {
            $errors[] = 'La imagen no debe superar los 2MB.';
        } else {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'avatar_' . $id_usuario . '_' . time() . '.' . $ext;
            $upload_dir = 'images/avatars/';
            $upload_path = $upload_dir . $filename;
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                $avatar = $upload_path;
                // Delete old avatar if exists
                if ($user['avatar'] && file_exists($user['avatar']) && $user['avatar'] !== 'images/default_avatar.jpg') {
                    unlink($user['avatar']);
                }
            } else {
                $errors[] = 'Error al subir la imagen.';
            }
        }
    }

    if (empty($errors)) {
        // Update user
        $query = "UPDATE Usuarios SET nombre = ?, email = ?, telefono = ?, direccion = ?, avatar = ?, theme = ?";
        $params = [$nombre, $email, $telefono, $direccion, $avatar, $theme];
        $types = 'ssssss';
        if (!empty($password)) {
            $query .= ", contraseña = ?";
            $params[] = password_hash($password, PASSWORD_DEFAULT);
            $types .= 's';
        }
        $query .= " WHERE id_usuario = ?";
        $params[] = $id_usuario;
        $types .= 'i';

        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        if ($stmt->execute()) {
            $success = 'Perfil actualizado con éxito.';
        } else {
            $errors[] = 'Error al actualizar el perfil: ' . $conn->error;
        }
        $stmt->close();
    }
}

// Generate CSRF token
$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil - CarsCuor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
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
        .edit-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }
        .edit-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 3rem;
            color: #1a237e;
            margin-bottom: 1.5rem;
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
        .profile-avatar {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #e91e63;
            box-shadow: 0 0 15px rgba(233, 30, 99, 0.5);
            margin-bottom: 1rem;
        }
        .custom-footer {
            background: linear-gradient(45deg, #1a237e, #6a1b9a);
            color: #fff;
            padding: 2rem 0;
            box-shadow: 0 -4px 15px rgba(0, 0, 0, 0.3);
        }
        .custom-footer h5 {
            color: #e1bee7;
            font-family: 'Bebas Neue', sans-serif;
            text-shadow: 0 0 5px rgba(255, 255, 255, 0.3);
        }
        .custom-footer a {
            color: #fff;
            transition: color 0.3s ease;
        }
        .custom-footer a:hover {
            color: #e1bee7;
        }
        .custom-footer hr {
            border-color: #e1bee7;
            opacity: 0.5;
        }
        .custom-footer p {
            margin-bottom: 0;
            font-size: 0.9rem;
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
                        <a class="nav-link" href="index.php">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="coches.php">Vehículos</a>
                    </li>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'administrador'): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
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
                    <?php if (isset($_SESSION['role'])): ?>
                        <li class="nav-item">
                            <a class="nav-link active" href="perfil.php">Perfil</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Cerrar sesión</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Iniciar Sesión</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="registro.php">Registrarse</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Edit Profile Section -->
    <div class="container mt-5 pt-5">
        <div class="edit-container">
            <h2 class="edit-title">Editar Perfil</h2>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <p><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <div class="row g-3">
                    <div class="col-md-12 text-center">
                        <img src="<?= $user['avatar'] ? htmlspecialchars($user['avatar']) : 'images/default_avatar.jpg' ?>" class="profile-avatar" alt="Avatar">
                        <label for="avatar" class="form-label">Cambiar Foto de Perfil</label>
                        <input type="file" name="avatar" id="avatar" class="form-control" accept="image/jpeg,image/png,image/gif">
                    </div>
                    <div class="col-md-6">
                        <label for="nombre" class="form-label">Nombre Completo</label>
                        <input type="text" name="nombre" id="nombre" class="form-control" value="<?= htmlspecialchars($user['nombre']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="telefono" class="form-label">Teléfono</label>
                        <input type="tel" name="telefono" id="telefono" class="form-control" value="<?= htmlspecialchars($user['telefono'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="direccion" class="form-label">Dirección</label>
                        <input type="text" name="direccion" id="direccion" class="form-control" value="<?= htmlspecialchars($user['direccion'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="password" class="form-label">Nueva Contraseña (dejar en blanco para no cambiar)</label>
                        <input type="password" name="password" id="password" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label for="theme" class="form-label">Tema</label>
                        <select name="theme" id="theme" class="form-select">
                            <option value="light" <?= $user['theme'] === 'light' ? 'selected' : '' ?>>Claro</option>
                            <option value="dark" <?= $user['theme'] === 'dark' ? 'selected' : '' ?>>Oscuro</option>
                            <option value="neon_pink" <?= $user['theme'] === 'neon_pink' ? 'selected' : '' ?>>Neón Rosa</option>
                            <option value="neon_blue" <?= $user['theme'] === 'neon_blue' ? 'selected' : '' ?>>Neón Azul</option>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary w-100 mt-3">Guardar Cambios</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="custom-footer text-center py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>Acerca de Nosotros</h5>
                    <p>Somos una empresa dedicada a la venta y distribución de vehículos, ofreciendo coches de alta calidad.</p>
                </div>
                <div class="col-md-4">
                    <h5>Enlaces útiles</h5>
                    <ul class="list-unstyled">
                        <li><a href="coches.php">Coches</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Redes Sociales</h5>
                    <ul class="list-unstyled">
                        <li><a href="#">Facebook</a></li>
                        <li><a href="#">Twitter</a></li>
                        <li><a href="#">Instagram</a></li>
                    </ul>
                </div>
            </div>
            <hr>
            <p>© 2025 CarsCuor. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <?php if ($success): ?>
        <script>
            Swal.fire({
                title: 'Éxito',
                text: '<?= htmlspecialchars($success) ?>',
                icon: 'success',
                confirmButtonText: 'OK'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'perfil.php';
                }
            });
        </script>
    <?php endif; ?>
</body>
</html>