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
        $stmt = $conn->prepare("DELETE FROM Usuarios WHERE id_Usuario=?");
        $stmt->bind_param("i", $id_usuario);
        if ($stmt->execute()) {
            session_destroy();
            header("Location: registro_eliminado.php");
            exit();
        } else {
            $mensaje = "Error al borrar la cuenta.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Perfil</title>
    <link rel="stylesheet" href="styles.css"> <!-- Opcional: puedes poner los estilos en un archivo externo -->
    <style>
        /* perfil.css */

/* Reseteo básico */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
    padding: 5px;
    border-radius: 50%;
    transition: background 0.3s;
}

.close-btn:hover {
    background: #f0f0f0;
}

/* Imagen de perfil y nombre */
.foto_perfil {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    margin-bottom: 15px;
    border: 3px solid #ddd;
}

.modal-content > div:nth-child(2) {
    text-align: center;
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
    <div class="modal-content" style="max-width: 600px; margin: 40px auto;">
        <div class="modal-header">
            <h2 class="modal-title">Mi Perfil 🎉</h2>
            <a href="dashboard.php" class="close-btn">&larr;</a>
        </div>

        <div style="text-align:center; margin-bottom: 20px;">
            <img src="usuarios/<?= htmlspecialchars($fotoActual) ?>" alt="Foto de perfil" class="foto_perfil">
            <h3><?= $username ?></h3>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="mensaje <?= (strpos($mensaje, 'Error') !== false) ? 'error' : ''; ?>">
                <?= $mensaje ?>
            </div>
        <?php endif; ?>

        <!-- Cambiar usuario -->
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

        <!-- Cambiar foto -->
        <div class="profile-form">
            <h3>Actualizar Foto de Perfil</h3>
            <button type="button" onclick="location.href='foto_perfil.php'" class="form-btn">Cambiar Foto</button>
        </div>

        <!-- Eliminar cuenta -->
        <form method="POST" class="profile-form" onsubmit="return confirmDelete()">
            <h3>Zona de peligro</h3>
            <p style="color: #666; margin-bottom: 20px;">Esta acción no se puede deshacer.</p>
            <input type="hidden" name="action" value="borrar_cuenta">
            <button type="submit" class="form-btn danger">Eliminar cuenta permanentemente</button>
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
            return confirm('⚠️ ¿Estás seguro de que deseas eliminar tu cuenta? Esta acción no se puede deshacer.');
        }
    </script>
</body>
</html>
