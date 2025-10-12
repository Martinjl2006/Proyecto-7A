<?php
session_start();

// Incluir la conexión desde main.php
include 'main.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['username'])) {
    header("Location: registro.html");
    exit();
}

// Obtener el ID del usuario
$username = $_SESSION['username'];
$sql_usuario = "SELECT id_usuario FROM Usuarios WHERE username = ?";
$stmt = $conn->prepare($sql_usuario);
$stmt->bind_param("s", $username);
$stmt->execute();
$resultado_usuario = $stmt->get_result();
$usuario = $resultado_usuario->fetch_assoc();
$id_usuario = $usuario['id_usuario'] ?? null;

// Procesar agregar/quitar de favoritos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mito_id'])) {
    $mito_id = $_POST['mito_id'];
    
    if ($id_usuario) {
        // Verificar si ya está en favoritos
        $sql_check = "SELECT id_favorito FROM Favoritos WHERE id_usuario = ? AND id_mitooleyenda = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("ii", $id_usuario, $mito_id);
        $stmt_check->execute();
        $resultado_check = $stmt_check->get_result();
        
        if ($resultado_check->num_rows > 0) {
            // Eliminar de favoritos
            $sql_delete = "DELETE FROM Favoritos WHERE id_usuario = ? AND id_mitooleyenda = ?";
            $stmt_delete = $conn->prepare($sql_delete);
            $stmt_delete->bind_param("ii", $id_usuario, $mito_id);
            $stmt_delete->execute();
        } else {
            // Agregar a favoritos
            $sql_insert = "INSERT INTO Favoritos (id_usuario, id_mitooleyenda) VALUES (?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("ii", $id_usuario, $mito_id);
            $stmt_insert->execute();
        }
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Obtener mitos favoritos del usuario
$mitos_favoritos = [];
if ($id_usuario) {
    $sql_favoritos = "SELECT m.id_mitooleyenda, m.Titulo, m.textobreve, m.Descripcion, m.imagen, m.id_provincia 
                      FROM MitoLeyenda m 
                      INNER JOIN Favoritos f ON m.id_mitooleyenda = f.id_mitooleyenda 
                      WHERE f.id_usuario = ? 
                      ORDER BY m.Titulo";
    $stmt_favoritos = $conn->prepare($sql_favoritos);
    $stmt_favoritos->bind_param("i", $id_usuario);
    $stmt_favoritos->execute();
    $resultado_favoritos = $stmt_favoritos->get_result();
    
    while($mito = $resultado_favoritos->fetch_assoc()) {
        $mitos_favoritos[] = $mito;
    }
}

// Obtener mitos agrupados por provincia
$sql = "SELECT id_mitooleyenda, Titulo, textobreve, Descripcion, imagen, id_provincia FROM MitoLeyenda ORDER BY id_provincia, Titulo";
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

// Función para verificar si un mito es favorito
function esFavorito($mito_id, $favoritos) {
    foreach ($favoritos as $fav) {
        if ($fav['id_mitooleyenda'] == $mito_id) {
            return true;
        }
    }
    return false;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mitos - LeyendAR</title>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        .favoritos-section {
            margin-bottom: 3rem;
            display: none;
        }

        .favoritos-section.active {
            display: block;
        }

        .favoritos-title {
            font-size: 1.8rem;
            color: #1d2e42;
            margin-bottom: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .favoritos-title i {
            color: #ffc107;
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
            position: relative;
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

        .btn-favorito {
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            transition: transform 0.2s;
            color: #ccc;
            z-index: 10;
        }

        .btn-favorito:hover {
            transform: scale(1.2);
        }

        .btn-favorito.active {
            color: #ffc107;
        }

        .mito-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding-right: 40px;
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
            max-height: 80px;
            overflow: hidden;
            text-overflow: ellipsis;
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
        <!-- Sección de Favoritos -->
        <?php if (count($mitos_favoritos) > 0): ?>
            <section class="favoritos-section active">
                <h2 class="favoritos-title">
                    <i class="fas fa-star"></i> Mis Favoritos
                </h2>
                <div class="mitos-scroll">
                    <?php foreach ($mitos_favoritos as $mito): ?>
                        <div class="mito-card" onclick="toggleExpand(this)">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="mito_id" value="<?= $mito['id_mitooleyenda'] ?>">
                                <button type="submit" class="btn-favorito active" title="Quitar de favoritos">
                                    <i class="fas fa-star"></i>
                                </button>
                            </form>
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
                            <p class="mito-text"><?= htmlspecialchars($mito['textobreve']) ?></p>
                            <p class="mito-extra"><?= htmlspecialchars($mito['Descripcion']) ?></p>
                            <button class="leer-mas-btn" onclick="event.stopPropagation(); location.href='mitos.php?id=<?= $mito['id_mitooleyenda'] ?>'">Leer más</button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- Secciones por Provincia -->
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
                        $es_favorito = esFavorito($mito['id_mitooleyenda'], $mitos_favoritos);
                    ?>
                        <div class="mito-card" onclick="toggleExpand(this)">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="mito_id" value="<?= $mito['id_mitooleyenda'] ?>">
                                <button type="submit" class="btn-favorito <?= $es_favorito ? 'active' : '' ?>" title="<?= $es_favorito ? 'Quitar de favoritos' : 'Agregar a favoritos' ?>">
                                    <i class="fas fa-star"></i>
                                </button>
                            </form>
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
                            <p class="mito-text"><?= htmlspecialchars($mito['textobreve']) ?></p>
                            <p class="mito-extra"><?= htmlspecialchars($mito['Descripcion']) ?></p>
                            <button class="leer-mas-btn" onclick="event.stopPropagation(); location.href='mitos.php?id=<?= $mito['id_mitooleyenda'] ?>'">Leer más</button>
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