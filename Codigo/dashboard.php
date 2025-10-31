<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once "main.php"; // incluye la conexión ya establecida

// Verificar sesión
if (!isset($_SESSION["username"]) || !isset($_SESSION["id_usuario"])) {
    header("Location: registro.html");
    exit();
}

$id_usuario = $_SESSION["id_usuario"];
$username = htmlspecialchars($_SESSION["username"]);

$consultafoto = $conn->query("SELECT foto FROM Usuarios WHERE id_usuario = " .$id_usuario);
$foto = $consultafoto->fetch_assoc();
$fotoActual = $foto['foto'];


// mito aleatorio
$result = $conn->query("SELECT COUNT(*) AS total FROM MitoLeyenda");
$row = $result->fetch_assoc();
$total = (int) $row['total'];

// Elegir id aleatorio (entre 1 y $total)
$mito_id = rand(1, $total);

// Consulta del mito (incluyendo el id para poder usarlo en el enlace)
$nombre = $conn->query("SELECT id_mitooleyenda, Titulo, Descripcion,textobreve,imagen 
                        FROM MitoLeyenda WHERE Verificado = 1
                        ORDER BY RAND() 
                        LIMIT 1");
$mito_actual = $nombre->fetch_assoc();


// Procesar formulario
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];

    // Cambiar usuario
    if ($action === "cambiar_usuario") {
        $nuevo_usuario = trim($_POST['nuevo_usuario']);
        if (!empty($nuevo_usuario)) {
            $stmt = $conn->prepare("UPDATE Usuarios SET Username=? WHERE id_Usuario=?");
            $stmt->bind_param("si", $nuevo_usuario, $id_usuario);
            if ($stmt->execute()) {
                $_SESSION["username"] = $nuevo_usuario;
                $username = htmlspecialchars($nuevo_usuario);
                $mensaje = "Nombre de usuario actualizado correctamente.";
            } else {
                $mensaje = "Error al actualizar nombre de usuario.";
            }
            $stmt->close();
        }
    }

    // Cambiar contraseña
    elseif ($action === "cambiar_clave") {
        $clave_actual = trim($_POST['clave_actual']);
        $nueva_clave = trim($_POST['nueva_clave']);
        
        if (!empty($clave_actual) && !empty($nueva_clave)) {
            // Verificar contraseña actual
            $stmt = $conn->prepare("SELECT contrasena FROM Usuarios WHERE id_Usuario=?");
            $stmt->bind_param("i", $id_usuario);
            $stmt->execute();
            $resultado = $stmt->get_result();
            $usuario_actual = $resultado->fetch_assoc();
            $stmt->close();
            
            if ($usuario_actual && $usuario_actual['contrasena'] === $clave_actual) {
                // Contraseña actual correcta, actualizar con la nueva
                $stmt = $conn->prepare("UPDATE Usuarios SET contrasena=? WHERE id_Usuario=?");
                $stmt->bind_param("si", $nueva_clave, $id_usuario);
                if ($stmt->execute()) {
                    $mensaje = "Contraseña actualizada correctamente.";
                } else {
                    $mensaje = "Error al actualizar la contraseña.";
                }
                $stmt->close();
            } else {
                $mensaje = "La contraseña actual es incorrecta.";
            }
        } else {
            $mensaje = "Todos los campos son obligatorios.";
        }
    }

    // Borrar cuenta
    elseif ($action === "borrar_cuenta") {
        // Iniciar transacción para asegurar que todo se borre correctamente
        $conn->begin_transaction();
        
        try {
            // 1. Obtener todas las imágenes de los mitos del usuario para borrarlas del servidor
            $stmt_imagenes = $conn->prepare("SELECT imagen FROM MitoLeyenda WHERE id_usuario = ? AND imagen IS NOT NULL");
            $stmt_imagenes->bind_param("i", $id_usuario);
            $stmt_imagenes->execute();
            $resultado_imagenes = $stmt_imagenes->get_result();
            
            $imagenes_a_borrar = [];
            while ($row_img = $resultado_imagenes->fetch_assoc()) {
                if (!empty($row_img['imagen'])) {
                    $imagenes_a_borrar[] = $row_img['imagen'];
                }
            }
            $stmt_imagenes->close();
            
            // 2. Eliminar favoritos relacionados con los mitos del usuario
            $stmt_fav = $conn->prepare("DELETE FROM Favoritos WHERE id_mitooleyenda IN (SELECT id_mitooleyenda FROM MitoLeyenda WHERE id_usuario = ?)");
            $stmt_fav->bind_param("i", $id_usuario);
            $stmt_fav->execute();
            $stmt_fav->close();
            
            // 3. Eliminar todos los mitos del usuario
            $stmt_mitos = $conn->prepare("DELETE FROM MitoLeyenda WHERE id_usuario = ?");
            $stmt_mitos->bind_param("i", $id_usuario);
            $stmt_mitos->execute();
            $stmt_mitos->close();
            
            
            // 5. Eliminar el usuario
            $stmt_usuario = $conn->prepare("DELETE FROM Usuarios WHERE id_Usuario = ?");
            $stmt_usuario->bind_param("i", $id_usuario);
            $stmt_usuario->execute();
            $stmt_usuario->close();
            
            // Confirmar transacción
            $conn->commit();
            
            // 6. Borrar las imágenes de los mitos del servidor
            foreach ($imagenes_a_borrar as $imagen) {
                $ruta_imagen = "mitos/" . $imagen;
                if (file_exists($ruta_imagen) && $imagen != "default.png") {
                    unlink($ruta_imagen);
                }
            }
            
            // Destruir sesión y redirigir
            session_destroy();
            header("Location: registro_eliminado.php");
            exit();
            
        } catch (Exception $e) {
            // Si hay algún error, revertir todos los cambios
            $conn->rollback();
            $mensaje = "Error al borrar la cuenta: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LegendAR - Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        /* Header */
        .header {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            cursor: pointer;
            transition: opacity 0.3s;
        }

        .user-info:hover {
            opacity: 0.8;
        }

        .profile-pic {
            width: 50px;
            height: 50px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: white;
            font-weight: bold;
            overflow: hidden;
        }

        .username {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }

        .nav-menu {
            display: flex;
            gap: 30px;
            align-items: center;
        }

        .nav-item {
            color: #666;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
            cursor: pointer;
        }

        .nav-item:hover {
            color: #333;
        }

        .nav-item.logout {
            color: #d32f2f;
        }

        /* Main Content */
        .main-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 30px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }

        /* Map Section */
        .map-section {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .map-container {
            background: #999;
            border-radius: 20px;
            height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }

        .map-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="%23ffffff" stroke-width="0.5" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
        }

        .explore-btn {
            background: linear-gradient(45deg, #ff6b35, #f7931e);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 50px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
            align-self: center;
            margin-top: 20px;
        }

        .explore-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 107, 53, 0.4);
        }

        /* Features Grid */
        .features-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }

        .feature-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .feature-icon {
            width: 40px;
            height: 40px;
            margin-bottom: 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .feature-card:nth-child(1) .feature-icon {
            background: linear-gradient(45deg, #ff6b6b, #ee5a24);
        }

        .feature-card:nth-child(2) .feature-icon {
            background: linear-gradient(45deg, #74b9ff, #0984e3);
        }

        .feature-card:nth-child(3) .feature-icon {
            background: linear-gradient(45deg, #fd79a8, #e84393);
        }

        .feature-card:nth-child(4) .feature-icon {
            background: linear-gradient(45deg, #00b894, #00a085);
        }

        .feature-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .feature-description {
            font-size: 14px;
            color: #666;
            line-height: 1.4;
        }

        /* Story Section */
        .story-section {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .story-preview {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }

        .story-preview::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            border-radius: 50%;
            transform: translate(30px, -30px);
            opacity: 0.1;
        }

        .story-author {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .author-pic {
            width: 50px;
            height: 50px;
            background: #ddd;
            border-radius: 50%;
            overflow: hidden;
            align-items: center;

        }

        .author-name {
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }

        .story-content {
            font-size: 16px;
            color: #555;
            line-height: 1.6;
            margin-bottom: 25px;
        }

        .read-more-btn {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .read-more-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        /* Create Section */
        .create-section {
            background: white;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .create-title {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 25px;
            line-height: 1.2;
        }

        .create-btn {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 18px 50px;
            border-radius: 50px;
            font-size: 20px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .create-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        /* Botón de Ayuda Flotante */
        .help-button {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
            transition: all 0.3s ease;
            z-index: 999;
            text-decoration: none;
        }

        .help-button:hover {
            transform: scale(1.1) translateY(-2px);
            box-shadow: 0 8px 30px rgba(102, 126, 234, 0.6);
        }

        .help-button:active {
            transform: scale(0.95);
        }

        /* Tooltip para el botón de ayuda */
        .help-button::before {
            content: 'Manual de Usuario';
            position: absolute;
            right: 70px;
            background: #333;
            color: white;
            padding: 8px 15px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            pointer-events: none;
        }

        .help-button::after {
            content: '';
            position: absolute;
            right: 60px;
            border: 8px solid transparent;
            border-left-color: #333;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .help-button:hover::before,
        .help-button:hover::after {
            opacity: 1;
            visibility: visible;
        }

        /* Modal de Perfil */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            backdrop-filter: blur(5px);
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            border-radius: 20px;
            padding: 40px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .modal-title {
            font-size: 24px;
            font-weight: 700;
            color: #333;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
            padding: 5px;
            border-radius: 50%;
            transition: background 0.3s;
        }

        .close-btn:hover {
            background: #f0f0f0;
        }

        .profile-form {
            margin-bottom: 30px;
            padding: 25px;
            background: #f8f9fa;
            border-radius: 15px;
        }

        .profile-form h3 {
            color: #333;
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }

        .form-input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-input:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-btn {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
            width: 100%;
        }

        .form-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .form-btn.danger {
            background: linear-gradient(45deg, #ff6b6b, #ee5a24);
        }

        .form-btn.danger:hover {
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
        }

        .mensaje {
            background: #4caf50;
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
        }

        .mensaje.error {
            background: #f44336;
        }

        .warning-box {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            color: #856404;
        }

        .warning-box strong {
            display: block;
            margin-bottom: 8px;
            font-size: 16px;
        }

        .warning-box ul {
            margin-left: 20px;
            margin-top: 8px;
        }

        .warning-box li {
            margin-bottom: 5px;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .main-content {
                grid-template-columns: 1fr;
                gap: 30px;
            }
        }

        @media (max-width: 768px) {
            .header {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
            }

            .nav-menu {
                gap: 20px;
            }

            .main-content {
                padding: 20px;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .modal-content {
                padding: 30px 20px;
            }

            .help-button {
                width: 50px;
                height: 50px;
                font-size: 24px;
                bottom: 20px;
                right: 20px;
            }

            .help-button::before {
                display: none;
            }

            .help-button::after {
                display: none;
            }
        }

        .foto_perfil{
            width: 100px;
            height: 100px;
            border: 0px;
            border-radius: 50%;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="user-info" onclick="openProfileModal()">
            <div class="profile-pic">
                <img src="usuarios/<?= htmlspecialchars($fotoActual) ?>" class="foto_perfil">
            </div>
            <div class="username"><?php echo $username; ?></div>
        </div>
        <nav class="nav-menu">
            <span class="nav-item" onclick="location.href='../index.php'">volver al inicio</span>
            <span class="nav-item logout" onclick="logout()">Salir</span>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Left Column -->
        <section class="map-section">
            <!-- Map -->
            <div class="map-container">
                <img src="logo.jpg" alt="" srcset="">
            </div>
            <button class="explore-btn" onclick="location.href='mapa.php'">Explorar mapa</button>

            <!-- Features Grid -->
            <div class="features-grid">
                <div class="feature-card" onclick="location.href='ranking.php'">
                    <div class="feature-icon">🏆</div>
                    <div class="feature-title">Ranking</div>
                    <div class="feature-description">Explora los mistos mas populares ordenados por nuestros usuarios</div>
                </div>

                <div class="feature-card" onclick="location.href='mis_mitos.php'">
                    <div class="feature-icon">📚</div>
                    <div class="feature-title">Mis mitos</div>
                    <div class="feature-description">Edita y gestiona tus mitos</div>
                </div>

                <div class="feature-card" onclick="location.href='lista_mitos.php'">
                    <div class="feature-icon">🦄</div>
                    <div class="feature-title">Criaturas y Mitos</div>
                    <div class="feature-description">Historias del Pombero, la Llorona, el Lobizón y muchos más</div>
                </div>

                <div class="feature-card" onclick="location.href='validar_mitos.php'">
                    <div class="feature-icon">🔍</div>
                    <div class="feature-title">Validacion</div>
                    <div class="feature-description">Ayudanos a validar mitos publicados por usuarios como tu</div>
                </div>
            </div>
        </section>

        <!-- Right Column -->
        <section class="story-section">
            <!-- Story Preview -->
            <div class="story-preview">
                <div class="story-author">
                    <div class="author-pic">
                        <img src="mitos/<?php echo htmlspecialchars($mito_actual['imagen']); ?>" alt="Imagen del mito" width="50" class="imagen_mito">
                    </div>
                    <div class="author-name"> <?php echo $mito_actual['Titulo']; ?></div>
                </div>
                <div class="story-content">
                    <?php echo $mito_actual['textobreve']; ?>
                </div>
                <button 
                    class="read-more-btn" 
                    onclick="location.href='mitos.php?id=<?php echo $mito_actual['id_mitooleyenda']; ?>'">
                    Leer más
                </button>
            </div>

            <!-- Create Section -->
            <div class="create-section">
                <h2 class="create-title">¡Cuéntanos alguna historia popular de tu provincia!</h2>
                <button class="create-btn" onclick="location.href='crearmito.php'">Crear</button>
            </div>
        </section>
    </main>

    <!-- Botón de Ayuda Flotante -->
    <a href="MANUAL_DE_USUARIO_LeyendAR.pdf" download="MANUAL_DE_USUARIO_LeyendAR.pdf" class="help-button" title="Descargar Manual de Usuario">
        ❔
    </a>

    <!-- Modal de Perfil -->
    <div class="modal-overlay" id="profileModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Mi Perfil 🎉</h2>
                <button class="close-btn" onclick="closeProfileModal()">&times;</button>
            </div>

            <?php if(!empty($mensaje)): ?>
                <div class="mensaje <?php echo (strpos($mensaje, 'Error') !== false) ? 'error' : ''; ?>">
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>

            <!-- Cambiar nombre de usuario -->
            <form method="POST" class="profile-form" onsubmit="return validateUsername()">
                <h3>Cambiar nombre de usuario</h3>
                <div class="form-group">
                    <label for="nuevo_usuario" class="form-label">Nuevo nombre de usuario:</label>
                    <input type="text" name="nuevo_usuario" id="nuevo_usuario" class="form-input" required>
                </div>
                <input type="hidden" name="action" value="cambiar_usuario">
                <button type="submit" class="form-btn">Actualizar Usuario</button>
            </form>

            <!-- Cambiar contraseña -->
            <form method="POST" class="profile-form" onsubmit="return validatePassword()">
                <h3>Cambiar contraseña</h3>
                <div class="form-group">
                    <label for="clave_actual" class="form-label">Contraseña actual:</label>
                    <input type="password" name="clave_actual" id="clave_actual" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="nueva_clave" class="form-label">Nueva contraseña:</label>
                    <input type="password" name="nueva_clave" id="nueva_clave" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="confirmar_clave" class="form-label">Confirmar nueva contraseña:</label>
                    <input type="password" name="confirmar_clave" id="confirmar_clave" class="form-input" required>
                </div>
                <input type="hidden" name="action" value="cambiar_clave">
                <button type="submit" class="form-btn">Actualizar Contraseña</button>
            </form>

            <div class="profile-form"> 
                <button type="button" onclick="location.href='foto_perfil.php'" class="form-btn">cambiar foto de perfil</button>
            </div>

            <!-- Borrar cuenta -->
            <form method="POST" class="profile-form" onsubmit="return confirmDelete()">
                <h3>Zona de peligro</h3>
                <div class="warning-box">
                    <strong>⚠️ Al eliminar tu cuenta se borrará:</strong>
                    <ul>
                        <li>Tu perfil y datos personales</li>
                        <li>Todos tus mitos publicados</li>
                        <li>Todas las imágenes asociadas</li>
                        <li>Tus favoritos y validaciones</li>
                    </ul>
                    <strong style="margin-top: 10px;">Esta acción no se puede deshacer.</strong>
                </div>
                <input type="hidden" name="action" value="borrar_cuenta">
                <button type="submit" class="form-btn danger">Eliminar cuenta permanentemente</button>
            </form>
        </div>
    </div>

    <script>
        // Funciones del modal
        function openProfileModal() {
            document.getElementById('profileModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeProfileModal() {
            document.getElementById('profileModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Cerrar modal al hacer clic fuera
        document.getElementById('profileModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeProfileModal();
            }
        });

        // Validaciones
        function validateUsername() {
            const username = document.getElementById('nuevo_usuario').value.trim();
            if (username.length < 3) {
                alert('El nombre de usuario debe tener al menos 3 caracteres');
                return false;
            }
            return true;
        }

        function validatePassword() {
            const currentPassword = document.getElementById('clave_actual').value;
            const newPassword = document.getElementById('nueva_clave').value;
            const confirmPassword = document.getElementById('confirmar_clave').value;
            
            if (currentPassword.length === 0) {
                alert('Debes ingresar tu contraseña actual');
                return false;
            }
            
            if (newPassword.length < 6) {
                alert('La nueva contraseña debe tener al menos 6 caracteres');
                return false;
            }
            
            if (newPassword !== confirmPassword) {
                alert('Las contraseñas no coinciden');
                return false;
            }
            
            if (currentPassword === newPassword) {
                alert('La nueva contraseña debe ser diferente a la actual');
                return false;
            }
            
            return true;
        }

        function confirmDelete() {
            const mensaje = '⚠️ ¿ESTÁS ABSOLUTAMENTE SEGURO?\n\n' +
                          'Al eliminar tu cuenta se borrará permanentemente:\n' +
                          '• Tu perfil completo\n' +
                          '• Todos tus mitos publicados\n' +
                          '• Todas las imágenes\n' +
                          '• Tus favoritos y validaciones\n\n' +
                          'ESTA ACCIÓN NO SE PUEDE DESHACER.\n\n' +
                          '¿Confirmas que deseas eliminar tu cuenta?';
            
            return confirm(mensaje);
        }

        function logout() {
            if (confirm('¿Estás seguro de que quieres cerrar sesión?')) {
                window.location.href = 'logout.php';
            }
        }

        // Mostrar modal si hay mensaje (después de una actualización)
        <?php if(!empty($mensaje)): ?>
        window.addEventListener('load', function() {
            openProfileModal();
        });
        <?php endif; ?>
    </script>
</body>
</html>