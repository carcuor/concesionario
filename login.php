<?php
session_start();

// Redirige al index si ya hay sesión activa
if (isset($_SESSION['role'])) {
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - CarsCuor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&Bangers&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { 
            padding-top: 80px; 
            background: #f5f5f5; 
        }
        .navbar {
            background: linear-gradient(45deg, #e91e63, #6a1b9a);
            box-shadow: 0 4px 20px rgba(233, 30, 99, 0.5);
            padding: 1rem 0;
        }
        .navbar-brand {
            font-family: 'Bangers', cursive;
            font-size: 2.2rem;
            color: #fff;
            text-shadow: 0 0 15px rgba(255, 255, 255, 0.8), 0 0 10px #e91e63;
            transition: transform 0.3s ease;
            display: flex;
            align-items: center;
            animation: pulse 2s infinite;
        }
        .navbar-brand img {
            margin-right: 10px;
            height: 40px;
            filter: drop-shadow(0 0 5px #e91e63);
            transition: transform 0.3s ease;
        }
        .navbar-brand:hover {
            transform: scale(1.1);
            color: #f06292;
        }
        .navbar-brand:hover img {
            transform: rotate(10deg);
        }
        .navbar-toggler {
            border: none;
            padding: 0.5rem;
        }
        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(255, 255, 255, 0.8)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }
        @keyframes pulse {
            0% { text-shadow: 0 0 15px rgba(255, 255, 255, 0.8), 0 0 10px #e91e63; }
            50% { text-shadow: 0 0 25px rgba(255, 255, 255, 1), 0 0 15px #e91e63; }
            100% { text-shadow: 0 0 15px rgba(255, 255, 255, 0.8), 0 0 10px #e91e63; }
        }
        @media (max-width: 992px) {
            .navbar {
                padding: 0.5rem;
            }
            .navbar-brand {
                font-size: 1.8rem;
            }
        }
        .login-container {
            max-width: 500px;
            margin: 0 auto;
            padding: 2rem;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }
        .login-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 3rem;
            color: #1a237e;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .form-control {
            border-radius: 8px;
            border: 1px solid #e1bee7;
            transition: border-color 0.3s ease;
        }
        .form-control:focus {
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
        .alert {
            border-radius: 8px;
        }
        .text-center a {
            color: #6a1b9a;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .text-center a:hover {
            color: #0288d1;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand">
                <img src="images/logo.png" alt="Logo Vehículos" style="height: 40px;">
                CarsCuor
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <!-- No links displayed on login page -->
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5 pt-5">
        <div class="login-container">
            <h2 class="login-title">Iniciar Sesión</h2>
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger text-center"><?= htmlspecialchars($_GET['error']) ?></div>
            <?php endif; ?>
            <form action="verificar_login.php" method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label">Correo Electrónico</label>
                    <input type="email" name="email" id="email" class="form-control" required placeholder="ejemplo@correo.com">
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña</label>
                    <input type="password" name="password" id="password" class="form-control" required placeholder="Tu contraseña">
                </div>
                <button type="submit" class="btn btn-primary w-100">Entrar</button>
                <div class="text-center mt-3">
                    <p>¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a></p>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <?php if (isset($_GET['error'])): ?>
        <script>
            Swal.fire({
                title: 'Error',
                text: '<?= htmlspecialchars($_GET['error']) ?>',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        </script>
    <?php endif; ?>
</body>
</html>