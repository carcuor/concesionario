<?php
session_start();
require_once 'conexion.php';

// Obtener tema del usuario
if (isset($_SESSION['id_usuario'])) {
    $stmt = $conn->prepare("SELECT theme FROM Usuarios WHERE id_usuario = ?");
    $stmt->bind_param('i', $_SESSION['id_usuario']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $_SESSION['theme'] = $result->fetch_assoc()['theme'];
    } else {
        $_SESSION['theme'] = 'light';
    }
    $stmt->close();
} else {
    $_SESSION['theme'] = 'light';
}
?>

<!DOCTYPE html>
<html lang="es" data-theme="<?= htmlspecialchars($_SESSION['theme']) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página Principal - CarsCuor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
    <style>
        body {
            padding-top: 80px;
            background: #f5f5f5;
            transition: background-color 0.3s ease;
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
        .main-title {
            margin-top: 5rem;
            padding-top: 5rem;
            animation: fadeIn 1s ease;
        }
        .main-title h1 {
            font-family: 'Bebas Neue', sans-serif;
            color: #1a237e;
            text-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease;
        }
        .main-title h1:hover {
            transform: scale(1.05);
        }
        .main-title p {
            color: #6a1b9a;
            font-size: 1.2rem;
            font-weight: 500;
        }
        .carousel-inner img {
            border-radius: 16px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
            object-fit: cover;
            height: 500px;
        }
        .carousel-control-prev, .carousel-control-next {
            background: rgba(26, 35, 126, 0.3);
            width: 5%;
            border-radius: 10px;
        }
        .carousel-control-prev:hover, .carousel-control-next:hover {
            background: rgba(106, 27, 154, 0.5);
        }
        .chat-container {
            background: linear-gradient(135deg, #f3e5f5, #ffffff);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
            border-radius: 16px;
            border: none;
            transition: all 0.3s ease;
        }
        .chat-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        .chat-box {
            background: #fff;
            border-radius: 10px;
            margin: 10px;
            padding: 10px;
            box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.1);
        }
        #chat-button {
            background: linear-gradient(45deg, #1a237e, #6a1b9a);
            border: none;
            border-radius: 50px;
            padding: 10px 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        #chat-button:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 15px rgba(106, 27, 154, 0.5);
            background: linear-gradient(45deg, #6a1b9a, #1a237e);
        }
        .input-group input.form-control {
            border-radius: 8px 0 0 8px;
            border: 1px solid #e1bee7;
            transition: border-color 0.3s ease;
        }
        .input-group input.form-control:focus {
            border-color: #6a1b9a;
            box-shadow: 0 0 8px rgba(106, 27, 154, 0.3);
        }
        .input-group button {
            background: linear-gradient(45deg, #1a237e, #6a1b9a);
            border: none;
            border-radius: 0 8px 8px 0;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .input-group button:hover {
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
        /* Tema oscuro */
        [data-theme="dark"] body {
            background: #1c2526;
            color: #fff;
        }
        [data-theme="dark"] .navbar {
            background: linear-gradient(45deg, #2c3e50, #34495e);
        }
        [data-theme="dark"] .main-title h1, [data-theme="dark"] .main-title p {
            color: #e1bee7;
        }
        [data-theme="dark"] .chat-container {
            background: linear-gradient(135deg, #2c3e50, #34495e);
        }
        [data-theme="dark"] .chat-box {
            background: #34495e;
            color: #e1bee7;
        }
        [data-theme="dark"] .custom-footer {
            background: linear-gradient(45deg, #2c3e50, #34495e);
        }
        /* Tema neón rosa */
        [data-theme="neon_pink"] .navbar {
            background: linear-gradient(45deg, #e91e63, #ff4081);
        }
        [data-theme="neon_pink"] .main-title h1 {
            color: #ff4081;
        }
        [data-theme="neon_pink"] .main-title p {
            color: #e91e63;
        }
        [data-theme="neon_pink"] .chat-container {
            border: 2px solid #ff4081;
        }
        /* Tema neón azul */
        [data-theme="neon_blue"] .navbar {
            background: linear-gradient(45deg, #0288d1, #03a9f4);
        }
        [data-theme="neon_blue"] .main-title h1 {
            color: #03a9f4;
        }
        [data-theme="neon_blue"] .main-title p {
            color: #0288d1;
        }
        [data-theme="neon_blue"] .chat-container {
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

    <!-- Título principal -->
    <div class="main-title text-center mt-5 pt-5">
        <h1 class="display-4">Bienvenidos a CarsCuor</h1>
        <p class="lead">Conduce la aventura. Elige el vehículo de tus sueños.</p>
    </div>

    <!-- Carrusel -->
    <div id="carouselExample" class="carousel slide mt-5" data-bs-ride="carousel">
        <div class="carousel-inner">
            <div class="carousel-item active">
                <img src="images/img1.png" class="d-block w-100" alt="Coche deportivo rojo en la carretera">
            </div>
            <div class="carousel-item">
                <img src="images/img2.png" class="d-block w-100" alt="Moto deportiva en una pista de carreras">
            </div>
            <div class="carousel-item">
                <img src="images/img3.png" class="d-block w-100" alt="Coche clásico en una carretera abierta">
            </div>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#carouselExample" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#carouselExample" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
    </div>

    <!-- Botón de chat -->
    <button class="btn" id="chat-button" style="position: fixed; bottom: 20px; left: 20px; z-index: 1000;">
        <i class="bi bi-chat"></i> Chat
    </button>

    <!-- Contenedor del chat -->
    <div class="chat-container" id="chat-container" style="position: fixed; bottom: 80px; left: 20px; width: 300px; height: 400px; display: none;">
        <div class="chat-box" id="chat-box" style="height: 350px; overflow-y: auto;"></div>
        <div class="input-group mb-3" style="padding: 10px;">
            <input type="text" class="form-control" id="user-input" placeholder="Escribe algo...">
            <button class="btn" id="send-button">Enviar</button>
        </div>
    </div>

    <!-- Footer -->
    <footer class="custom-footer text-center py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>Acerca de Nosotros</h5>
                    <p>Somos una empresa dedicada a la venta y distribución de vehículos, ofreciendo coches y motos de alta calidad.</p>
                </div>
                <div class="col-md-4">
                    <h5>Enlaces útiles</h5>
                    <ul class="list-unstyled">
                        <li><a href="coches.php">Vehiculos</a></li>
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

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
    <script src="modoOscuro.js"></script>
    <script>
        const respuestas = {
            "hola": "¡Hola! Soy el Canijo de Jerez. ¿En qué puedo ayudarte?",
            "adios": "¡Hasta pronto! Vuelve cuando quieras hablar conmigo.",
            "coches": "Tenemos una gran variedad de coches. ¿Te gustaría saber más sobre algún modelo en particular?",
            "ayuda": "Claro, ¿qué necesitas saber sobre nuestros coches o servicios?, si desea ver todos los comandos escriba 'comandos'",
            "motor": "Nuestros coches vienen con potentes motores V8, ¿te gustaría saber más sobre alguno?",
            "velocidad": "¡Nuestros coches son súper rápidos! ¿Te interesa saber la velocidad de algún modelo?",
            "comandos": "Nuestros comandos son: 'hola', 'adios', 'coches', 'ayuda', 'motor' y 'velocidad', escríbelos y te ayudarán en tu estancia por la web"
        };

        function sendMessage() {
            const userInput = document.getElementById('user-input').value.toLowerCase().trim();
            const chatBox = document.getElementById('chat-box');

            if (userInput) {
                let userMessage = document.createElement('div');
                userMessage.textContent = "Tú: " + userInput;
                chatBox.appendChild(userMessage);

                let botMessage = document.createElement('div');
                botMessage.textContent = "Bot: " + (respuestas[userInput] || "Lo siento, no entiendo lo que dices. ¿Puedes reformularlo?");
                chatBox.appendChild(botMessage);

                chatBox.scrollTop = chatBox.scrollHeight;
                document.getElementById('user-input').value = '';
            }
        }

        document.getElementById('user-input').addEventListener('keypress', function (event) {
            if (event.key === 'Enter') {
                sendMessage();
            }
        });

        document.getElementById('chat-button').addEventListener('click', function () {
            const chatContainer = document.getElementById('chat-container');
            chatContainer.style.display = (chatContainer.style.display === 'none' || chatContainer.style.display === '') ? 'block' : 'none';
        });

        document.getElementById('send-button').addEventListener('click', sendMessage);
    </script>
</body>
</html>