<?php
session_start();
require_once "main.php"; // Conexión a la BD

if (!isset($_SESSION['id_usuario'])) {
    header("Location: registro.html");
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

// Eliminar mito si se solicitó
if (isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
    $id_mito = intval($_GET['eliminar']);

    // Verificar que el mito pertenece al usuario
    $stmt = $conn->prepare("DELETE FROM MitoLeyenda WHERE id_mitooleyenda = ? AND id_usuario = ?");
    $stmt->bind_param("ii", $id_mito, $id_usuario);
    $stmt->execute();
    $stmt->close();
    header("Location: mis_mitos.php");
    exit();
}

// Obtener mitos del usuario
$stmt = $conn->prepare("SELECT id_mitooleyenda, Titulo, textobreve, imagen FROM MitoLeyenda WHERE id_usuario = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$resultado = $stmt->get_result();
$mis_mitos = $resultado->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Mitos</title>
    <link rel="stylesheet" href="estilos.css"> <!-- Puedes enlazar tu CSS aquí -->
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 30px;
        }

        h1 {
            color: #333;
            margin-bottom: 20px;
        }

        .mito-card {
            background: #fff;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            align-items: flex-start;
            gap: 20px;
        }

        .mito-card img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }

        .mito-info {
            flex-grow: 1;
        }

        .mito-info h3 {
            margin: 0 0 10px;
        }

        .mito-info p {
            margin: 0 0 15px;
            color: #555;
        }

        .acciones a {
            margin-right: 15px;
            text-decoration: none;
            color: #4a90e2;
            font-weight: bold;
        }

        .acciones a.eliminar {
            color: #e74c3c;
        }

        .acciones a:hover {
            text-decoration: underline;
        }

        .btn-crear {
            display: inline-block;
            margin-bottom: 30px;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
        }

        .btn-crear:hover {
            background: #556cd6;
        }
    </style>
</head>
<body>
    <h1>Mis Mitos</h1>
    <a class="btn-crear" href="crearmito.html">+ Crear nuevo mito</a>

    <?php if (count($mis_mitos) > 0): ?>
        <?php foreach ($mis_mitos as $mito): ?>
            <div class="mito-card">
                <img src="mitos/<?= htmlspecialchars($mito['imagen']) ?>" alt="Imagen del mito">
                <div class="mito-info">
                    <h3><?= htmlspecialchars($mito['Titulo']) ?></h3>
                    <p><?= htmlspecialchars($mito['textobreve']) ?></p>
                    <div class="acciones">
                        <a href="editar_mito.php?id=<?= $mito['id_mitooleyenda'] ?>">✏️ Editar</a>
                        <a href="mis_mitos.php?eliminar=<?= $mito['id_mitooleyenda'] ?>" class="eliminar" onclick="return confirm('¿Estás seguro de que deseas eliminar este mito?')">🗑️ Eliminar</a>
                        <a href="mitos.php?id=<?= $mito['id_mitooleyenda'] ?>">🔍 Ver</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No has creado ningún mito todavía.</p>
    <?php endif; ?>
</body>
</html>
