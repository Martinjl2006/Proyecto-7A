<?php
session_start();

// Incluir la conexión desde main.php
include 'main.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['username'])) {
    header("Location: registro.html");
    exit();
}

$sql = "SELECT id_mitooleyenda, Titulo, Descripcion, imagen FROM MitoLeyenda";
$resultado = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mitos y Leyendas</title>
    <link rel="stylesheet" href="style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            padding: 0 20px;
        }

        .user-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .profile-pic {
            width: 60px;
            height: 60px;
            background-color: #ccc;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85em;
            text-align: center;
            padding: 5px;
        }

        .user-name {
            font-size: 1.1em;
            color: #333;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .btn-explorar {
            background-color: #a0644e;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1em;
            text-decoration: none;
            display: inline-block;
        }

        .btn-explorar:hover {
            background-color: #8b5340;
        }

        .btn-logout {
            background-color: #dc3545;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 0.9em;
            text-decoration: none;
            display: inline-block;
        }

        .btn-logout:hover {
            background-color: #c82333;
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
            font-size: 2em;
        }

        .mitos-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .mito {
            background-color: #ddd;
            border-radius: 15px;
            padding: 25px;
            position: relative;
            min-height: 280px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .mito-imagen-circle {
            position: absolute;
            top: -20px;
            left: -20px;
            width: 80px;
            height: 80px;
            background-color: #bbb;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75em;
            text-align: center;
            padding: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .mito-imagen-circle img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .mito h2 {
            color: #333;
            font-size: 1.3em;
            margin-top: 30px;
            margin-bottom: 15px;
        }

        .mito p {
            color: #555;
            font-size: 0.95em;
            line-height: 1.5;
        }

        .btn-ver-mas {
            display: none;
            margin-top: 20px;
            padding: 10px 25px;
            background-color: #0033cc;
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-size: 0.95em;
            font-weight: bold;
            text-align: center;
            width: 100%;
            cursor: pointer;
            border: none;
        }

        .btn-ver-mas:hover {
            background-color: #0027a3;
        }

        .mito:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
            z-index: 10;
        }

        .mito:hover .btn-ver-mas {
            display: block;
        }

        footer {
            text-align: center;
            margin-top: 60px;
            padding: 40px 20px;
            color: #333;
            font-size: 1.2em;
            font-weight: bold;
        }

        @media (max-width: 1024px) {
            .mitos-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .mitos-grid {
                grid-template-columns: 1fr;
            }

            .header {
                flex-direction: column;
                gap: 20px;
            }

            .header-right {
                flex-direction: column;
                width: 100%;
            }

            .btn-explorar, .btn-logout {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="user-section">
            <div class="profile-pic">foto de perfil</div>
            <span class="user-name"><?= htmlspecialchars($_SESSION['username']) ?></span>
        </div>
        <div class="header-right">
            <button class="btn-explorar">Explorar mapa</button>
            <a href="logout.php" class="btn-logout">Cerrar sesión</a>
        </div>
    </div>

    <h1>Mitos y Leyendas Argentinas</h1>

    <div class="mitos-grid">
        <?php 
        while($mito = $resultado->fetch_assoc()): 
        ?>
            <div class="mito">
                <div class="mito-imagen-circle">
                    <img src="img/<?php echo htmlspecialchars($mito['imagen']); ?>" alt="<?= htmlspecialchars($mito['Titulo']) ?>">
                </div>
                <h2><?= htmlspecialchars($mito['Titulo']) ?></h2>
                <p><?= htmlspecialchars($mito['Descripcion']) ?></p>
                <a class="btn-ver-mas" href="mitos.php?id=<?= $mito['id_mitooleyenda'] ?>">Leer mas</a>
            </div>
        <?php 
        endwhile; 
        ?>
    </div>

    <footer>
        Footer
    </footer>

</body>
</html>