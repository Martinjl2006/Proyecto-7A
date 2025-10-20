<?php
$host = "localhost";
$user = "root";
$password = "";
$db = "LegendAR";

$conn = new mysqli($host, $user, $password, $db);
if ($conn->connect_error) {
    die("❌ Error de conexión: " . $conn->connect_error);
}

$verificado = false;
$mensaje = "";
$error = false;

if (!isset($_GET['token'])) {
    $error = true;
    $mensaje = "Token de verificación faltante.";
} else {
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

        $verificado = true;
        $mensaje = "Tu cuenta ha sido verificada correctamente. Ya podés iniciar sesión.";
    } else {
        $error = true;
        $mensaje = "Token inválido o cuenta ya verificada.";
    }

    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $verificado ? 'Cuenta Verificada' : 'Error de Verificación' ?> - LeyendAR</title>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Quicksand', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: moveBackground 20s linear infinite;
        }

        @keyframes moveBackground {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        .container {
            background: white;
            border-radius: 25px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 100%;
            padding: 50px 40px;
            text-align: center;
            position: relative;
            z-index: 1;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo {
            height: 60px;
            margin-bottom: 30px;
            opacity: 0.9;
            animation: fadeIn 0.8s ease-out 0.2s backwards;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.8); }
            to { opacity: 1; transform: scale(1); }
        }

        .icon-container {
            margin-bottom: 30px;
            animation: fadeIn 0.8s ease-out 0.3s backwards;
        }

        .main-icon {
            width: 100px;
            height: 100px;
            background: <?= $verificado ? 'linear-gradient(135deg, #4caf50, #45a049)' : 'linear-gradient(135deg, #ff6b6b, #ee5a24)' ?>;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 3.5rem;
            color: white;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            <?= $verificado ? 'animation: pulse 2s ease-in-out infinite;' : '' ?>
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .title {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 15px;
            animation: fadeIn 0.8s ease-out 0.4s backwards;
        }

        .message {
            font-size: 1.1rem;
            color: #666;
            line-height: 1.6;
            margin-bottom: 30px;
            animation: fadeIn 0.8s ease-out 0.5s backwards;
        }

        .success-box {
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            border-left: 4px solid #4caf50;
            border-radius: 12px;
            padding: 20px;
            margin: 25px 0;
            text-align: left;
            color: #2e7d32;
            animation: fadeIn 0.8s ease-out 0.6s backwards;
        }

        .success-box h3 {
            font-size: 1.1rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .success-box ul {
            margin-left: 30px;
            margin-top: 10px;
        }

        .success-box li {
            margin-bottom: 8px;
        }

        .error-box {
            background: linear-gradient(135deg, #ffebee, #ffcdd2);
            border-left: 4px solid #f44336;
            border-radius: 12px;
            padding: 20px;
            margin: 25px 0;
            text-align: left;
            color: #c62828;
            animation: fadeIn 0.8s ease-out 0.6s backwards;
        }

        .error-box h3 {
            font-size: 1.1rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn {
            display: inline-block;
            padding: 15px 40px;
            border-radius: 25px;
            border: none;
            font-family: 'Quicksand', sans-serif;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
            margin: 10px;
            animation: fadeIn 0.8s ease-out 0.7s backwards;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-secondary:hover {
            background: #f0f4ff;
            transform: translateY(-2px);
        }

        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            color: #999;
            font-size: 0.9rem;
            animation: fadeIn 0.8s ease-out 0.8s backwards;
        }

        @media (max-width: 768px) {
            .container {
                padding: 40px 25px;
            }

            .title {
                font-size: 1.6rem;
            }

            .message {
                font-size: 1rem;
            }

            .main-icon {
                width: 80px;
                height: 80px;
                font-size: 3rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="logo_logo_re_logo_sin_fondo_-removebg-preview.png" alt="LeyendAR Logo" class="logo">

        <div class="icon-container">
            <div class="main-icon">
                <?= $verificado ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-times-circle"></i>' ?>
            </div>
        </div>

        <h1 class="title">
            <?= $verificado ? '¡Cuenta Verificada!' : 'Error de Verificación' ?>
        </h1>

        <p class="message">
            <?= htmlspecialchars($mensaje) ?>
        </p>

        <?php if ($verificado): ?>
            <div class="success-box">
                <h3>
                    <i class="fas fa-check"></i>
                    ¡Todo listo!
                </h3>
                <p>Ahora podés:</p>
                <ul>
                    <li>Iniciar sesión con tu cuenta</li>
                    <li>Explorar mitos y leyendas argentinas</li>
                    <li>Crear y compartir tus propias historias</li>
                    <li>Validar contenido de otros usuarios</li>
                </ul>
            </div>

            <a href="inicio.html" class="btn btn-primary">
                <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
            </a>
        <?php else: ?>
            <div class="error-box">
                <h3>
                    <i class="fas fa-exclamation-triangle"></i>
                    ¿Qué puedo hacer?
                </h3>
                <p>
                    Si el enlace expiró o es inválido, podés solicitar un nuevo correo de verificación 
                    intentando iniciar sesión nuevamente.
                </p>
            </div>

            <a href="registro.html" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Registrarse de nuevo
            </a>
            <a href="inicio.html" class="btn btn-secondary">
                <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
            </a>
        <?php endif; ?>

        <div class="footer">
            <p>
                <i class="fas fa-shield-alt"></i>
                Tu seguridad es importante para nosotros
            </p>
            <p style="margin-top: 10px;">
                © 2025 LeyendAR - Mitos y Leyendas Argentinas
            </p>
        </div>
    </div>
</body>
</html>