<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$titulo = $_POST['titulo'];
$provincia = $_POST['provincia'];
$descripcion = $_POST['descripcion'];
$imagen = $_POST['imagen'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <div class="username"><?php echo $titulo; ?></div>
    <div class="username"><?php echo $provincia; ?></div>
    <div class="username"><?php echo $descripcion; ?></div>
    <div class="username"><?php echo $imagen; ?></div>
</body>
</html>