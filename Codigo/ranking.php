<?php
session_start();

// Incluir la conexión desde main.php
include 'main.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['username'])) {
    header("Location: registro.html");
    exit();
}

// Obtener el ID del usuario y su foto
$username = $_SESSION['username'];
$sql_usuario = "SELECT id_usuario, foto FROM Usuarios WHERE username = ?";
$stmt = $conn->prepare($sql_usuario);
$stmt->bind_param("s", $username);
$stmt->execute();
$resultado_usuario = $stmt->get_result();
$usuario = $resultado_usuario->fetch_assoc();
$id_usuario = $usuario['id_usuario'] ?? null;
$foto_perfil = $usuario['foto'] ?? null;

// Obtener filtro de tipo
$filtro_tipo = $_GET['tipo'] ?? 'todos';

// Construir consulta según filtro - SOLO MITOS VERIFICADOS CON MÁS DE 15 VALIDACIONES
$sql = "SELECT m.id_mitooleyenda, m.Titulo, m.textobreve, m.Descripcion, m.imagen, m.tipo, m.Votos, p.Nombre as nombre_provincia 
        FROM MitoLeyenda m 
        INNER JOIN Provincias p ON m.id_provincia = p.id_provincia 
        WHERE m.Verificado = 1 AND m.Votos >= 15";

if ($filtro_tipo === 'mito') {
    $sql .= " AND m.tipo = 'Mito'";
} elseif ($filtro_tipo === 'leyenda') {
    $sql .= " AND m.tipo = 'Leyenda'";
}

$sql .= " ORDER BY m.Votos DESC";

$stmt = $conn->prepare($sql);
$stmt->execute();
$resultado = $stmt->get_result();

$ranking = [];
while($mito = $resultado->fetch_assoc()) {
    $ranking[] = $mito;
}

