<?php
$host = "127.0.0.1";
$user = "usuario";       // tu usuario MySQL
$password = "miclave123"; // tu contraseña MySQL
$db = "LegendAR";

// Conectar a MySQL
$conn = new mysqli($host, $user, $password, $db);

// Verificar conexión
if ($conn->connect_error) {
    die("❌ Error de conexión: " . $conn->connect_error);
}

// Obtener datos del formulario
$nombre = $_POST['nombre'];
$apellido = $_POST['apellido'];
$email = $_POST['email'];
$usuario = $_POST['usuario'];
$provincia = 1;
$ciudad = 1;
$contraseña = password_hash($_POST['contraseña'], PASSWORD_DEFAULT); // Encriptar contraseña

// Preparar e insertar
$stmt = $conn->prepare("INSERT INTO Usuarios (nombre, apellido, email, usuario, contraseña,provincia,ciudad) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $nombre,$apellido, $email, $usuario, $contraseña, $provincia,$ciudad);

if ($stmt->execute()) {
    echo "✅ Registro exitoso.";
} else {
    echo "❌ Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>