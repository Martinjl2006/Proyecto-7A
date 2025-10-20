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

$sql = "SELECT id_provincia, nombre FROM Provincias ORDER BY nombre";
$result = $conn->query($sql);

$provincias = [];
while ($row = $result->fetch_assoc()) {
    $provincias[] = $row;
}

echo json_encode($provincias);
$conn->close();
?>