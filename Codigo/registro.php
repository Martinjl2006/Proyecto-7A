<?php
$host = "localhost";
$user = "root";
$password = "";
$db = "LegendAR";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Acceso inválido');
}

$conn = new mysqli($host, $user, $password, $db);
if ($conn->connect_error) {
    die("❌ Error de conexión: " . $conn->connect_error);
}

// Datos del formulario (sanitizar/normalizar)
$nombre     = trim($_POST['nombre'] ?? '');
$apellido   = trim($_POST['apellido'] ?? '');
$mail       = trim(strtolower($_POST['email'] ?? ''));
$username   = trim($_POST['usuario'] ?? '');
$contraseña = $_POST['clave'] ?? '';
$nombre_provincia= $_POST['provincia'];
$nombre_ciudad= $_POST['ciudad'];

$stmt = $conn->prepare("SELECT id_provincia FROM Provincias WHERE Nombre = ?");
$stmt->bind_param("s", $nombre_provincia);
$stmt->execute();
$result = $stmt->get_result();

if ($fila = $result->fetch_assoc()) {
    $id_provincia = (int) $fila['id_provincia'];
} else {
    die("❌ La provincia '$nombre_provincia' no existe en la tabla Provincias");
}

$stmt->close();

$stmt = $conn->prepare("SELECT id_ciudad FROM Ciudad WHERE Nombre = ?");
$stmt->bind_param("s", $nombre_ciudad);
$stmt->execute();
$result = $stmt->get_result();

if ($fila = $result->fetch_assoc()) {
    $id_ciudad = (int) $fila['id_ciudad'];
} else {
    die("❌ La provincia '$nombre_ciudad' no existe en la tabla Ciudades");
}

$stmt->close();

// Validaciones básicas
if (empty($nombre) || empty($apellido) || empty($mail) || empty($username) || empty($contraseña)) {
    echo "<script>alert('Por favor complete todos los campos obligatorios.'); window.history.back();</script>";
    exit;
}

// 1) Comprobar si mail o username ya existen
$check = $conn->prepare("SELECT id_usuario FROM Usuarios WHERE mail = ? OR Username = ?");
if (!$check) {
    die("Error en la preparación de la consulta: " . $conn->error);
}
$check->bind_param("ss", $mail, $username);
if (!$check->execute()) {
    die("Error al ejecutar la comprobación: " . $check->error);
}
$check->store_result(); // guarda el conjunto de resultados en el statement

if ($check->num_rows > 0) {
    // Ya existe mail o username
    echo "<script>alert('❌ El correo o el usuario ya están registrados'); window.history.back();</script>";
    $check->close();
    $conn->close();
    exit;
}
$check->close();


//$pass_to_store = password_hash($contraseña, PASSWORD_DEFAULT); codigo contraseña encriptada, hay que aplicar modificaciones al login antes de usar;

$stmt = $conn->prepare("
    INSERT INTO Usuarios (Nombre, apellido, Username, mail, contrasena, id_provincia, id_ciudad)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
if (!$stmt) {
    die("Error en la preparación del INSERT: " . $conn->error);
}

// 5 strings y 2 enteros -> "sssssii"
$stmt->bind_param("sssssii", $nombre, $apellido, $username, $mail, $contraseña, $id_provincia, $id_ciudad);

if ($stmt->execute()) {
    // Éxito -> redirigir
    header("Location: registro_exitoso.php");
    exit;
} else {
    // Falló el INSERT
    echo "<script>alert('⚠️ Error al registrar usuario: " . addslashes($stmt->error) . "');</script>";
}

$stmt->close();
$conn->close();
?>

