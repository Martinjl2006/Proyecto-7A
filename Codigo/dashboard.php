<?php
session_start();

// Verificamos si hay una sesión iniciada
if (!isset($_SESSION["id_usuario"])) {
    header("Location: inicio.html");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Bienvenido a LeyendAR</title>
  <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Quicksand', sans-serif;
      background-color: #f4f1ed;
      margin: 0;
      padding: 40px;
      text-align: center;
    }

    h1 {
      color: #1d2e42;
    }

    .usuario-info {
      margin-top: 30px;
      font-size: 1.2rem;
    }

    .btn-cerrar {
      margin-top: 30px;
      padding: 12px 25px;
      background: linear-gradient(to right, #c04848, #e67e22);
      color: white;
      border: none;
      border-radius: 20px;
      font-weight: bold;
      cursor: pointer;
      text-decoration: none;
    }
  </style>
</head>
<body>

  <h1>¡Hola <?php echo $_SESSION["nombre"]; ?>! 👋</h1>

  <div class="usuario-info">
    <p>Nombre de usuario: <strong><?php echo $_SESSION["username"]; ?></strong></p>
    <p>ID de usuario: <?php echo $_SESSION["id_usuario"]; ?></p>
  </div>

  <a class="btn-cerrar" href="logout.php">Cerrar sesión</a>

</body>
</html>
