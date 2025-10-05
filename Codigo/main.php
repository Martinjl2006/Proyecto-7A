<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$host = "localhost";
$user = "root";
$password = "";
$db = "LegendAR";

$conn = new mysqli($host, $user, $password, $db);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Solo procesar login si viene desde la página de inicio
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["email"]) && isset($_POST["password"])) {
    $email = trim($_POST["email"]);
    $pass = trim($_POST["password"]);

    // Login sin hash
    $stmt = $conn->prepare("SELECT * FROM Usuarios WHERE mail=? AND contrasena=?");
    $stmt->bind_param("ss", $email, $pass);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $usuario = $resultado->fetch_assoc();
    $stmt->close();

if ($usuario) {
    $_SESSION["id_usuario"] = $usuario["id_usuario"];  // CORREGIDO
    $_SESSION["username"] = $usuario["Username"];
    $_SESSION["nombre"] = $usuario["Nombre"];

    header("Location: dashboard.php?login=success");
    exit();
} else {
    // Redirige al login con un parámetro de error
    header("Location: inicio.html?error=1");
    exit();
}

}

// No cerrar la conexión aquí ya que otros archivos la necesitan
// $conn->close();
?>
