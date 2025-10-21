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

// Crear tabla de votos si no existe
$sql_create_table = "CREATE TABLE IF NOT EXISTS Votos_Validacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_mitooleyenda INT NOT NULL,
    voto INT NOT NULL,
    fecha_voto TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES Usuarios(id_usuario),
    FOREIGN KEY (id_mitooleyenda) REFERENCES MitoLeyenda(id_mitooleyenda) ON DELETE CASCADE,
    UNIQUE KEY unique_vote (id_usuario, id_mitooleyenda)
)";
$conn->query($sql_create_table);

// Procesar votos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mito_id']) && isset($_POST['voto'])) {
    $mito_id = $_POST['mito_id'];
    $voto = intval($_POST['voto']); // 1 para upvote, -1 para downvote
    
    if ($id_usuario) {
        // Verificar si el usuario ya votó este mito
        $sql_check = "SELECT voto FROM Votos_Validacion WHERE id_usuario = ? AND id_mitooleyenda = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("ii", $id_usuario, $mito_id);
        $stmt_check->execute();
        $resultado_check = $stmt_check->get_result();
        
        if ($resultado_check->num_rows > 0) {
            // Ya votó, actualizar voto
            $voto_anterior = $resultado_check->fetch_assoc()['voto'];
            
            if ($voto_anterior == $voto) {
                // Si hace click en el mismo voto, lo elimina (neutral)
                $sql_delete = "DELETE FROM Votos_Validacion WHERE id_usuario = ? AND id_mitooleyenda = ?";
                $stmt_delete = $conn->prepare($sql_delete);
                $stmt_delete->bind_param("ii", $id_usuario, $mito_id);
                $stmt_delete->execute();
                
                // Restar el voto de Votos
                $sql_update = "UPDATE MitoLeyenda SET Votos = Votos - ? WHERE id_mitooleyenda = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("ii", $voto, $mito_id);
                $stmt_update->execute();
            } else {
                // Cambiar de voto
                $sql_update_voto = "UPDATE Votos_Validacion SET voto = ?, fecha_voto = CURRENT_TIMESTAMP WHERE id_usuario = ? AND id_mitooleyenda = ?";
                $stmt_update_voto = $conn->prepare($sql_update_voto);
                $stmt_update_voto->bind_param("iii", $voto, $id_usuario, $mito_id);
                $stmt_update_voto->execute();
                
                // Actualizar Votos (restar el anterior y sumar el nuevo)
                $diferencia = $voto - $voto_anterior;
                $sql_update = "UPDATE MitoLeyenda SET Votos = Votos + ? WHERE id_mitooleyenda = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("ii", $diferencia, $mito_id);
                $stmt_update->execute();
            }
        } else {
            // Nuevo voto
            $sql_insert = "INSERT INTO Votos_Validacion (id_usuario, id_mitooleyenda, voto) VALUES (?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("iii", $id_usuario, $mito_id, $voto);
            $stmt_insert->execute();
            
            // Sumar voto
            $sql_update = "UPDATE MitoLeyenda SET Votos = Votos + ? WHERE id_mitooleyenda = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("ii", $voto, $mito_id);
            $stmt_update->execute();
        }
        
        // Verificar si el mito debe ser eliminado (Votos <= -15)
        $sql_check_votos = "SELECT Votos FROM MitoLeyenda WHERE id_mitooleyenda = ?";
        $stmt_check_votos = $conn->prepare($sql_check_votos);
        $stmt_check_votos->bind_param("i", $mito_id);
        $stmt_check_votos->execute();
        $resultado_votos = $stmt_check_votos->get_result();
        
        if ($resultado_votos->num_rows > 0) {
            $votos_actuales = $resultado_votos->fetch_assoc()['Votos'];
            
            if ($votos_actuales <= -15) {
                // Eliminar el mito
                $sql_delete_mito = "DELETE FROM MitoLeyenda WHERE id_mitooleyenda = ?";
                $stmt_delete_mito = $conn->prepare($sql_delete_mito);
                $stmt_delete_mito->bind_param("i", $mito_id);
                $stmt_delete_mito->execute();
            } elseif ($votos_actuales >= 15) {
                // Marcar como verificado si alcanzó 15 votos
                $sql_verificar = "UPDATE MitoLeyenda SET Verificado = 1 WHERE id_mitooleyenda = ?";
                $stmt_verificar = $conn->prepare($sql_verificar);
                $stmt_verificar->bind_param("i", $mito_id);
                $stmt_verificar->execute();
            }
        }
    }
    
    // Construir URL de redirección con filtros
    $redirect_params = [];
    if (isset($_GET['search']) && $_GET['search'] != '') {
        $redirect_params[] = 'search=' . urlencode($_GET['search']);
    }
    if (isset($_GET['tipo']) && $_GET['tipo'] != '') {
        $redirect_params[] = 'tipo=' . urlencode($_GET['tipo']);
    }
    if (isset($_GET['provincia']) && $_GET['provincia'] != '') {
        $redirect_params[] = 'provincia=' . urlencode($_GET['provincia']);
    }
    
    $redirect_url = $_SERVER['PHP_SELF'];
    if (!empty($redirect_params)) {
        $redirect_url .= '?' . implode('&', $redirect_params);
    }
    
    header("Location: " . $redirect_url);
    exit();
}

