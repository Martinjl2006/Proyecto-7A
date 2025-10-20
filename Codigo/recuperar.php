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
    if (!empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
        $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
    } elseif (!empty($_SERVER['HTTP_HOST'])) {
        $host = $_SERVER['HTTP_HOST'];
    } else {
        $host = $_SERVER['SERVER_NAME'];
    }
    
    $protocol = 'http';
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        $protocol = 'https';
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $protocol = $_SERVER['HTTP_X_FORWARDED_PROTO'];
    } elseif (stripos($host, 'ngrok') !== false) {
        $protocol = 'https';
    }
    
    return $protocol . '://' . $host;
}

// --- Datos del formulario ---
$mail = trim(strtolower($_POST['email'] ?? ''));

if (empty($mail) || !filter_var($mail, FILTER_VALIDATE_EMAIL)) {
    die("<script>alert('❌ Por favor, ingresá un correo válido.'); window.history.back();</script>");
}

// --- Buscar al usuario ---
// Solo permitimos restablecer a cuentas verificadas
$stmt = $conn->prepare("SELECT id_usuario, nombre FROM Usuarios WHERE mail = ? AND verificado = 1");
$stmt->bind_param("s", $mail);
$stmt->execute();
$result = $stmt->get_result();

$userFound = $result->fetch_assoc();

if ($userFound) {
    $nombre = $userFound['nombre'];
    
    // --- Generar token de recuperación y expiración (1 hora) ---
    $token = bin2hex(random_bytes(32));
    $expira = date('Y-m-d H:i:s', time() + 3600); // 1 hora desde ahora

    // --- Guardar token en la BD (usando token_verificacion y token_reset_expira) ---
    // ¡Asegúrate de haber agregado la columna token_reset_expira! (Ver paso 3)
    $updateStmt = $conn->prepare("UPDATE Usuarios SET token_verificacion = ?, token_reset_expira = ? WHERE mail = ?");
    $updateStmt->bind_param("sss", $token, $expira, $mail);
    $updateStmt->execute();
    $updateStmt->close();

    // --- Configurar y enviar el correo de recuperación ---
    try {
        $mailObj = new PHPMailer(true);
        $mailObj->isSMTP();
        $mailObj->Host = 'smtp.gmail.com';
        $mailObj->SMTPAuth = true;
        $mailObj->Username = 'martinlage63@gmail.com';
        $mailObj->Password = 'izbosdhiarkbffkf'; // ¡Cuidado con exponer contraseñas!
        $mailObj->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mailObj->Port = 587;

        $mailObj->setFrom('martinlage63@gmail.com', 'LegendAR');
        $mailObj->addAddress($mail, $nombre);

        $mailObj->isHTML(true);
        $mailObj->Subject = 'Restablecé tu contraseña en LegendAR';

        $baseUrl = getBaseUrl();
        $link = $baseUrl . "/proyecto/Codigo/restablecer.php?token=" . urlencode($token);
        
        $mailObj->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f5f5f5;'>
                <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 10px 10px 0 0; text-align: center;'>
                    <h1 style='color: white; margin: 0;'>Restablecer Contraseña</h1>
                </div>
                <div style='background: white; padding: 30px; border-radius: 0 0 10px 10px;'>
                    <h2 style='color: #333;'>¡Hola, $nombre!</h2>
                    <p style='color: #666; line-height: 1.6;'>Recibimos una solicitud para restablecer la contraseña de tu cuenta en <strong>LegendAR</strong>.</p>
                    <p style='color: #666; line-height: 1.6;'>Si vos hiciste esta solicitud, hacé clic en el siguiente botón para crear una nueva contraseña. El enlace es válido por 1 hora.</p>
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='$link' style='background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 15px 40px; text-decoration: none; border-radius: 25px; font-weight: bold; display: inline-block;'>Restablecer contraseña</a>
                    </div>
                    <p style='color: #999; font-size: 14px; line-height: 1.6;'>Si el botón no funciona, copiá y pegá este enlace en tu navegador:</p>
                    <p style='color: #667eea; font-size: 14px; word-break: break-all;'>$link</p>
                    <hr style='border: none; border-top: 1px solid #eee; margin: 30px 0;'>
                    <p style='color: #999; font-size: 12px;'>Si no solicitaste un cambio de contraseña, ignorá este mensaje.</p>
                </div>
            </div>
        ";

        $mailObj->send();

    } catch (Exception $e) {
        // No mostramos el error al usuario, solo lo logueamos (idealmente)
        // error_log("PHPMailer error: {$mailObj->ErrorInfo}");
    }
}

$stmt->close();
$conn->close();

// --- Respuesta genérica ---
// Por seguridad (para evitar enumeración de usuarios), siempre mostramos
// el mismo mensaje, exista o no el correo.
echo "<script>alert('✅ Si existe una cuenta verificada con ese correo, te enviamos un enlace para restablecer tu contraseña. Revisá tu bandeja de entrada (y spam).'); window.location='inicio.html';</script>";
exit;
?>