<?php
session_start();
include 'main.php';

if (!isset($_SESSION['username'])) {
    header("Location: registro.html");
    exit();
}

$username = $_SESSION['username'];
$sql_usuario = "SELECT id_usuario FROM Usuarios WHERE username = ?";
$stmt = $conn->prepare($sql_usuario);
$stmt->bind_param("s", $username);
$stmt->execute();
$resultado_usuario = $stmt->get_result();
$usuario = $resultado_usuario->fetch_assoc();
$id_usuario = $usuario['id_usuario'] ?? null;

$filtro_tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'todos';
$filtro_orden = isset($_GET['orden']) ? $_GET['orden'] : 'provincia';
$filtro_provincia = isset($_GET['provincia']) ? $_GET['provincia'] : '';
$filtro_ciudad = isset($_GET['ciudad']) ? $_GET['ciudad'] : '';
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mito_id'])) {
    $mito_id = $_POST['mito_id'];
    
    if ($id_usuario) {
        $sql_check = "SELECT id_favorito FROM Favoritos WHERE id_usuario = ? AND id_mitooleyenda = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("ii", $id_usuario, $mito_id);
        $stmt_check->execute();
        $resultado_check = $stmt_check->get_result();
        
        if ($resultado_check->num_rows > 0) {
            $sql_delete = "DELETE FROM Favoritos WHERE id_usuario = ? AND id_mitooleyenda = ?";
            $stmt_delete = $conn->prepare($sql_delete);
            $stmt_delete->bind_param("ii", $id_usuario, $mito_id);
            $stmt_delete->execute();
        } else {
            $sql_insert = "INSERT INTO Favoritos (id_usuario, id_mitooleyenda) VALUES (?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("ii", $id_usuario, $mito_id);
            $stmt_insert->execute();
        }
    }
    
    header("Location: " . $_SERVER['PHP_SELF'] . "?" . http_build_query($_GET));
    exit();
}

$mitos_favoritos = [];
if ($id_usuario) {
    $sql_favoritos = "SELECT m.id_mitooleyenda, m.Titulo, m.textobreve, m.Descripcion, m.imagen, m.id_provincia, m.Esmito, m.Votos 
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

$sql = "SELECT m.id_mitooleyenda, m.Titulo, m.textobreve, m.Descripcion, m.imagen, m.id_provincia, m.id_ciudad, m.Esmito, m.Votos 
        FROM MitoLeyenda m
        WHERE 1=1";

if ($filtro_tipo === 'mito') {
    $sql .= " AND m.Esmito = TRUE";
} elseif ($filtro_tipo === 'leyenda') {
    $sql .= " AND m.Esmito = FALSE";
}

if (!empty($filtro_provincia)) {
    $sql .= " AND m.id_provincia = " . (int)$filtro_provincia;
}

if (!empty($filtro_ciudad)) {
    $sql .= " AND m.id_ciudad = " . (int)$filtro_ciudad;
}

if (!empty($busqueda)) {
    $sql .= " AND (m.Titulo LIKE '%" . $conn->real_escape_string($busqueda) . "%' 
              OR m.textobreve LIKE '%" . $conn->real_escape_string($busqueda) . "%'
              OR m.Descripcion LIKE '%" . $conn->real_escape_string($busqueda) . "%')";
}

if ($filtro_orden === 'ranking') {
    $sql .= " ORDER BY m.Votos DESC, m.Titulo ASC";
} else {
    $sql .= " ORDER BY m.id_provincia, m.Titulo ASC";
}

$resultado = $conn->query($sql);

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

// Obtener lista de provincias
$provincias = $conn->query("SELECT id_provincia, Nombre FROM Provincias ORDER BY Nombre");

