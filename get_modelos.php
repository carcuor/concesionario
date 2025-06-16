<?php
require_once 'conexion.php';

header('Content-Type: application/json');

if (!isset($_GET['id_marca']) || !is_numeric($_GET['id_marca'])) {
    echo json_encode([]);
    exit;
}

$id_marca = (int)$_GET['id_marca'];

$stmt = $conn->prepare("SELECT id_modelo, nombre FROM Modelos WHERE id_marca = ? ORDER BY nombre");
if ($stmt === false) {
    echo json_encode([]);
    exit;
}
$stmt->bind_param('i', $id_marca);
$stmt->execute();
$result = $stmt->get_result();
$modelos = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode($modelos);
?>