<?php
session_start();
require_once "main.php"; // Tu conexión a la base de datos

// Verificar sesión
if (!isset($_SESSION["username"]) || !isset($_SESSION["id_usuario"])) {
  header("Location: registro.html");
  exit();
}

$id_usuario = $_SESSION["id_usuario"];
$username = htmlspecialchars($_SESSION["username"]);
$mensaje = "";

// Procesar formulario
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
  $action = $_POST['action'];

  if ($action === "cambiar_usuario") {
    $nuevo_usuario = trim($_POST['nuevo_usuario']);
    if (!empty($nuevo_usuario)) {
      $stmt = $conn->prepare("UPDATE Usuarios SET Username = ? WHERE id_Usuario = ?");
      $stmt->bind_param("si", $nuevo_usuario, $id_usuario);
      if ($stmt->execute()) {
        $_SESSION["username"] = $nuevo_usuario;
        $username = htmlspecialchars($nuevo_usuario);
        $mensaje = "Nombre de usuario actualizado correctamente.";
      } else {
        $mensaje = "Error al actualizar nombre de usuario.";
      }
    }
  }

  elseif ($action === "cambiar_clave") {
    $nueva_clave = trim($_POST['nueva_clave']);
    if (!empty($nueva_clave)) {
      // Al registrar
      $hash = password_hash($_POST['clave'], PASSWORD_DEFAULT);
      // Guardar $hash en la base de datos

      // Al login
      $stmt = $conn->prepare("SELECT contraseña FROM Usuarios WHERE Username=?");
      $stmt->bind_param("s", $usuario);
      $stmt->execute();
      $stmt->bind_result($hash_db);
      $stmt->fetch();
      if (password_verify($_POST['clave'], $hash_db)) {
        // Contraseña correcta
      } else {
        // Contraseña incorrecta
      }

    }
  }

  elseif ($action === "borrar_cuenta") {
    $stmt = $conn->prepare("DELETE FROM Usuarios WHERE id_Usuario = ?");
    $stmt->bind_param("i", $id_usuario);
    if ($stmt->execute()) {
      session_destroy();
      header("Location: registro_eliminado.php"); // página de confirmación
      exit();
    } else {
      $mensaje = "Error al borrar la cuenta.";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mi Perfil</title>
<style>
body { font-family: Arial; background: #f4f4f4; }
.perfil-container { max-width: 500px; margin: 50px auto; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 0 15px rgba(0,0,0,0.2); }
h1 { text-align: center; color: #2e7d32; }
form { margin: 20px 0; }
label { display: block; margin-bottom: 5px; font-weight: bold; }
input { width: 100%; padding: 10px; margin-bottom: 15px; border-radius: 6px; border: 1px solid #ccc; }
button { padding: 10px 20px; border: none; border-radius: 8px; background: #1976d2; color: #fff; font-weight: bold; cursor: pointer; transition: background 0.3s; }
button:hover { background: #0d47a1; }
.danger { background: #d32f2f; }
.danger:hover { background: #b71c1c; }
.mensaje { margin: 10px 0; color: green; font-weight: bold; text-align: center; }
</style>
</head>
<body>

<div class="perfil-container">
<h1>Bienvenido, <?php echo $username; ?> 🎉</h1>
<?php if(!empty($mensaje)) echo "<div class='mensaje'>{$mensaje}</div>"; ?>

<!-- Cambiar Usuario -->
<form method="POST">
<h3>Cambiar nombre de usuario</h3>
<label for="nuevo_usuario">Nuevo nombre de usuario:</label>
<input type="text" name="nuevo_usuario" id="nuevo_usuario" required>
<input type="hidden" name="action" value="cambiar_usuario">
<button type="submit">Actualizar</button>
</form>

<!-- Cambiar Contraseña -->
<form method="POST">
<h3>Cambiar contraseña</h3>
<label for="nueva_clave">Nueva contraseña:</label>
<input type="password" name="nueva_clave" id="nueva_clave" required>
<input type="hidden" name="action" value="cambiar_clave">
<button type="submit">Actualizar</button>
</form>

<!-- Borrar Cuenta -->
<form method="POST" onsubmit="return confirm('¿Estás seguro de borrar tu cuenta?');">
<h3>Borrar cuenta</h3>
<input type="hidden" name="action" value="borrar_cuenta">
<button type="submit" class="danger">Eliminar cuenta</button>
</form>
</div>

</body>
</html>

