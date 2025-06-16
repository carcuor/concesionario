<?php
include('conexion.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $contraseña = password_hash($_POST['contraseña'], PASSWORD_DEFAULT); // Hashear la contraseña
    $rol = "cliente"; // Por defecto todos son clientes

    $stmt = $conn->prepare("INSERT INTO Usuarios (nombre, email, contraseña, rol) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $nombre, $email, $contraseña, $rol);

    if ($stmt->execute()) {
        echo "<script>alert('Usuario registrado correctamente.'); window.location.href='login.php';</script>";
    } else {
        echo "<script>alert('Error al registrar: " . $stmt->error . "'); window.history.back();</script>";
    }

    $stmt->close();
    $conn->close();
}
?>
