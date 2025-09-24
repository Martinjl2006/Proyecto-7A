<?php
$conexion = new mysqli("localhost", "root", "", "LegendAR");
if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}

$sql = "SELECT id_mitooleyenda, Titulo, Descripcion, foto FROM MitoLeyenda";
$resultado = $conexion->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mitos y Leyendas</title>
    <link rel="stylesheet" href="style.css">
</head>
<style>
    body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f4;
    padding: 20px;
}

h1 {
    text-align: center;
    color: #333;
}

.mitos-container {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    justify-content: center;
}

.mito {
    background-color: #fff;
    border: 1px solid #ccc;
    border-radius: 8px;
    width: 300px;
    padding: 15px;
    box-shadow: 2px 2px 8px rgba(0,0,0,0.1);
}

.mito img {
    max-width: 100%;
    height: auto;
    margin-top: 10px;
    border-radius: 4px;
}

.ver-mas {
    display: inline-block;
    margin-top: 10px;
    padding: 8px 12px;
    background-color: #0077cc;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    font-size: 0.9em;
}

.ver-mas:hover {
    background-color: #005fa3;
}

</style>
<body>
    <h1>Mitos y Leyendas Argentinas</h1>

    <div class="mitos-container">
        <?php while($mito = $resultado->fetch_assoc()): ?>
            <div class="mito">
                <h2><?= htmlspecialchars($mito['Titulo']) ?></h2>
                <p><?= htmlspecialchars($mito['Descripcion']) ?></p>
                <img src="img/<?= htmlspecialchars($mito['foto']) ?>" alt="<?= htmlspecialchars($mito['Titulo']) ?>">
                <a class="ver-mas" href="mitos.php?id=<?= $mito['id_mitooleyenda'] ?>">Ver más</a>
            </div>
        <?php endwhile; ?>
    </div>

</body>
</html>