// Separar top 3 del resto
$top3 = array_slice($ranking, 0, 3);
$resto = array_slice($ranking, 3);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ranking - LeyendAR</title>
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
            text-align: center;
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2.5rem;
            color: #1d2e42;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 1rem;
        }

        .page-title i {
            color: #ffc107;
        }

        .page-subtitle {
            font-size: 1.1rem;
            color: #666;
        }

        /* Filtros de tipo */
        .filtros-tipo {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 3rem;
            flex-wrap: wrap;
        }

        .btn-filtro {
            padding: 12px 28px;
            border: 2px solid #ddd;
            border-radius: 25px;
            background: white;
            color: #555;
            font-family: 'Quicksand', sans-serif;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-filtro:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .btn-filtro.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-color: #667eea;
        }

        /* Top 3 Podio */
        .podio-container {
            display: flex;
            justify-content: center;
            align-items: flex-end;
            gap: 2rem;
            margin-bottom: 4rem;
            flex-wrap: wrap;
        }

        .podio-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .podio-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
        }

        .podio-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 32px rgba(0,0,0,0.2);
        }

        .podio-card.puesto-1 {
            order: 2;
            width: 350px;
        }

        .podio-card.puesto-1::before {
            background: linear-gradient(135deg, #ffd700, #ffed4e);
        }

        .podio-card.puesto-2 {
            order: 1;
            width: 320px;
        }

        .podio-card.puesto-2::before {
            background: linear-gradient(135deg, #c0c0c0, #e8e8e8);
        }

        .podio-card.puesto-3 {
            order: 3;
            width: 320px;
        }

        .podio-card.puesto-3::before {
            background: linear-gradient(135deg, #cd7f32, #daa06d);
        }

        .medalla {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0 auto 1rem;
            color: white;
        }

        .puesto-1 .medalla {
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            box-shadow: 0 6px 20px rgba(255, 215, 0, 0.5);
        }

        .puesto-2 .medalla {
            background: linear-gradient(135deg, #c0c0c0, #e8e8e8);
            box-shadow: 0 6px 20px rgba(192, 192, 192, 0.5);
            color: #555;
        }

        .puesto-3 .medalla {
            background: linear-gradient(135deg, #cd7f32, #daa06d);
            box-shadow: 0 6px 20px rgba(205, 127, 50, 0.5);
        }

        .podio-imagen {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 1rem;
            overflow: hidden;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .podio-imagen img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .podio-badges {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            margin-bottom: 1rem;
            flex-wrap: wrap;
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

        .badge-votos {
            background: #c8e6c9;
            color: #2e7d32;
            border: 1px solid #4caf50;
            font-size: 0.85rem;
        }

        .podio-titulo {
            font-size: 1.4rem;
            font-weight: 700;
            color: #1d2e42;
            margin-bottom: 0.8rem;
            line-height: 1.3;
        }

        .podio-texto {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 1rem;
        }

        .podio-provincia {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #e8eeff;
            color: #2b4ab8;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        /* Lista del resto */
        .ranking-section {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .ranking-title {
            font-size: 1.8rem;
            color: #1d2e42;
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .ranking-lista {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .ranking-item {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            transition: all 0.3s;
            cursor: pointer;
            border: 2px solid transparent;
        }

        .ranking-item:hover {
            background: white;
            border-color: #2b4ab8;
            transform: translateX(4px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .ranking-numero {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
            flex-shrink: 0;
        }

        .ranking-imagen {
            width: 80px;
            height: 80px;
            border-radius: 12px;
            overflow: hidden;
            background: #e0e0e0;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .ranking-imagen img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .ranking-info {
            flex: 1;
            min-width: 0;
        }

        .ranking-info-header {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            margin-bottom: 0.5rem;
            flex-wrap: wrap;
        }

        .ranking-item-titulo {
            font-size: 1.2rem;
            font-weight: 700;
            color: #1d2e42;
        }

        .ranking-item-texto {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 0.5rem;
        }

        .ranking-item-footer {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .ranking-votos {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #c8e6c9;
            color: #2e7d32;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .ranking-votos i {
            font-size: 1rem;
        }

        .no-resultados {
            text-align: center;
            padding: 4rem 1rem;
            color: #666;
        }

        .no-resultados i {
            font-size: 5rem;
            color: #ddd;
            margin-bottom: 1.5rem;
        }

        .no-resultados h3 {
            font-size: 1.8rem;
            margin-bottom: 1rem;
            color: #555;
        }

        .no-resultados p {
            font-size: 1.1rem;
        }

        footer {
            background: #e0e0e0;
            text-align: center;
            padding: 1.5rem;
            color: #666;
            font-size: 0.9rem;
            margin-top: 3rem;
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

            .page-title {
                font-size: 1.8rem;
            }

            .podio-container {
                flex-direction: column;
                align-items: center;
            }

            .podio-card {
                width: 100% !important;
                max-width: 350px;
            }

            .podio-card.puesto-1,
            .podio-card.puesto-2,
            .podio-card.puesto-3 {
                order: initial;
            }

            .ranking-item {
                flex-direction: column;
                text-align: center;
            }

            .ranking-numero {
                width: 60px;
                height: 60px;
                font-size: 1.8rem;
            }

            .ranking-info-header {
                justify-content: center;
            }

            .ranking-item-footer {
                justify-content: center;
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
                <i class="fas fa-trophy"></i> Ranking de Mitos y Leyendas
            </h1>
            <p class="page-subtitle">Los mitos y leyendas más validados por la comunidad</p>
        </div>

        <!-- Filtros de Tipo -->
        <div class="filtros-tipo">
            <a href="ranking.php?tipo=todos" class="btn-filtro <?= $filtro_tipo === 'todos' ? 'active' : '' ?>">
                <i class="fas fa-list"></i> Todos
            </a>
            <a href="ranking.php?tipo=mito" class="btn-filtro <?= $filtro_tipo === 'mito' ? 'active' : '' ?>">
                <i class="fas fa-bookmark"></i> Mitos
            </a>
            <a href="ranking.php?tipo=leyenda" class="btn-filtro <?= $filtro_tipo === 'leyenda' ? 'active' : '' ?>">
                <i class="fas fa-bookmark"></i> Leyendas
            </a>
        </div>

        <?php if (count($ranking) > 0): ?>
            <!-- Top 3 Podio -->
            <?php if (count($top3) > 0): ?>
                <div class="podio-container">
                    <?php foreach ($top3 as $index => $mito): 
                        $puesto = $index + 1;
                    ?>
                        <div class="podio-card puesto-<?= $puesto ?>" onclick="location.href='mitos.php?id=<?= $mito['id_mitooleyenda'] ?>'">
                            <div class="medalla"><?= $puesto ?></div>
                            <div class="podio-imagen">
                                <?php if (!empty($mito['imagen'])): ?>
                                    <img src="mitos/<?= htmlspecialchars($mito['imagen']) ?>" alt="<?= htmlspecialchars($mito['Titulo']) ?>" onerror="this.parentElement.innerHTML='<i class=\'fas fa-image\' style=\'font-size: 2rem; color: #ccc;\'></i>'">
                                <?php else: ?>
                                    <i class="fas fa-image" style="font-size: 2rem; color: #ccc;"></i>
                                <?php endif; ?>
                            </div>
                            <div class="podio-badges">
                                <?php if (!empty($mito['tipo'])): ?>
                                    <span class="badge <?= $mito['tipo'] === 'Mito' ? 'badge-mito' : 'badge-leyenda' ?>">
                                        <i class="fas fa-bookmark"></i> <?= htmlspecialchars($mito['tipo']) ?>
                                    </span>
                                <?php endif; ?>
                                <span class="badge badge-votos">
                                    <i class="fas fa-check-circle"></i> <?= $mito['Votos'] ?> validaciones
                                </span>
                            </div>
                            <h3 class="podio-titulo"><?= htmlspecialchars($mito['Titulo']) ?></h3>
                            <p class="podio-texto"><?= htmlspecialchars(mb_substr($mito['textobreve'], 0, 100)) ?>...</p>
                            <span class="podio-provincia">
                                <i class="fas fa-map-marker-alt"></i>
                                <?= htmlspecialchars($mito['nombre_provincia']) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Resto del ranking -->
            <?php if (count($resto) > 0): ?>
                <div class="ranking-section">
                    <h2 class="ranking-title">
                        <i class="fas fa-list-ol"></i> Resto del Ranking
                    </h2>
                    <div class="ranking-lista">
                        <?php foreach ($resto as $index => $mito): 
                            $puesto = $index + 4;
                        ?>
                            <div class="ranking-item" onclick="location.href='mitos.php?id=<?= $mito['id_mitooleyenda'] ?>'">
                                <div class="ranking-numero"><?= $puesto ?></div>
                                <div class="ranking-imagen">
                                    <?php if (!empty($mito['imagen'])): ?>
                                        <img src="mitos/<?= htmlspecialchars($mito['imagen']) ?>" alt="<?= htmlspecialchars($mito['Titulo']) ?>" onerror="this.parentElement.innerHTML='<i class=\'fas fa-image\' style=\'font-size: 1.5rem; color: #ccc;\'></i>'">
                                    <?php else: ?>
                                        <i class="fas fa-image" style="font-size: 1.5rem; color: #ccc;"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="ranking-info">
                                    <div class="ranking-info-header">
                                        <h3 class="ranking-item-titulo"><?= htmlspecialchars($mito['Titulo']) ?></h3>
                                        <?php if (!empty($mito['tipo'])): ?>
                                            <span class="badge <?= $mito['tipo'] === 'Mito' ? 'badge-mito' : 'badge-leyenda' ?>">
                                                <i class="fas fa-bookmark"></i> <?= htmlspecialchars($mito['tipo']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="ranking-item-texto"><?= htmlspecialchars(mb_substr($mito['textobreve'], 0, 120)) ?>...</p>
                                    <div class="ranking-item-footer">
                                        <div class="ranking-votos">
                                            <i class="fas fa-check-circle"></i>
                                            <span><?= $mito['Votos'] ?> validaciones</span>
                                        </div>
                                        <span class="podio-provincia">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <?= htmlspecialchars($mito['nombre_provincia']) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="no-resultados">
                <i class="fas fa-trophy"></i>
                <h3>No hay mitos/leyendas en el ranking</h3>
                <p>Aún no hay mitos/leyendas verificados con suficientes validaciones para mostrar en esta categoría</p>
            </div>
        <?php endif; ?>
    </main>

    <footer>
        © 2025 leyendAR - Mitos y Leyendas Argentinas
    </footer>
</body>
</html>