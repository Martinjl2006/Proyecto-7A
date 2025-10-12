<?php
session_start();

// Incluir la conexión desde main.php
include 'main.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['username'])) {
    header("Location: registro.html");
    exit();
}

// Obtener mitos agrupados por provincia
$sql = "SELECT id_mitooleyenda, Titulo, Descripcion, imagen, id_provincia FROM MitoLeyenda ORDER BY id_provincia, Titulo";
$resultado = $conn->query($sql);



// Agrupar los resultados por provincia
$mitosPorProvincia = [];
while($mito = $resultado->fetch_assoc()) {
    $nombre_provincia = "SELECT Nombre FROM Provincias WHERE id_provincia = " . $mito['id_provincia'];
    $resultado2 = $conn->query($nombre_provincia);
    $provincia = $resultado2->fetch_assoc();

    $nombreprovincia = $provincia['Nombre'];
    if (!isset($mitosPorProvincia[$nombreprovincia])) {
        $mitosPorProvincia[$nombreprovincia] = [];
    }
    $mitosPorProvincia[$nombreprovincia][] = $mito;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mitos - LeyendAR</title>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Quicksand', sans-serif;
            background: #f0f0f0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding-bottom: 80px;
        }

        header {
            background: white;
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-section img {
            height: 42px;
        }

        .logo-section span {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1d2e42;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-section {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .profile-pic {
            width: 42px;
            height: 42px;
            background-color: #ccc;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            text-align: center;
            padding: 5px;
            overflow: hidden;
        }

        .profile-pic img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .user-name {
            font-size: 0.95rem;
            color: #333;
            font-weight: 600;
        }

        .btn-logout {
            background-color: #dc3545;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.85rem;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.2s;
        }

        .btn-logout:hover {
            background-color: #c82333;
        }

        .explore-btn-fixed {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: linear-gradient(135deg, #1d2e42, #3c506d);
            color: white;
            padding: 14px 36px;
            border-radius: 50px;
            border: none;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
            z-index: 1000;
        }

        .explore-btn-fixed:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.4);
        }

        main {
            flex: 1;
            padding: 2rem;
            width: 100%;
        }

        .provincia-section {
            margin-bottom: 3rem;
        }

        .provincia-title {
            font-size: 1.8rem;
            color: #1d2e42;
            margin-bottom: 1.5rem;
            font-weight: 700;
        }

        .mitos-scroll {
            display: flex;
            gap: 1.5rem;
            overflow-x: auto;
            padding-bottom: 1rem;
            scrollbar-width: thin;
        }

        .mitos-scroll::-webkit-scrollbar {
            height: 8px;
        }

        .mitos-scroll::-webkit-scrollbar-track {
            background: #e0e0e0;
            border-radius: 10px;
        }

        .mitos-scroll::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }

        .mito-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            min-width: 300px;
            max-width: 300px;
            transition: all 0.3s ease;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .mito-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        .mito-card.expanded {
            min-width: 450px;
            max-width: 450px;
            background: linear-gradient(135deg, #f0f4ff, #e8eeff);
            border: 2px solid #2b4ab8;
        }

        .mito-header {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .mito-image {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            color: #888;
            flex-shrink: 0;
            overflow: hidden;
        }

        .mito-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .mito-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1d2e42;
        }

        .mito-text {
            color: #555;
            line-height: 1.5;
            font-size: 0.95rem;
        }

        .mito-extra {
            display: none;
            color: #444;
            line-height: 1.6;
            margin-top: 0.5rem;
        }

        .mito-card.expanded .mito-extra {
            display: block;
        }

        .leer-mas-btn {
            display: none;
            background: linear-gradient(135deg, #2b4ab8, #4a6cd6);
            color: white;
            padding: 10px 24px;
            border-radius: 25px;
            border: none;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            align-self: flex-start;
            transition: transform 0.2s;
        }

        .mito-card.expanded .leer-mas-btn {
            display: block;
        }

        .leer-mas-btn:hover {
            transform: scale(1.05);
        }

        footer {
            background: #e0e0e0;
            text-align: center;
            padding: 1.5rem;
            color: #666;
            font-size: 0.9rem;
            margin-top: auto;
        }

        @media (max-width: 768px) {
            header {
                padding: 1rem;
                flex-direction: column;
                gap: 15px;
            }

            .header-right {
                width: 100%;
                justify-content: space-between;
            }

            .logo-section span {
                font-size: 1.2rem;
            }

            .provincia-title {
                font-size: 1.4rem;
            }

            .mito-card {
                min-width: 250px;
                max-width: 250px;
            }

            .mito-card.expanded {
                min-width: 320px;
                max-width: 320px;
            }

            .explore-btn-fixed {
                bottom: 15px;
                right: 15px;
                padding: 12px 28px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo-section">
            <img src="logo_logo_re_logo_sin_fondo_-removebg-preview.png" alt="Logo">
            <span>leyendAR</span>
        </div>
        <div class="header-right">
            <div class="user-section">
                <div class="profile-pic">
                    <img src="img/profile.jpg" alt="Perfil" onerror="this.style.display='none'">
                </div>
                <span class="user-name"><?= htmlspecialchars($_SESSION['username']) ?></span>
            </div>
            <a href="dashboard.php" class="btn-logout">volver a inicio</a>
        </div>
    </header>

    <button class="explore-btn-fixed" onclick="location.href='mapa.php'">Explorar mapa</button>

    <main>
        <?php 
        if (empty($mitosPorProvincia)): 
        ?>
            <p style="text-align: center; color: #666; font-size: 1.1rem;">No hay mitos registrados en el sistema.</p>
        <?php 
        else:
            foreach ($mitosPorProvincia as $provincia => $mitos): 
        ?>
            <section class="provincia-section">
                <h2 class="provincia-title"><?= htmlspecialchars($provincia) ?></h2>
                <div class="mitos-scroll">
                    <?php 
                    foreach ($mitos as $mito): 
                    ?>
                        <div class="mito-card" onclick="toggleExpand(this)">
                            <div class="mito-header">
                                <div class="mito-image">
                                    <?php if (!empty($mito['imagen'])): ?>
                                        <img src="mitos/<?= htmlspecialchars($mito['imagen']) ?>" alt="<?= htmlspecialchars($mito['Titulo']) ?>" onerror="this.parentElement.textContent='Imagen no disponible'">
                                    <?php else: ?>
                                        <span>Sin imagen</span>
                                    <?php endif; ?>
                                </div>
                                <h3 class="mito-title"><?= htmlspecialchars($mito['Titulo']) ?></h3>
                            </div>
                            <p class="mito-text"><?= htmlspecialchars($mito['Descripcion']) ?></p>
                            <p class="mito-extra"><?= htmlspecialchars($mito['Descripcion']) ?></p>
                            <button class="leer-mas-btn" onclick="event.stopPropagation(); location.href='mitos.php?id=<?= $mito['id_mitooleyenda'] ?>'">Leer mas</button>
                        </div>
                    <?php 
                    endforeach; 
                    ?>
                </div>
            </section>
        <?php 
            endforeach;
        endif; 
        ?>
    </main>

    <footer>
        © 2025 leyendAR - Mitos y Leyendas Argentinas
    </footer>

    <script>
        function toggleExpand(card) {
            // Cerrar todas las otras tarjetas
            document.querySelectorAll('.mito-card').forEach(c => {
                if (c !== card) c.classList.remove('expanded');
            });
            // Toggle la tarjeta clickeada
            card.classList.toggle('expanded');
        }
    </script>
</body>
</html>