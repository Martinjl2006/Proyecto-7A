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

// --- Detectar la URL base automáticamente ---
function getBaseUrl() {
    // Primero verificar si hay un header X-Forwarded-Host (ngrok lo usa)
    if (!empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
        $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
    } elseif (!empty($_SERVER['HTTP_HOST'])) {
        $host = $_SERVER['HTTP_HOST'];
    } else {
        $host = $_SERVER['SERVER_NAME'];
    }
    
    // Detectar si es HTTPS
    $protocol = 'http';
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        $protocol = 'https';
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $protocol = $_SERVER['HTTP_X_FORWARDED_PROTO'];
    } elseif (stripos($host, 'ngrok') !== false) {
        // ngrok siempre usa HTTPS
        $protocol = 'https';
    }
    
    return $protocol . '://' . $host;
}

// --- Datos del formulario ---
$nombre     = trim($_POST['nombre'] ?? '');
$apellido   = trim($_POST['apellido'] ?? '');
$mail       = trim(strtolower($_POST['email'] ?? ''));
$username   = trim($_POST['usuario'] ?? '');
$contraseña = $_POST['clave'] ?? '';
$id_provincia  = $_POST['provincia'] ?? '';
$id_ciudad     = $_POST['ciudad'] ?? '';
$fecha_nacimiento = $_POST['fecha_nacimiento'] ?? '';
$foto = "default.png";

// --- Validar formato de correo ---
if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
    die("<script>alert('❌ El formato del correo no es válido.'); window.history.back();</script>");
}

// --- Validar edad mínima (6 años) ---
$fecha_actual = new DateTime();
$fecha_nac = new DateTime($fecha_nacimiento);
$edad = $fecha_actual->diff($fecha_nac)->y;

if ($edad < 6) {
    die("<script>alert('❌ Debes tener al menos 6 años para registrarte.'); window.history.back();</script>");
}

// --- Validar contraseña segura ---
if (strlen($contraseña) < 8) {
    die("<script>alert('❌ La contraseña debe tener al menos 8 caracteres.'); window.history.back();</script>");
}

if (!preg_match('/[A-Z]/', $contraseña)) {
    die("<script>alert('❌ La contraseña debe contener al menos una letra mayúscula.'); window.history.back();</script>");
}

if (!preg_match('/[a-z]/', $contraseña)) {
    die("<script>alert('❌ La contraseña debe contener al menos una letra minúscula.'); window.history.back();</script>");
}

if (!preg_match('/[0-9]/', $contraseña)) {
    die("<script>alert('❌ La contraseña debe contener al menos un número.'); window.history.back();</script>");
}

// --- Verificar si el email ya está registrado ---
$checkEmail = $conn->prepare("SELECT id_usuario FROM Usuarios WHERE mail = ?");
$checkEmail->bind_param("s", $mail);
$checkEmail->execute();
$checkEmail->store_result();

if ($checkEmail->num_rows > 0) {
    $checkEmail->close();
    $conn->close();
    die("<script>alert('❌ Error: Este correo electrónico ya está registrado.'); window.history.back();</script>");
}
$checkEmail->close();

// --- Verificar si el username ya está registrado ---
$checkUsername = $conn->prepare("SELECT id_usuario FROM Usuarios WHERE Username = ?");
$checkUsername->bind_param("s", $username);
$checkUsername->execute();
$checkUsername->store_result();

if ($checkUsername->num_rows > 0) {
    $checkUsername->close();
    $conn->close();
    die("<script>alert('❌ Error: Este nombre de usuario ya está en uso.'); window.history.back();</script>");
}
$checkUsername->close();

// --- Contraseña sin hashear ---
$contraseña_hash = $contraseña;

// --- Generar token de verificación ---
$token = bin2hex(random_bytes(16));

// --- Insertar usuario no verificado ---
$stmt = $conn->prepare("
    INSERT INTO Usuarios (Nombre, apellido, Username, mail, contrasena, foto, id_provincia, id_ciudad, verificado, token_verificacion, fecha_nacimiento)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$verificado = 0;
$stmt->bind_param("ssssssiisss", $nombre, $apellido, $username, $mail, $contraseña_hash, $foto, $id_provincia, $id_ciudad, $verificado, $token, $fecha_nacimiento);


if (!$stmt->execute()) {
    die("⚠️ Error al registrar usuario: " . $stmt->error);
}

// --- Configurar y enviar el correo de verificación ---
try {
    $mailObj = new PHPMailer(true);
    $mailObj->isSMTP();
    $mailObj->Host = 'smtp.gmail.com';
    $mailObj->SMTPAuth = true;

    $mailObj->Username = 'martinlage63@gmail.com';
    $mailObj->Password = 'izbosdhiarkbffkf';
    $mailObj->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mailObj->Port = 587;

    $mailObj->setFrom('martinlage63@gmail.com', 'LegendAR');
    $mailObj->addAddress($mail, "$nombre $apellido");

    $mailObj->isHTML(true);
    $mailObj->Subject = 'Verificá tu cuenta en LegendAR';

    // Obtener la URL base automáticamente (funciona con ngrok y localhost)
    $baseUrl = getBaseUrl();
    $link = $baseUrl . "/proyecto/Codigo/verificar.php?token=" . urlencode($token);
    
    $mailObj->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f5f5f5;'>
            <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 10px 10px 0 0; text-align: center;'>
                <h1 style='color: white; margin: 0;'>¡Bienvenido a LegendAR!</h1>
            </div>
            <div style='background: white; padding: 30px; border-radius: 0 0 10px 10px;'>
                <h2 style='color: #333;'>¡Hola, $nombre!</h2>
                <p style='color: #666; line-height: 1.6;'>Gracias por registrarte en <strong>LegendAR</strong>, la plataforma de mitos y leyendas argentinas.</p>
                <p style='color: #666; line-height: 1.6;'>Para completar tu registro, por favor verificá tu correo electrónico haciendo clic en el siguiente botón:</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='$link' style='background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 15px 40px; text-decoration: none; border-radius: 25px; font-weight: bold; display: inline-block;'>Verificar mi cuenta</a>
                </div>
                <p style='color: #999; font-size: 14px; line-height: 1.6;'>Si el botón no funciona, copiá y pegá este enlace en tu navegador:</p>
                <p style='color: #667eea; font-size: 14px; word-break: break-all;'>$link</p>
                <hr style='border: none; border-top: 1px solid #eee; margin: 30px 0;'>
                <p style='color: #999; font-size: 12px;'>Si no creaste esta cuenta, ignorá este mensaje.</p>
            </div>
        </div>
    ";

    $mailObj->send();
    echo "<script>alert('✅ Registro exitoso. Revisá tu correo para verificar tu cuenta.'); window.location='inicio.html';</script>";

} catch (Exception $e) {
    echo "❌ No se pudo enviar el correo: {$mailObj->ErrorInfo}";
}

$stmt->close();
$conn->close();
?>