<?php
header('Content-Type: application/json');

$host = "localhost";
$user = "root";
$password = "";
$db = "LegendAR";

$conn = new mysqli($host, $user, $password, $db);
if ($conn->connect_error) {
    die(json_encode(["error" => "Error de conexión"]));
}

$id_provincia = $_GET['id_provincia'] ?? 0;

$stmt = $conn->prepare("SELECT id_ciudad, nombre FROM Ciudad WHERE id_provincia = ? ORDER BY nombre");
$stmt->bind_param("i", $id_provincia);
$stmt->execute();
$result = $stmt->get_result();

$ciudades = [];
while ($row = $result->fetch_assoc()) {
    $ciudades[] = $row;
}

echo json_encode($ciudades);
$stmt->close();
$conn->close();
?>