// Obtener ciudades si hay provincia seleccionada
$ciudades = [];
if (!empty($filtro_provincia)) {
    $sql_ciudades = "SELECT id_ciudad, Nombre FROM Ciudad WHERE id_provincia = " . (int)$filtro_provincia . " ORDER BY Nombre";
    $resultado_ciudades = $conn->query($sql_ciudades);
    while($ciudad = $resultado_ciudades->fetch_assoc()) {
        $ciudades[] = $ciudad;
    }
}

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
        }

        header {
            background: white;
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .menu-toggle {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #1d2e42;
            cursor: pointer;
            padding: 8px;
            transition: background 0.2s;
            border-radius: 8px;
        }

        .menu-toggle:hover {
            background: #f0f0f0;
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

        .search-toggle {
            background: none;
            border: none;
            font-size: 1.3rem;
            color: #1d2e42;
            cursor: pointer;
            padding: 8px;
            transition: background 0.2s;
            border-radius: 50%;
        }

        .search-toggle:hover {
            background: #f0f0f0;
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

        .search-bar {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: none;
            animation: slideDown 0.3s ease;
        }

        .search-bar.active {
            display: block;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .search-form {
            display: flex;
            gap: 10px;
            max-width: 600px;
            margin: 0 auto;
        }

        .search-input {
            flex: 1;
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 25px;
            font-family: 'Quicksand', sans-serif;
            font-size: 0.95rem;
            outline: none;
            transition: border-color 0.2s;
        }

        .search-input:focus {
            border-color: #1d2e42;
        }

        .search-btn {
            padding: 10px 24px;
            background: #1d2e42;
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.2s;
        }

        .search-btn:hover {
            background: #2d3e52;
        }

        .sidebar {
            position: fixed;
            left: -300px;
            top: 0;
            width: 300px;
            height: 100vh;
            background: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            transition: left 0.3s ease;
            z-index: 200;
            overflow-y: auto;
            padding: 80px 0 20px 0;
        }

        .sidebar.active {
            left: 0;
        }

        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: none;
            z-index: 150;
        }

        .sidebar-overlay.active {
            display: block;
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e0e0e0;
            font-size: 1.2rem;
            font-weight: 700;
            color: #1d2e42;
        }

        .sidebar-section {
            padding: 1.5rem;
            border-bottom: 1px solid #e0e0e0;
        }

        .sidebar-section h3 {
            font-size: 1rem;
            color: #1d2e42;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .sidebar-options {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .sidebar-option {
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            background: white;
            color: #1d2e42;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.2s;
            text-decoration: none;
            display: block;
            text-align: center;
        }

        .sidebar-option:hover {
            background: #f0f4ff;
        }

        .sidebar-option.active {
            background: #1d2e42;
            color: white;
            border-color: #1d2e42;
        }

        .sidebar-select {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 20px;
            font-family: 'Quicksand', sans-serif;
            font-size: 0.9rem;
            font-weight: 600;
            outline: none;
            cursor: pointer;
            background: white;
            transition: border-color 0.2s;
        }

        .sidebar-select:focus {
            border-color: #1d2e42;
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

        .section-title {
            font-size: 1.8rem;
            color: #1d2e42;
            margin-bottom: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-title i {
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

        .mitos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            padding-bottom: 1rem;
        }

        .mitos-grid .mito-card {
            min-width: unset;
            max-width: unset;
            width: 100%;
        }

        .mito-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            min-width: 300px;
            max-width: 300px;
            transition: all 0.5s ease;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            position: relative;
        }

        .mito-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        .mito-card.expandido {
            max-width: 100%;
        }

        .mito-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 700;
            z-index: 5;
        }

        .mito-badge.es-mito {
            background: #4a6cd6;
            color: white;
        }

        .mito-badge.es-leyenda {
            background: #ff9800;
            color: white;
        }

        .votos-badge {
            position: absolute;
            top: 15px;
            right: 55px;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            background: #ffc107;
            color: #1d2e42;
            z-index: 5;
            display: flex;
            align-items: center;
            gap: 4px;
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
            max-height: 48px;
            overflow: hidden;
            transition: max-height 0.6s ease;
        }

        .mito-text.expandido {
            max-height: 500px;
        }

        .btn-ver-mas {
            padding: 10px 24px;
            background: #ff7b00;
            color: white;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-weight: 700;
            font-size: 0.9rem;
            transition: all 0.4s ease;
            text-decoration: none;
            display: none;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(255, 123, 0, 0.3);
            opacity: 0;
            transform: translateY(-10px);
            margin-top: 10px;
        }

        .btn-ver-mas.visible {
            display: flex;
            opacity: 1;
            transform: translateY(0);
        }

        .btn-ver-mas:hover {
            background: #e66d00;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 123, 0, 0.5);
        }

        @media (max-width: 768px) {
            header {
                padding: 1rem;
            }

            .logo-section span {
                font-size: 1.2rem;
            }

            .user-name {
                display: none;
            }

            .sidebar {
                width: 280px;
                left: -280px;
            }

            .mitos-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }

            .mito-card {
                min-width: 250px;
                max-width: 250px;
            }

            .mito-card.expandido {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>
    
    <div class="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-filter"></i> Filtros
        </div>
        
        <div class="sidebar-section">
            <h3>Tipo</h3>
            <div class="sidebar-options">
                <a href="?tipo=todos&orden=<?= $filtro_orden ?>&provincia=<?= $filtro_provincia ?>&ciudad=<?= $filtro_ciudad ?>&busqueda=<?= $busqueda ?>" 
                   class="sidebar-option <?= $filtro_tipo === 'todos' ? 'active' : '' ?>">
                    Todos
                </a>
                <a href="?tipo=mito&orden=<?= $filtro_orden ?>&provincia=<?= $filtro_provincia ?>&ciudad=<?= $filtro_ciudad ?>&busqueda=<?= $busqueda ?>" 
                   class="sidebar-option <?= $filtro_tipo === 'mito' ? 'active' : '' ?>">
                    <i class="fas fa-ghost"></i> Mitos
                </a>
                <a href="?tipo=leyenda&orden=<?= $filtro_orden ?>&provincia=<?= $filtro_provincia ?>&ciudad=<?= $filtro_ciudad ?>&busqueda=<?= $busqueda ?>" 
                   class="sidebar-option <?= $filtro_tipo === 'leyenda' ? 'active' : '' ?>">
                    <i class="fas fa-book"></i> Leyendas
                </a>
            </div>
        </div>

        <div class="sidebar-section">
            <h3>Ordenar por</h3>
            <div class="sidebar-options">
                <a href="?tipo=<?= $filtro_tipo ?>&orden=provincia&provincia=<?= $filtro_provincia ?>&ciudad=<?= $filtro_ciudad ?>&busqueda=<?= $busqueda ?>" 
                   class="sidebar-option <?= $filtro_orden === 'provincia' ? 'active' : '' ?>">
                    <i class="fas fa-map-marker-alt"></i> Provincia
                </a>
                <a href="?tipo=<?= $filtro_tipo ?>&orden=ranking&provincia=<?= $filtro_provincia ?>&ciudad=<?= $filtro_ciudad ?>&busqueda=<?= $busqueda ?>" 
                   class="sidebar-option <?= $filtro_orden === 'ranking' ? 'active' : '' ?>">
                    <i class="fas fa-trophy"></i> Ranking
                </a>
            </div>
        </div>

        <div class="sidebar-section">
            <h3>Provincia</h3>
            <form method="GET" id="provinciaForm">
                <input type="hidden" name="tipo" value="<?= $filtro_tipo ?>">
                <input type="hidden" name="orden" value="<?= $filtro_orden ?>">
                <input type="hidden" name="busqueda" value="<?= $busqueda ?>">
                <select name="provincia" class="sidebar-select" onchange="document.getElementById('provinciaForm').submit()">
                    <option value="">Todas las provincias</option>
                    <?php while($prov = $provincias->fetch_assoc()): ?>
                        <option value="<?= $prov['id_provincia'] ?>" <?= $filtro_provincia == $prov['id_provincia'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($prov['Nombre']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </form>
        </div>

        <?php if (!empty($ciudades)): ?>
        <div class="sidebar-section">
            <h3>Ciudad</h3>
            <form method="GET" id="ciudadForm">
                <input type="hidden" name="tipo" value="<?= $filtro_tipo ?>">
                <input type="hidden" name="orden" value="<?= $filtro_orden ?>">
                <input type="hidden" name="provincia" value="<?= $filtro_provincia ?>">
                <input type="hidden" name="busqueda" value="<?= $busqueda ?>">
                <select name="ciudad" class="sidebar-select" onchange="document.getElementById('ciudadForm').submit()">
                    <option value="">Todas las ciudades</option>
                    <?php foreach($ciudades as $ciudad): ?>
                        <option value="<?= $ciudad['id_ciudad'] ?>" <?= $filtro_ciudad == $ciudad['id_ciudad'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($ciudad['Nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <header>
        <div class="header-left">
            <button class="menu-toggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <div class="logo-section">
                <a href="mapa.php"><img src="logo_logo_re_logo_sin_fondo_-removebg-preview.png" alt="Logo"></a>
                <span>leyendAR</span>
            </div>
        </div>
        <div class="header-right">
            <button class="search-toggle" onclick="toggleSearch()">
                <i class="fas fa-search"></i>
            </button>
            <div class="user-section">
                <div class="profile-pic">
                    <img src="img/profile.jpg" alt="Perfil" onerror="this.style.display='none'">
                </div>
                <span class="user-name"><?= htmlspecialchars($_SESSION['username']) ?></span>
            </div>
            <a href="dashboard.php" class="btn-logout">volver a inicio</a>
        </div>
    </header>

    <div class="search-bar" id="searchBar">
        <form method="GET" class="search-form">
            <input type="hidden" name="tipo" value="<?= $filtro_tipo ?>">
            <input type="hidden" name="orden" value="<?= $filtro_orden ?>">
            <input type="hidden" name="provincia" value="<?= $filtro_provincia ?>">
            <input type="hidden" name="ciudad" value="<?= $filtro_ciudad ?>">
            <input type="text" name="busqueda" class="search-input" placeholder="Buscar mito o leyenda..." value="<?= htmlspecialchars($busqueda) ?>">
            <button type="submit" class="search-btn">
                <i class="fas fa-search"></i> Buscar
            </button>
        </form>
    </div>

    <main>
        <?php if (count($mitos_favoritos) > 0): ?>
            <section class="favoritos-section active">
                <h2 class="section-title">
                    <i class="fas fa-star"></i> Mis Favoritos
                </h2>
                <div class="mitos-scroll">
                    <?php foreach ($mitos_favoritos as $mito): ?>
                        <div class="mito-card" onmouseenter="expandirMito(<?= $mito['id_mitooleyenda'] ?>)" onmouseleave="cerrarMito(<?= $mito['id_mitooleyenda'] ?>)">
                            <span class="mito-badge <?= $mito['Esmito'] ? 'es-mito' : 'es-leyenda' ?>">
                                <?= $mito['Esmito'] ? 'MITO' : 'LEYENDA' ?>
                            </span>
                            <?php if ($mito['Votos'] > 0): ?>
                            <span class="votos-badge">
                                <i class="fas fa-thumbs-up"></i> <?= $mito['Votos'] ?>
                            </span>
                            <?php endif; ?>
                            <form method="POST" style="display: inline;" onclick="event.stopPropagation()">
                                <input type="hidden" name="mito_id" value="<?= $mito['id_mitooleyenda'] ?>">
                                <button type="submit" class="btn-favorito active" title="Quitar de favoritos">
                                    <i class="fas fa-star"></i>
                                </button>
                            </form>
                            <div class="mito-header">
                                <div class="mito-image">
                                    <?php if (!empty($mito['imagen'])): ?>
                                        <img src="mitos/<?= htmlspecialchars($mito['imagen']) ?>" alt="<?= htmlspecialchars($mito['Titulo']) ?>" onerror="this.parentElement.textContent='Sin imagen'">
                                    <?php else: ?>
                                        <span>Sin imagen</span>
                                    <?php endif; ?>
                                </div>
                                <h3 class="mito-title"><?= htmlspecialchars($mito['Titulo']) ?></h3>
                            </div>
                            <p class="mito-text" id="texto-<?= $mito['id_mitooleyenda'] ?>"><?= htmlspecialchars($mito['textobreve']) ?></p>
                            <a href="mitos.php?id=<?= $mito['id_mitooleyenda'] ?>" class="btn-ver-mas" id="btn-<?= $mito['id_mitooleyenda'] ?>" onclick="event.stopPropagation()">
                                Ver más
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if (empty($mitosPorProvincia)): ?>
            <p style="text-align: center; color: #666; font-size: 1.1rem; padding: 3rem;">No hay mitos registrados con estos filtros.</p>
        <?php else: ?>
            <?php if ($filtro_orden === 'ranking'): ?>
                <section class="provincia-section">
                    <h2 class="provincia-title">
                        <i class="fas fa-trophy"></i> Ranking por Validaciones
                    </h2>
                    <div class="mitos-grid">
                        <?php 
                        $posicion = 1;
                        foreach ($mitosPorProvincia as $provincia => $mitos): 
                            foreach ($mitos as $mito): 
                                $es_favorito = esFavorito($mito['id_mitooleyenda'], $mitos_favoritos);
                        ?>
                            <div class="mito-card" onmouseenter="expandirMito(<?= $mito['id_mitooleyenda'] ?>)" onmouseleave="cerrarMito(<?= $mito['id_mitooleyenda'] ?>)">
                                <span class="mito-badge <?= $mito['Esmito'] ? 'es-mito' : 'es-leyenda' ?>">
                                    <?= $mito['Esmito'] ? 'MITO' : 'LEYENDA' ?>
                                </span>
                                <span class="votos-badge">
                                    <i class="fas fa-award"></i> #<?= $posicion ?> - <?= $mito['Votos'] ?> votos
                                </span>
                                <form method="POST" style="display: inline;" onclick="event.stopPropagation()">
                                    <input type="hidden" name="mito_id" value="<?= $mito['id_mitooleyenda'] ?>">
                                    <button type="submit" class="btn-favorito <?= $es_favorito ? 'active' : '' ?>" title="<?= $es_favorito ? 'Quitar de favoritos' : 'Agregar a favoritos' ?>">
                                        <i class="fas fa-star"></i>
                                    </button>
                                </form>
                                <div class="mito-header">
                                    <div class="mito-image">
                                        <?php if (!empty($mito['imagen'])): ?>
                                            <img src="mitos/<?= htmlspecialchars($mito['imagen']) ?>" alt="<?= htmlspecialchars($mito['Titulo']) ?>" onerror="this.parentElement.textContent='Sin imagen'">
                                        <?php else: ?>
                                            <span>Sin imagen</span>
                                        <?php endif; ?>
                                    </div>
                                    <h3 class="mito-title"><?= htmlspecialchars($mito['Titulo']) ?></h3>
                                </div>
                                <p class="mito-text" id="texto-<?= $mito['id_mitooleyenda'] ?>"><?= htmlspecialchars($mito['textobreve']) ?></p>
                                <a href="mitos.php?id=<?= $mito['id_mitooleyenda'] ?>" class="btn-ver-mas" id="btn-<?= $mito['id_mitooleyenda'] ?>" onclick="event.stopPropagation()">
                                    Ver más
                                </a>
                            </div>
                        <?php 
                                $posicion++;
                            endforeach; 
                        endforeach; 
                        ?>
                    </div>
                </section>
            <?php else: ?>
                <?php foreach ($mitosPorProvincia as $provincia => $mitos): ?>
                <section class="provincia-section">
                    <h2 class="provincia-title"><?= htmlspecialchars($provincia) ?></h2>
                    <div class="mitos-scroll">
                        <?php foreach ($mitos as $mito): 
                            $es_favorito = esFavorito($mito['id_mitooleyenda'], $mitos_favoritos);
                        ?>
                            <div class="mito-card" onmouseenter="expandirMito(<?= $mito['id_mitooleyenda'] ?>)" onmouseleave="cerrarMito(<?= $mito['id_mitooleyenda'] ?>)">
                                <span class="mito-badge <?= $mito['Esmito'] ? 'es-mito' : 'es-leyenda' ?>">
                                    <?= $mito['Esmito'] ? 'MITO' : 'LEYENDA' ?>
                                </span>
                                <?php if ($mito['Votos'] > 0): ?>
                                <span class="votos-badge">
                                    <i class="fas fa-thumbs-up"></i> <?= $mito['Votos'] ?>
                                </span>
                                <?php endif; ?>
                                <form method="POST" style="display: inline;" onclick="event.stopPropagation()">
                                    <input type="hidden" name="mito_id" value="<?= $mito['id_mitooleyenda'] ?>">
                                    <button type="submit" class="btn-favorito <?= $es_favorito ? 'active' : '' ?>" title="<?= $es_favorito ? 'Quitar de favoritos' : 'Agregar a favoritos' ?>">
                                        <i class="fas fa-star"></i>
                                    </button>
                                </form>
                                <div class="mito-header">
                                    <div class="mito-image">
                                        <?php if (!empty($mito['imagen'])): ?>
                                            <img src="mitos/<?= htmlspecialchars($mito['imagen']) ?>" alt="<?= htmlspecialchars($mito['Titulo']) ?>" onerror="this.parentElement.textContent='Sin imagen'">
                                        <?php else: ?>
                                            <span>Sin imagen</span>
                                        <?php endif; ?>
                                    </div>
                                    <h3 class="mito-title"><?= htmlspecialchars($mito['Titulo']) ?></h3>
                                </div>
                                <p class="mito-text" id="texto-<?= $mito['id_mitooleyenda'] ?>"><?= htmlspecialchars($mito['textobreve']) ?></p>
                                <a href="mitos.php?id=<?= $mito['id_mitooleyenda'] ?>" class="btn-ver-mas" id="btn-<?= $mito['id_mitooleyenda'] ?>" onclick="event.stopPropagation()">
                                    Ver más
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        function toggleSearch() {
            const searchBar = document.getElementById('searchBar');
            searchBar.classList.toggle('active');
            if (searchBar.classList.contains('active')) {
                searchBar.querySelector('input').focus();
            }
        }

        function expandirMito(id) {
            const card = event.currentTarget;
            const texto = document.getElementById('texto-' + id);
            const btn = document.getElementById('btn-' + id);
            
            card.classList.add('expandido');
            texto.classList.add('expandido');
            btn.classList.add('visible');
        }

        function cerrarMito(id) {
            const card = event.currentTarget;
            const texto = document.getElementById('texto-' + id);
            const btn = document.getElementById('btn-' + id);
            
            card.classList.remove('expandido');
            texto.classList.remove('expandido');
            btn.classList.remove('visible');
        }

        // Cerrar sidebar al hacer clic en un enlace
        document.querySelectorAll('.sidebar-option').forEach(option => {
            option.addEventListener('click', () => {
                setTimeout(() => {
                    toggleSidebar();
                }, 200);
            });
        });
    </script>
</body>
</html>