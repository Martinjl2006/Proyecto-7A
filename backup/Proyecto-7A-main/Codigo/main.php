<?php
session_start();

$host = "127.0.0.1";
$user = "usuario";
$password = "miclave123";
$db = "LegendAR";


$conn = new mysqli($host, $user, $password, $db);


if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $pass = $_POST["password"];

  
    $sql = "SELECT * FROM Usuarios WHERE mail = ? AND contraseña = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $email, $pass);
    $stmt->execute();

    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();

        $_SESSION["id_usuario"] = $usuario["id_Usuario"];
        $_SESSION["username"] = $usuario["Username"];
        $_SESSION["nombre"] = $usuario["Nombre"];

        header("Location: dashboard.php");
        exit();
    } else {
        echo "<script>alert('Correo o contraseña incorrectos'); window.location.href='inicio.html';</script>";
    }
}

$conn->close();
?>
