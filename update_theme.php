<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['id_usuario'])) {
    http_response_code(401);
    exit(json_encode(['error' => 'No autorizado']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $theme = $data['theme'] ?? null;

    if (!in_array($theme, ['light', 'dark', 'neon_pink', 'neon_blue'])) {
        http_response_code(400);
        exit(json_encode(['error' => 'Tema inválido']));
    }

    $stmt = $conn->prepare("UPDATE Usuarios SET theme = ? WHERE id_usuario = ?");
    $stmt->bind_param('si', $theme, $_SESSION['id_usuario']);
    if ($stmt->execute()) {
        $_SESSION['theme'] = $theme; // Actualizar sesión
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al actualizar el tema']);
    }
    $stmt->close();
}
?>