// Obtener filtros
$search = $_GET['search'] ?? '';
$filtro_tipo = $_GET['tipo'] ?? '';
$filtro_provincia = $_GET['provincia'] ?? '';

// Construir consulta con filtros - MITOS CON MENOS DE 15 VALIDACIONES Y MÁS DE -15
$sql = "SELECT m.id_mitooleyenda, m.Titulo, m.textobreve, m.Descripcion, m.imagen, m.id_provincia, m.tipo, m.Votos, m.Fecha,
        p.Nombre as nombre_provincia, u.username as autor
        FROM MitoLeyenda m 
        INNER JOIN Provincias p ON m.id_provincia = p.id_provincia 
        LEFT JOIN Usuarios u ON m.id_usuario = u.id_usuario
        WHERE m.Votos < 15 AND m.Votos > -15 AND m.Verificado = 0";
$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (m.Titulo LIKE ? OR m.textobreve LIKE ? OR m.Descripcion LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

if (!empty($filtro_tipo)) {
    $sql .= " AND m.tipo = ?";
    $params[] = $filtro_tipo;
    $types .= "s";
}

if (!empty($filtro_provincia)) {
    $sql .= " AND m.id_provincia = ?";
    $params[] = $filtro_provincia;
    $types .= "i";
}

$sql .= " ORDER BY m.Votos DESC, m.Fecha DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$resultado = $stmt->get_result();

// Obtener lista de provincias para el filtro
$sql_provincias = "SELECT id_provincia, Nombre FROM Provincias ORDER BY Nombre";
$resultado_provincias = $conn->query($sql_provincias);
$provincias = [];
while($prov = $resultado_provincias->fetch_assoc()) {
    $provincias[] = $prov;
}

// Obtener los votos del usuario actual
$votos_usuario = [];
if ($id_usuario) {
    $sql_votos = "SELECT id_mitooleyenda, voto FROM Votos_Validacion WHERE id_usuario = ?";
    $stmt_votos = $conn->prepare($sql_votos);
    $stmt_votos->bind_param("i", $id_usuario);
    $stmt_votos->execute();
    $resultado_votos = $stmt_votos->get_result();
    while($voto = $resultado_votos->fetch_assoc()) {
        $votos_usuario[$voto['id_mitooleyenda']] = $voto['voto'];
    }
}

// Agrupar los resultados en un array
$mitos = [];
while($mito = $resultado->fetch_assoc()) {
    $mitos[] = $mito;
}

// Obtener estadísticas
$sql_stats = "SELECT 
    COUNT(*) as total_pendientes,
    SUM(CASE WHEN Votos >= 0 THEN 1 ELSE 0 END) as positivos,
    SUM(CASE WHEN Votos < 0 THEN 1 ELSE 0 END) as negativos
    FROM MitoLeyenda 
    WHERE Votos < 15 AND Votos > -15 AND Verificado = 0";
$resultado_stats = $conn->query($sql_stats);
$stats = $resultado_stats->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Validar Mitos - LeyendAR</title>
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
            background: #dae0e6;
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
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }

        .page-header {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
        }

        .page-title {
            font-size: 2rem;
            color: #1d2e42;
            margin-bottom: 0.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-title i {
            color: #ff4500;
        }

        .page-description {
            color: #666;
            font-size: 1rem;
            line-height: 1.6;
        }

        .stats-bar {
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            display: flex;
            gap: 2rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stat-item i {
            font-size: 1.2rem;
        }

        .stat-item.total i {
            color: #ff4500;
        }

        .stat-item.positive i {
            color: #4caf50;
        }

        .stat-item.negative i {
            color: #f44336;
        }

        .stat-number {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1d2e42;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        /* FILTROS */
        .filtros-container {
            background: white;
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .filtros-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .filtros-title {
            font-size: 1.3rem;
            color: #1d2e42;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }

        .filtros-form {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .filtro-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            flex: 1;
            min-width: 200px;
        }

        .filtro-group label {
            font-size: 0.9rem;
            color: #555;
            font-weight: 600;
        }

        .filtro-group input,
        .filtro-group select {
            padding: 10px 14px;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-family: 'Quicksand', sans-serif;
            font-size: 0.95rem;
            transition: border-color 0.2s;
        }

        .filtro-group input:focus,
        .filtro-group select:focus {
            outline: none;
            border-color: #ff4500;
        }

        .filtros-actions {
            display: flex;
            gap: 0.5rem;
            align-items: flex-end;
        }

        .btn-filtrar,
        .btn-limpiar {
            padding: 10px 24px;
            border: none;
            border-radius: 10px;
            font-family: 'Quicksand', sans-serif;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-filtrar {
            background: #ff4500;
            color: white;
        }

        .btn-filtrar:hover {
            background: #ff5722;
            transform: translateY(-2px);
        }

        .btn-limpiar {
            background: #e0e0e0;
            color: #555;
        }

        .btn-limpiar:hover {
            background: #d0d0d0;
        }

        /* POSTS ESTILO REDDIT */
        .mitos-container {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .mito-post {
            background: white;
            border-radius: 8px;
            display: flex;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: box-shadow 0.2s;
            overflow: hidden;
        }

        .mito-post:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .vote-section {
            background: #f8f9fa;
            padding: 0.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
            min-width: 60px;
        }

        .vote-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.5rem;
            color: #878a8c;
            transition: all 0.2s;
            padding: 0.25rem;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
        }

        .vote-btn:hover {
            background: rgba(0,0,0,0.05);
        }

        .vote-btn.upvote:hover {
            color: #ff4500;
        }

        .vote-btn.downvote:hover {
            color: #7193ff;
        }

        .vote-btn.upvote.active {
            color: #ff4500;
        }

        .vote-btn.downvote.active {
            color: #7193ff;
        }

        .vote-count {
            font-size: 0.9rem;
            font-weight: 700;
            color: #1c1c1c;
        }

        .vote-count.positive {
            color: #ff4500;
        }

        .vote-count.negative {
            color: #7193ff;
        }

        .mito-content {
            flex: 1;
            padding: 1.25rem;
        }

        .mito-header-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
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

        .badge-sin-tipo {
            background: #e0e0e0;
            color: #666;
        }

        .badge-provincia {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #4caf50;
        }

        .mito-author {
            color: #878a8c;
            font-size: 0.85rem;
        }

        .mito-author strong {
            color: #1c1c1c;
        }

        .mito-title-link {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1d2e42;
            margin-bottom: 0.5rem;
            display: block;
            text-decoration: none;
            transition: color 0.2s;
        }

        .mito-title-link:hover {
            color: #ff4500;
        }

        .mito-preview {
            color: #555;
            line-height: 1.5;
            font-size: 0.95rem;
            margin-bottom: 0.75rem;
        }

        .mito-thumbnail {
            width: 120px;
            height: 90px;
            background: #f0f0f0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
            margin-left: 1rem;
        }

        .mito-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .mito-footer {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .mito-action {
            color: #878a8c;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            transition: background 0.2s;
            text-decoration: none;
        }

        .mito-action:hover {
            background: rgba(0,0,0,0.05);
        }

        .votos-warning {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.85rem;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
        }

        .votos-warning.danger {
            background: #ffebee;
            color: #c62828;
        }

        .votos-warning.success {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .no-resultados {
            text-align: center;
            padding: 4rem 1rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .no-resultados i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 1rem;
        }

        .no-resultados h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: #555;
        }

        .no-resultados p {
            font-size: 1rem;
            color: #777;
        }

        footer {
            background: white;
            text-align: center;
            padding: 1.5rem;
            color: #666;
            font-size: 0.9rem;
            margin-top: auto;
            box-shadow: 0 -2px 8px rgba(0,0,0,0.1);
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

            .stats-bar {
                gap: 1rem;
            }

            .filtros-form {
                flex-direction: column;
            }

            .filtro-group {
                width: 100%;
            }

            .filtros-actions {
                width: 100%;
                flex-direction: column;
            }

            .btn-filtrar,
            .btn-limpiar {
                width: 100%;
            }

            .page-title {
                font-size: 1.5rem;
            }

            .mito-post {
                flex-direction: column;
            }

            .vote-section {
                flex-direction: row;
                min-width: unset;
                width: 100%;
                justify-content: center;
                padding: 0.5rem 1rem;
            }

            .mito-thumbnail {
                margin-left: 0;
                margin-top: 1rem;
                width: 100%;
                height: 150px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo-section">
            <a href="mapa.php"><img src="logo_logo_re_logo_sin_fondo_-removebg-preview.png" alt="Logo"></a>
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
                <span class="user-name"><?= htmlspecialchars($_SESSION['username']) ?></span>
            </div>
            <a href="dashboard.php" class="btn-logout">Volver a inicio</a>
        </div>
    </header>

    <main>
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-check-double"></i> Validar Mitos y Leyendas
            </h1>
            <p class="page-description">
                Ayuda a la comunidad validando mitos y leyendas. Los mitos con <strong>15+ votos positivos</strong> se publican en la lista oficial. 
                Los que alcanzan <strong>-15 votos</strong> se eliminan automáticamente. ¡Tu voto cuenta!
            </p>
        </div>

        <!-- Barra de Estadísticas -->
        <div class="stats-bar">
            <div class="stat-item total">
                <i class="fas fa-list"></i>
                <div>
                    <div class="stat-number"><?= $stats['total_pendientes'] ?></div>
                    <div class="stat-label">Pendientes</div>
                </div>
            </div>
            <div class="stat-item positive">
                <i class="fas fa-arrow-up"></i>
                <div>
                    <div class="stat-number"><?= $stats['positivos'] ?></div>
                    <div class="stat-label">Con votos positivos</div>
                </div>
            </div>
            <div class="stat-item negative">
                <i class="fas fa-arrow-down"></i>
                <div>
                    <div class="stat-number"><?= $stats['negativos'] ?></div>
                    <div class="stat-label">Con votos negativos</div>
                </div>
            </div>
        </div>

        <!-- Sistema de Filtros -->
        <div class="filtros-container">
            <div class="filtros-header">
                <h2 class="filtros-title">
                    <i class="fas fa-filter"></i> Filtros
                </h2>
            </div>
            
            <form method="GET" class="filtros-form">
                <div class="filtro-group">
                    <label for="search">Buscar</label>
                    <input type="text" id="search" name="search" placeholder="Título, descripción..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="filtro-group">
                    <label for="tipo">Tipo</label>
                    <select id="tipo" name="tipo">
                        <option value="">Todos</option>
                        <option value="Mito" <?= $filtro_tipo === 'Mito' ? 'selected' : '' ?>>Mito</option>
                        <option value="Leyenda" <?= $filtro_tipo === 'Leyenda' ? 'selected' : '' ?>>Leyenda</option>
                    </select>
                </div>
                <div class="filtro-group">
                    <label for="provincia">Provincia</label>
                    <select id="provincia" name="provincia">
                        <option value="">Todas</option>
                        <?php foreach($provincias as $prov): ?>
                            <option value="<?= $prov['id_provincia'] ?>" <?= $filtro_provincia == $prov['id_provincia'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($prov['Nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filtros-actions">
                    <button type="submit" class="btn-filtrar">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                    <button type="button" class="btn-limpiar" onclick="location.href='validar_mitos.php'">
                        <i class="fas fa-times"></i> Limpiar
                    </button>
                </div>
            </form>
        </div>

        <!-- Lista de Mitos -->
        <div class="mitos-container">
            <?php if (empty($mitos)): ?>
                <div class="no-resultados">
                    <i class="fas fa-inbox"></i>
                    <h3>No hay mitos pendientes de validación</h3>
                    <p>¡Todos los mitos han sido revisados o no hay resultados para tu búsqueda!</p>
                </div>
            <?php else: ?>
                <?php foreach ($mitos as $mito): 
                    $voto_usuario = $votos_usuario[$mito['id_mitooleyenda']] ?? 0;
                    $votos_para_publicar = 15 - $mito['Votos'];
                    $votos_para_eliminar = $mito['Votos'] - (-15);
                ?>
                    <div class="mito-post">
                        <div class="vote-section">
                            <form method="POST" style="margin: 0;">
                                <input type="hidden" name="mito_id" value="<?= $mito['id_mitooleyenda'] ?>">
                                <input type="hidden" name="voto" value="1">
                                <button type="submit" class="vote-btn upvote <?= $voto_usuario == 1 ? 'active' : '' ?>" title="Voto positivo">
                                    <i class="fas fa-arrow-up"></i>
                                </button>
                            </form>
                            <span class="vote-count <?= $mito['Votos'] > 0 ? 'positive' : ($mito['Votos'] < 0 ? 'negative' : '') ?>">
                                <?= $mito['Votos'] ?>
                            </span>
                            <form method="POST" style="margin: 0;">
                                <input type="hidden" name="mito_id" value="<?= $mito['id_mitooleyenda'] ?>">
                                <input type="hidden" name="voto" value="-1">
                                <button type="submit" class="vote-btn downvote <?= $voto_usuario == -1 ? 'active' : '' ?>" title="Voto negativo">
                                    <i class="fas fa-arrow-down"></i>
                                </button>
                            </form>
                        </div>
                        
                        <div class="mito-content">
                            <div class="mito-header-info">
                                <?php if (!empty($mito['tipo'])): ?>
                                    <span class="badge <?= $mito['tipo'] === 'Mito' ? 'badge-mito' : ($mito['tipo'] === 'Leyenda' ? 'badge-leyenda' : 'badge-sin-tipo') ?>">
                                        <i class="fas fa-bookmark"></i> <?= htmlspecialchars($mito['tipo']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-sin-tipo">
                                        <i class="fas fa-question"></i> Sin clasificar
                                    </span>
                                <?php endif; ?>
                                <span class="badge badge-provincia">
                                    <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($mito['nombre_provincia']) ?>
                                </span>
                                <span class="mito-author">
                                    Enviado por <strong><?= htmlspecialchars($mito['autor'] ?? 'Anónimo') ?></strong>
                                </span>
                            </div>
                            
                            <a href="mitos.php?id=<?= $mito['id_mitooleyenda'] ?>" class="mito-title-link">
                                <?= htmlspecialchars($mito['Titulo']) ?>
                            </a>
                            
                            <p class="mito-preview">
                                <?= htmlspecialchars($mito['textobreve']) ?>
                            </p>
                            
                            <div class="mito-footer">
                                <a href="mitos.php?id=<?= $mito['id_mitooleyenda'] ?>" class="mito-action">
                                    <i class="fas fa-comment"></i> Ver detalles
                                </a>
                                
                                <?php if ($mito['Votos'] >= 10): ?>
                                    <span class="votos-warning success">
                                        <i class="fas fa-star"></i> <?= $votos_para_publicar ?> votos para publicar
                                    </span>
                                <?php elseif ($mito['Votos'] <= -10): ?>
                                    <span class="votos-warning danger">
                                        <i class="fas fa-exclamation-triangle"></i> <?= $votos_para_eliminar ?> votos para eliminar
                                    </span>
                                <?php else: ?>
                                    <span class="mito-action">
                                        <i class="fas fa-clock"></i> Pendiente de validación
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($mito['imagen'])): ?>
                            <div class="mito-thumbnail">
                                <img src="mitos/<?= htmlspecialchars($mito['imagen']) ?>" alt="<?= htmlspecialchars($mito['Titulo']) ?>" onerror="this.parentElement.innerHTML='<i class=\'fas fa-image\' style=\'font-size: 2rem; color: #ccc;\'></i>'">
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        © 2025 leyendAR - Mitos y Leyendas Argentinas
    </footer>
</body>
</html>