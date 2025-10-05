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

$nombre = $_POST['nombre'];
$apellido = $_POST['apellido'];
$mail = $_POST['email'];                 
$username = $_POST['usuario'];
$contraseña = $_POST['clave']; //password_hash($_POST['clave'], PASSWORD_DEFAULT); CODIGO PARA ENCRIPTAR, TODAVIA NO LO USAMOS.
$provincia = 1; 
$ciudad = 1;   


// Preparar e insertar
$stmt = $conn->prepare("
    INSERT INTO Usuarios 
    (Nombre, apellido, Username, mail, contraseña, id_provincia, id_ciudad) 
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param(
    "sssssss", 
    $nombre, $apellido, $username, $mail, $contraseña, $provincia, $ciudad
);

$stmt->execute();

// Redirigir a otra página para evitar doble envío
header("Location: registro_exitoso.php");
exit();

$stmt->close();
$conn->close();
?>
