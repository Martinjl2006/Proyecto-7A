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

// Obtener información del usuario
$username = $_SESSION['username'];
$sql_usuario = "SELECT foto FROM Usuarios WHERE id_usuario = ?";
$stmt_user = $conn->prepare($sql_usuario);
$stmt_user->bind_param("i", $id_usuario);
$stmt_user->execute();
$resultado_usuario = $stmt_user->get_result();
$usuario = $resultado_usuario->fetch_assoc();
$foto_perfil = $usuario['foto'] ?? null;

// Obtener mitos del usuario
$stmt = $conn->prepare("SELECT id_mitooleyenda, Titulo, textobreve, Descripcion, imagen, tipo, Votos FROM MitoLeyenda WHERE id_usuario = ? ORDER BY Titulo");
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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mis Mitos - LeyendAR</title>
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
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .profile-pic:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .profile-pic img {
            width: 150%;
            height: 150%;
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

        main {
            flex: 1;
            padding: 2rem;
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .page-title {
            font-size: 2rem;
            color: #1d2e42;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-title i {
            color: #667eea;
        }

        .btn-crear {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 12px 28px;
            border-radius: 25px;
            border: none;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .btn-crear:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
        }

        .stats-container {
            background: white;
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-icon.purple {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .stat-icon.pink {
            background: linear-gradient(135deg, #f093fb, #f5576c);
            color: white;
        }

        .stat-icon.blue {
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            color: white;
        }

        .stat-info h3 {
            font-size: 1.8rem;
            color: #1d2e42;
            font-weight: 700;
        }

        .stat-info p {
            font-size: 0.9rem;
            color: #666;
        }

        .mitos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .mito-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
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
            background: linear-gradient(135deg, #f0f4ff, #e8eeff);
            border: 2px solid #2b4ab8;
        }

        .mito-badges {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 0.5rem;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .badge-mito {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .badge-leyenda {
            background: linear-gradient(135deg, #f093fb, #f5576c);
            color: white;
        }

        .badge-sin-tipo {
            background: #e0e0e0;
            color: #666;
        }

        .badge-votos {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .mito-header {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .mito-image {
            width: 70px;
            height: 70px;
            border-radius: 12px;
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
            font-size: 1.2rem;
            font-weight: 700;
            color: #1d2e42;
            line-height: 1.3;
        }

        .mito-text {
            color: #555;
            line-height: 1.6;
            font-size: 0.95rem;
        }

        .mito-extra {
            display: none;
            color: #444;
            line-height: 1.6;
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }

        .mito-card.expanded .mito-extra {
            display: block;
        }

        .mito-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: auto;
        }

        .btn-action {
            padding: 8px 16px;
            border-radius: 10px;
            border: none;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
            font-family: 'Quicksand', sans-serif;
        }

        .btn-editar {
            background: #e8eeff;
            color: #2b4ab8;
        }

        .btn-editar:hover {
            background: #d0dcff;
            transform: translateY(-1px);
        }

        .btn-ver {
            background: #e0f7fa;
            color: #006064;
        }

        .btn-ver:hover {
            background: #b2ebf2;
            transform: translateY(-1px);
        }

        .btn-eliminar {
            background: #ffebee;
            color: #c62828;
        }

        .btn-eliminar:hover {
            background: #ffcdd2;
            transform: translateY(-1px);
        }

        .no-mitos {
            text-align: center;
            padding: 4rem 1rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .no-mitos i {
            font-size: 5rem;
            color: #ddd;
            margin-bottom: 1.5rem;
        }

        .no-mitos h3 {
            font-size: 1.8rem;
            margin-bottom: 1rem;
            color: #555;
        }

        .no-mitos p {
            font-size: 1.1rem;
            color: #777;
            margin-bottom: 2rem;
        }

        .no-mitos .btn-crear {
            margin: 0 auto;
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

            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .btn-crear {
                width: 100%;
                justify-content: center;
            }

            .stats-container {
                flex-direction: column;
                gap: 1rem;
            }

            .mitos-grid {
                grid-template-columns: 1fr;
            }

            main {
                padding: 1rem;
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
                <div class="profile-pic" onclick="location.href='perfil.php'" title="Ver perfil">
                    <?php if (!empty($foto_perfil)): ?>
                        <img src="usuarios/<?= htmlspecialchars($foto_perfil) ?>" alt="Perfil" onerror="this.parentElement.innerHTML='<i class=\'fas fa-user\'></i>'">
                    <?php else: ?>
                        <i class="fas fa-user"></i>
                    <?php endif; ?>
                </div>
                <span class="user-name"><?= htmlspecialchars($username) ?></span>
            </div>
            <a href="dashboard.php" class="btn-logout">Volver a inicio</a>
        </div>
    </header>

    <main>
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-book"></i> Mis Mitos y Leyendas
            </h1>
            <a class="btn-crear" href="crearmito.php">
                <i class="fas fa-plus"></i> Crear nuevo mito
            </a>
        </div>

        <?php 
        $total_mitos = count($mis_mitos);
        $total_mitos_tipo = count(array_filter($mis_mitos, fn($m) => $m['tipo'] === 'Mito'));
        $total_leyendas = count(array_filter($mis_mitos, fn($m) => $m['tipo'] === 'Leyenda'));
        $total_votos = array_sum(array_column($mis_mitos, 'Votos'));
        ?>

        <?php if ($total_mitos > 0): ?>
            <div class="stats-container">
                <div class="stat-item">
                    <div class="stat-icon blue">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $total_mitos ?></h3>
                        <p>Total de publicaciones</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon purple">
                        <i class="fas fa-bookmark"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $total_mitos_tipo ?></h3>
                        <p>Mitos</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon pink">
                        <i class="fas fa-bookmark"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $total_leyendas ?></h3>
                        <p>Leyendas</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon blue">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $total_votos ?></h3>
                        <p>Validaciones totales</p>
                    </div>
                </div>
            </div>

            <div class="mitos-grid">
                <?php foreach ($mis_mitos as $mito): ?>
                    <div class="mito-card" onclick="toggleExpand(this)">
                        <div class="mito-badges">
                            <?php if (!empty($mito['tipo'])): ?>
                                <span class="badge <?= $mito['tipo'] === 'Mito' ? 'badge-mito' : ($mito['tipo'] === 'Leyenda' ? 'badge-leyenda' : 'badge-sin-tipo') ?>">
                                    <i class="fas fa-bookmark"></i> <?= htmlspecialchars($mito['tipo']) ?>
                                </span>
                            <?php else: ?>
                                <span class="badge badge-sin-tipo">
                                    <i class="fas fa-question"></i> Sin clasificar
                                </span>
                            <?php endif; ?>
                            <span class="badge badge-votos">
                                <i class="fas fa-check-circle"></i> <?= $mito['Votos'] ?> validaciones
                            </span>
                        </div>
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
                        <p class="mito-text"><?= htmlspecialchars($mito['textobreve']) ?></p>
                        <?php if (!empty($mito['Descripcion'])): ?>
                            <p class="mito-extra"><?= htmlspecialchars($mito['Descripcion']) ?></p>
                        <?php endif; ?>
                        <div class="mito-actions">
                            <a href="editar_mito.php?id=<?= $mito['id_mitooleyenda'] ?>" class="btn-action btn-editar" onclick="event.stopPropagation()">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <a href="mitos.php?id=<?= $mito['id_mitooleyenda'] ?>" class="btn-action btn-ver" onclick="event.stopPropagation()">
                                <i class="fas fa-eye"></i> Ver
                            </a>
                            <a href="mis_mitos.php?eliminar=<?= $mito['id_mitooleyenda'] ?>" class="btn-action btn-eliminar" onclick="event.stopPropagation(); return confirm('¿Estás seguro de que deseas eliminar este mito?')">
                                <i class="fas fa-trash"></i> Eliminar
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-mitos">
                <i class="fas fa-book-open"></i>
                <h3>No has creado ningún mito todavía</h3>
                <p>Comienza a compartir tus historias con la comunidad</p>
                <a class="btn-crear" href="crearmito.php">
                    <i class="fas fa-plus"></i> Crear mi primer mito
                </a>
            </div>
        <?php endif; ?>
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