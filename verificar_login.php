<?php
session_start();
include('conexion.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $query = "SELECT * FROM Usuarios WHERE email = ?";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        header("Location: login.php?error=Error en el servidor");
        exit();
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $usuario = $result->fetch_assoc();

        // Verificación de contraseña hasheada
        if (password_verify($password, $usuario['contraseña'])) {
            $_SESSION['role'] = $usuario['rol'];
            $_SESSION['email'] = $usuario['email'];
            $_SESSION['id_usuario'] = $usuario['id_usuario'];
            $_SESSION['nombre'] = $usuario['nombre'];
            header('Location: index.php');
            exit();
        } else {
            header("Location: login.php?error=Contraseña incorrecta");
            exit();
        }
    } else {
        header("Location: login.php?error=Usuario no encontrado");
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}
