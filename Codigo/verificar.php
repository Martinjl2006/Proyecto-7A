<?php
$host = "localhost";
$user = "root";
$password = "";
$db = "LegendAR";

$conn = new mysqli($host, $user, $password, $db);
if ($conn->connect_error) {
    die("❌ Error de conexión: " . $conn->connect_error);
}

if (!isset($_GET['token'])) {
    die("❌ Token de verificación faltante.");
}

$token = $_GET['token'];

$stmt = $conn->prepare("SELECT id_usuario FROM Usuarios WHERE token_verificacion = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($fila = $result->fetch_assoc()) {
    $id = $fila['id_usuario'];
    $update = $conn->prepare("UPDATE Usuarios SET verificado = 1, token_verificacion = NULL WHERE id_usuario = ?");
    $update->bind_param("i", $id);
    $update->execute();

    echo "<h2>✅ Tu cuenta ha sido verificada correctamente. Ya podés iniciar sesión.</h2>";
} else {
    echo "<h3>⚠️ Token inválido o cuenta ya verificada.</h3>";
}

$stmt->close();
$conn->close();
?>
