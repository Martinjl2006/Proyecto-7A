<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once "main.php";

if (!isset($_SESSION["username"]) || !isset($_SESSION["id_usuario"])) {
    header("Location: registro.html");
    exit();
}

$id_usuario = $_SESSION["id_usuario"];
$username = htmlspecialchars($_SESSION["username"]);

$consultafoto = $conn->query("SELECT foto FROM Usuarios WHERE id_usuario = " .$id_usuario);
$foto = $consultafoto->fetch_assoc();
$fotoActual = $foto['foto'];

// Mensaje por acción
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];

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

    elseif ($action === "cambiar_clave") {
        $clave_actual = trim($_POST['clave_actual']);
        $nueva_clave = trim($_POST['nueva_clave']);

        if (!empty($clave_actual) && !empty($nueva_clave)) {
            $stmt = $conn->prepare("SELECT contrasena FROM Usuarios WHERE id_Usuario=?");
            $stmt->bind_param("i", $id_usuario);
            $stmt->execute();
            $resultado = $stmt->get_result();
            $usuario_actual = $resultado->fetch_assoc();
            $stmt->close();

            if ($usuario_actual && $usuario_actual['contrasena'] === $clave_actual) {
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
    <title>Mi Perfil - LeyendAR</title>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Reseteo básico */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Quicksand', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        /* Contenedor del perfil */
        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 600px;
            margin: 40px auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        }

        /* Cabecera */
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
            text-decoration: none;
            font-size: 24px;
            color: #666;
            padding: 5px 10px;
            border-radius: 50%;
            transition: background 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .close-btn:hover {
            background: #f0f0f0;
        }

        /* Imagen de perfil y nombre */
        .profile-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .foto_perfil {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
            border: 4px solid #667eea;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .profile-header h3 {
            font-size: 1.8rem;
            color: #333;
            font-weight: 700;
        }

        /* Mensaje de éxito / error */
        .mensaje {
            background: #4caf50;
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .mensaje.error {
            background: #f44336;
        }

        /* Formularios */
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
            display: flex;
            align-items: center;
            gap: 10px;
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
            font-family: 'Quicksand', sans-serif;
            transition: border-color 0.3s;
        }

        .form-input:focus {
            outline: none;
            border-color: #667eea;
        }

        /* Botones */
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
            font-family: 'Quicksand', sans-serif;
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

        /* Adaptación responsiva */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            .modal-content {
                padding: 30px 20px;
                margin: 20px auto;
            }
            .form-btn {
                font-size: 14px;
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Mi Perfil 🎉</h2>
            <a href="dashboard.php" class="close-btn">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>

        <div class="profile-header">
            <img src="usuarios/<?= htmlspecialchars($fotoActual) ?>" alt="Foto de perfil" class="foto_perfil">
            <h3><?= $username ?></h3>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="mensaje <?= (strpos($mensaje, 'Error') !== false) ? 'error' : ''; ?>">
                <i class="fas <?= (strpos($mensaje, 'Error') !== false) ? 'fa-exclamation-circle' : 'fa-check-circle' ?>"></i>
                <?= $mensaje ?>
            </div>
        <?php endif; ?>

        <!-- Cambiar usuario -->
        <form method="POST" class="profile-form" onsubmit="return validateUsername()">
            <h3>
                <i class="fas fa-user-edit"></i>
                Cambiar nombre de usuario
            </h3>
            <div class="form-group">
                <label for="nuevo_usuario" class="form-label">Nuevo nombre de usuario:</label>
                <input type="text" name="nuevo_usuario" id="nuevo_usuario" class="form-input" required placeholder="Ingresa tu nuevo nombre de usuario">
            </div>
            <input type="hidden" name="action" value="cambiar_usuario">
            <button type="submit" class="form-btn">
                <i class="fas fa-save"></i> Actualizar Usuario
            </button>
        </form>

        <!-- Cambiar contraseña -->
        <form method="POST" class="profile-form" onsubmit="return validatePassword()">
            <h3>
                <i class="fas fa-lock"></i>
                Cambiar contraseña
            </h3>
            <div class="form-group">
                <label for="clave_actual" class="form-label">Contraseña actual:</label>
                <input type="password" name="clave_actual" id="clave_actual" class="form-input" required placeholder="Tu contraseña actual">
            </div>
            <div class="form-group">
                <label for="nueva_clave" class="form-label">Nueva contraseña:</label>
                <input type="password" name="nueva_clave" id="nueva_clave" class="form-input" required placeholder="Mínimo 6 caracteres">
            </div>
            <div class="form-group">
                <label for="confirmar_clave" class="form-label">Confirmar nueva contraseña:</label>
                <input type="password" name="confirmar_clave" id="confirmar_clave" class="form-input" required placeholder="Repite la nueva contraseña">
            </div>
            <input type="hidden" name="action" value="cambiar_clave">
            <button type="submit" class="form-btn">
                <i class="fas fa-save"></i> Actualizar Contraseña
            </button>
        </form>

        <!-- Cambiar foto -->
        <div class="profile-form">
            <h3>
                <i class="fas fa-camera"></i>
                Actualizar Foto de Perfil
            </h3>
            <button type="button" onclick="location.href='foto_perfil.php'" class="form-btn">
                <i class="fas fa-upload"></i> Cambiar Foto
            </button>
        </div>

        <!-- Eliminar cuenta -->
        <form method="POST" class="profile-form" onsubmit="return confirmDelete()">
            <h3>
                <i class="fas fa-exclamation-triangle"></i>
                Zona de peligro
            </h3>
            <div class="warning-box">
                <strong>⚠️ Al eliminar tu cuenta se borrará permanentemente:</strong>
                <ul>
                    <li>Tu perfil y datos personales</li>
                    <li>Todos tus mitos publicados</li>
                    <li>Todas las imágenes asociadas</li>
                    <li>Tus favoritos y validaciones</li>
                </ul>
                <strong style="margin-top: 10px;">Esta acción NO se puede deshacer.</strong>
            </div>
            <input type="hidden" name="action" value="borrar_cuenta">
            <button type="submit" class="form-btn danger">
                <i class="fas fa-trash-alt"></i> Eliminar cuenta permanentemente
            </button>
        </form>
    </div>

    <script>
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
    </script>
</body>
</html>