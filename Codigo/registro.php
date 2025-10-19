<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'C:/xampp/htdocs/proyecto/PHPMailer-master/src/Exception.php';
require 'C:/xampp/htdocs/proyecto/PHPMailer-master/src/PHPMailer.php';
require 'C:/xampp/htdocs/proyecto/PHPMailer-master/src/SMTP.php';

$host = "localhost";
$user = "root";
$password = "";
$db = "LegendAR";

$conn = new mysqli($host, $user, $password, $db);
if ($conn->connect_error) {
    die("❌ Error de conexión: " . $conn->connect_error);
}

// --- Datos del formulario ---
$nombre     = trim($_POST['nombre'] ?? '');
$apellido   = trim($_POST['apellido'] ?? '');
$mail       = trim(strtolower($_POST['email'] ?? ''));
$username   = trim($_POST['usuario'] ?? '');
$contraseña = $_POST['clave'] ?? '';
$provincia  = $_POST['provincia'] ?? '';
$ciudad     = $_POST['ciudad'] ?? '';

// --- Validar formato de correo ---
if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
    die("❌ El formato del correo no es válido.");
}

// --- Generar token de verificación ---
$token = bin2hex(random_bytes(16));

// --- Insertar usuario no verificado ---
$stmt = $conn->prepare("
    INSERT INTO Usuarios (Nombre, apellido, Username, mail, contrasena, id_provincia, id_ciudad, verificado, token_verificacion)
    VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?)
");
$id_provincia = 1; // ajustá según tu lógica
$id_ciudad = 1;
$stmt->bind_param("sssssiss", $nombre, $apellido, $username, $mail, $contraseña, $id_provincia, $id_ciudad, $token);

if (!$stmt->execute()) {
    die("⚠️ Error al registrar usuario: " . $stmt->error);
}

// --- Configurar y enviar el correo de verificación ---
try {
    $mailObj = new PHPMailer(true);
    $mailObj->isSMTP();
    $mailObj->Host = 'smtp.gmail.com';
    $mailObj->SMTPAuth = true;

    // ⚙️ Cambiá por tu cuenta Gmail
    $mailObj->Username = 'martinlage63@gmail.com';
    $mailObj->Password = 'izbosdhiarkbffkf';
    $mailObj->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mailObj->Port = 587;

    $mailObj->setFrom('martinlage63@gmail.co', 'LegendAR');
    $mailObj->addAddress($mail, "$nombre $apellido");

    $mailObj->isHTML(true);
    $mailObj->Subject = 'Verificá tu cuenta en LegendAR';

    $link = "http://localhost/proyecto/Codigo/verificar.php?token=" . urlencode($token);
    $mailObj->Body = "
        <h2>¡Hola, $nombre!</h2>
        <p>Gracias por registrarte en <b>LegendAR</b>.</p>
        <p>Por favor verificá tu correo haciendo clic en el siguiente enlace:</p>
        <p><a href='$link'>$link</a></p>
        <p>Si no creaste esta cuenta, ignorá este mensaje.</p>
    ";

    $mailObj->send();
    echo "<script>alert('✅ Registro exitoso. Revisá tu correo para verificar tu cuenta.'); window.location='login.html';</script>";

} catch (Exception $e) {
    echo "❌ No se pudo enviar el correo: {$mailObj->ErrorInfo}";
}

$stmt->close();
$conn->close();
?>
