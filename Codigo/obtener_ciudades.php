<?php
require_once "main.php";

header('Content-Type: application/json');

$id_provincia = $_GET['id_provincia'] ?? 0;

if ($id_provincia > 0) {
    $stmt = $conn->prepare("SELECT id_ciudad, Nombre FROM Ciudad WHERE id_provincia = ? ORDER BY Nombre");
    $stmt->bind_param("i", $id_provincia);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    $ciudades = [];
    while($ciudad = $resultado->fetch_assoc()) {
        $ciudades[] = $ciudad;
    }
    
    echo json_encode($ciudades);
} else {
    echo json_encode([]);
}
?>