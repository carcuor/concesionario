<?php
$servername = "db"; // Cambia esto si tu base de datos está en otro host
$username = "root"; // Cambia esto si usas otro nombre de usuario
$password = "test"; // Cambia esto si tienes una contraseña
$dbname = "concesionario"; // Nombre de tu base de datos

// Crear la conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar la conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
} else {
}
?>
