<?php
session_start();
include 'main.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['username'])) {
    header("Location: registro.html");
    exit();
}

// Inicializar variables
$mito = null;
$error = null;

// Obtener el ID del usuario
$username = $_SESSION['username'];
$sql_usuario = "SELECT id_usuario, Username, Nombre, foto FROM Usuarios WHERE Username = ?";
$stmt_usuario = $conn->prepare($sql_usuario);
$stmt_usuario->bind_param("s", $username);
$stmt_usuario->execute();
$resultado_usuario = $stmt_usuario->get_result();
$usuario = $resultado_usuario->fetch_assoc();
$id_usuario = $usuario['id_usuario'] ?? null;
$fotoperfil = $usuario['foto'] ?? null;
$nombreusuario = $usuario['Nombre'] ?? null;

// Procesar like del mito (solo incrementar, no unlike, y solo una vez por sesión)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_like'])) {
    $id_mito = intval($_POST['id_mito']);
    
    if ($id_usuario && $id_mito) {
        // Inicializar array de liked mitos si no existe
        if (!isset($_SESSION['liked_mitos'])) {
            $_SESSION['liked_mitos'] = [];
        }
        
        // Verificar si ya dio like en esta sesión
        if (!in_array($id_mito, $_SESSION['liked_mitos'])) {
            // Incrementar Votos en mitoleyenda
            $sql_update = "UPDATE mitoleyenda SET Votos = Votos + 1 WHERE id_mitooleyenda = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("i", $id_mito);
            $stmt_update->execute();
            
            // Agregar a la sesión para evitar likes múltiples en la misma sesión
            $_SESSION['liked_mitos'][] = $id_mito;
        }
        
        header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id_mito);
        exit();
    }
}

// Procesar nuevo comentario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuevo_comentario'])) {
    $comentario = trim($_POST['nuevo_comentario']);
    $id_mito = intval($_POST['id_mito']);
    
    if (!empty($comentario) && $id_usuario && $id_mito) {
        $sql_insert = "INSERT INTO Comentarios (Descripcion, Fecha, id_usuario, id_mitooleyenda) VALUES (?, NOW(), ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("sii", $comentario, $id_usuario, $id_mito);
        
        if ($stmt_insert->execute()) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id_mito);
            exit();
        }
    }
}

// Procesar nueva respuesta (solo a comentarios, no a otras respuestas)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nueva_respuesta'])) {
    $respuesta = trim($_POST['nueva_respuesta']);
    $id_comentario = intval($_POST['id_comentario']);
    $id_mito = intval($_POST['id_mito']);
    
    if (!empty($respuesta) && $id_usuario && $id_comentario) {
        $sql_insert = "INSERT INTO Respuestas (Descripcion, Fecha, id_comentario, id_usuario) VALUES (?, NOW(), ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("sii", $respuesta, $id_comentario, $id_usuario);
        
        if ($stmt_insert->execute()) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id_mito);
            exit();
        }
    }
}

// Verificar si se recibió un ID válido por GET
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);

    $sql = "SELECT m.id_mitooleyenda, m.Titulo, m.textobreve, m.Descripcion, m.imagen, m.Votos, p.Nombre as Provincia 
            FROM mitoleyenda m
            INNER JOIN Provincias p ON m.id_provincia = p.id_provincia
            WHERE m.id_mitooleyenda = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows > 0) {
            $mito = $resultado->fetch_assoc();
            
            // Verificar si el usuario dio like en esta sesión
            $user_liked = false;
            if (isset($_SESSION['liked_mitos']) && in_array($id, $_SESSION['liked_mitos'])) {
                $user_liked = true;
            }
            $mito['user_liked'] = $user_liked;
        } else {
            $error = "No se encontró el mito con ID: $id";
        }
        $stmt->close();
    } else {
        $error = "Error en la consulta SQL: " . $conn->error;
    }
} else {
    $error = "No se especificó un ID de mito válido.";
}

// Función para obtener respuestas de un comentario (solo nivel 1)
function obtenerRespuestas($conn, $id_comentario) {
    $sql = "SELECT r.id_respuesta, r.Descripcion, r.Fecha, u.Username, u.Nombre, u.foto
            FROM Respuestas r
            INNER JOIN Usuarios u ON r.id_usuario = u.id_usuario
            WHERE r.id_comentario = ?
            ORDER BY r.Fecha ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_comentario);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    $respuestas = [];
    while ($row = $resultado->fetch_assoc()) {
        $respuestas[] = $row;
    }
    $stmt->close();
    
    return $respuestas;
}

// Obtener comentarios del mito con sus respuestas
$comentarios = [];
if ($mito) {
    $sql_comentarios = "SELECT c.id_comentario, c.Descripcion, c.Fecha, u.Username, u.Nombre, u.foto
                        FROM Comentarios c
                        INNER JOIN Usuarios u ON c.id_usuario = u.id_usuario
                        WHERE c.id_mitooleyenda = ?
                        ORDER BY c.Fecha DESC";
    $stmt_com = $conn->prepare($sql_comentarios);
    $stmt_com->bind_param("i", $id);
    $stmt_com->execute();
    $resultado_com = $stmt_com->get_result();
    
    while ($row = $resultado_com->fetch_assoc()) {
        // Obtener respuestas del comentario
        $row['respuestas'] = obtenerRespuestas($conn, $row['id_comentario']);
        $comentarios[] = $row;
    }
    $stmt_com->close();
}

// Obtener mitos relacionados (3 aleatorios)
$mitosRelacionados = [];
if ($mito) {
    $sqlRelacionados = "SELECT m.id_mitooleyenda, m.Titulo, m.textobreve, m.Descripcion, m.imagen, p.Nombre as Provincia
                        FROM mitoleyenda m
                        INNER JOIN Provincias p ON m.id_provincia = p.id_provincia
                        WHERE m.id_mitooleyenda != ? 
                        ORDER BY RAND() 
                        LIMIT 3";
    $stmtRel = $conn->prepare($sqlRelacionados);
    $stmtRel->bind_param("i", $id);
    $stmtRel->execute();
    $resultadoRel = $stmtRel->get_result();
    
    while ($row = $resultadoRel->fetch_assoc()) {
        $mitosRelacionados[] = $row;
    }
    $stmtRel->close();
}

// Calcular tiempo de lectura
function calcularTiempoLectura($texto) {
    $palabras = str_word_count(strip_tags($texto));
    $minutos = ceil($palabras / 200);
    return $minutos;
}

// Función para obtener la foto de perfil del usuario
function obtenerFotoPerfil($nombreUsuario, $fotoDb) {
    if (!empty($fotoDb)) {
        return htmlspecialchars($fotoDb);
    }
    if (!empty($nombreUsuario)) {
        return 'usuarios/' . htmlspecialchars($nombreUsuario) . '.jpg';
    }
    return null;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $mito ? htmlspecialchars($mito['Titulo']) : "Error"; ?> - Pantalla de Mito</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      background: #f8f9fa;
      color: #333;
    }

    header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 10px 20px;
      background: white;
      border-bottom: 1px solid #ddd;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    header .usuario {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    header .profile-pic {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: #ccc;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      flex-shrink: 0;
    }

    header .profile-pic img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    header .usuario span {
      font-weight: 600;
      color: #1d2e42;
    }

    header .botones {
      display: flex;
      gap: 10px;
      align-items: center;
    }

    header button,
    header a.btn-pdf {
      background: #ff7b00;
      border: none;
      padding: 10px 15px;
      color: white;
      border-radius: 20px;
      cursor: pointer;
      font-weight: bold;
      text-decoration: none;
      display: inline-block;
      transition: background 0.3s ease;
      font-size: 14px;
    }

    header button:hover,
    header a.btn-pdf:hover {
      background: #e66d00;
    }

    .contenedor {
      max-width: 900px;
      margin: 20px auto;
      padding: 0 15px;
    }

    .imagen-principal {
      width: 100%;
      height: 250px;
      background: linear-gradient(135deg, #2c3e50 0%, #8b4513 100%);
      display: flex;
      justify-content: center;
      align-items: center;
      border-radius: 10px;
      margin-bottom: 20px;
      overflow: hidden;
    }

    .imagen-principal img {
      max-width: 100%;
      max-height: 100%;
      object-fit: contain;
    }

    h1 {
      font-size: 26px;
      margin-bottom: 10px;
    }

    .meta {
      font-size: 14px;
      color: gray;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 15px;
      flex-wrap: wrap;
    }

    .btn-like-mito {
      background: none;
      border: 2px solid #ff4500;
      color: #ff4500;
      padding: 8px 20px;
      border-radius: 25px;
      cursor: pointer;
      font-weight: bold;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s ease;
      font-size: 14px;
    }

    .btn-like-mito:hover {
      background: #ff4500;
      color: white;
      transform: scale(1.05);
    }

    .btn-like-mito.liked {
      background: #ff4500;
      color: white;
    }

    .btn-like-mito i {
      font-size: 16px;
    }

    .card {
      background: white;
      padding: 15px;
      border-radius: 10px;
      margin-bottom: 20px;
      box-shadow: 0px 2px 5px rgba(0,0,0,0.05);
    }

    .card h2 {
      font-size: 18px;
      margin-bottom: 15px;
      color: #1d2e42;
    }

    .card p {
      line-height: 1.6;
      margin-bottom: 15px;
    }

    .card ul {
      line-height: 1.8;
    }

    /* Estilos para comentarios */
    .comentarios-container {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    .formulario-comentario {
      background: #f8f9fa;
      padding: 15px;
      border-radius: 10px;
      border: 1px solid #e0e0e0;
    }

    .formulario-comentario h3 {
      margin-top: 0;
      margin-bottom: 10px;
      font-size: 14px;
    }

    .formulario-comentario textarea {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 5px;
      font-family: Arial, sans-serif;
      font-size: 14px;
      resize: vertical;
      min-height: 80px;
      box-sizing: border-box;
    }

    .formulario-comentario textarea:focus {
      outline: none;
      border-color: #ff7b00;
      box-shadow: 0 0 5px rgba(255, 123, 0, 0.3);
    }

    .formulario-comentario button {
      background: #ff7b00;
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 5px;
      cursor: pointer;
      font-weight: bold;
      margin-top: 10px;
      transition: background 0.3s ease;
    }

    .formulario-comentario button:hover {
      background: #e66d00;
    }

    .comentario {
      background: white;
      padding: 15px;
      border-radius: 8px;
      border-left: 4px solid #ff7b00;
    }

    .comentario-principal {
      display: flex;
      gap: 12px;
      margin-bottom: 10px;
    }

    .comentario-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: #ccc;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      overflow: hidden;
    }

    .comentario-avatar img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .comentario-content {
      flex: 1;
    }

    .comentario-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 8px;
    }

    .comentario-usuario {
      font-weight: bold;
      color: #1d2e42;
    }

    .comentario-fecha {
      font-size: 12px;
      color: #999;
    }

    .comentario-texto {
      color: #555;
      line-height: 1.5;
      font-size: 14px;
      margin-bottom: 10px;
    }

    .comentario-acciones {
      display: flex;
      gap: 15px;
      margin-top: 8px;
    }

    .btn-responder {
      background: none;
      border: none;
      color: #ff7b00;
      cursor: pointer;
      font-size: 13px;
      font-weight: 600;
      padding: 0;
      display: flex;
      align-items: center;
      gap: 5px;
      transition: color 0.2s;
    }

    .btn-responder:hover {
      color: #e66d00;
      text-decoration: underline;
    }

    .formulario-respuesta {
      display: none;
      margin-top: 15px;
      padding: 15px;
      background: #f8f9fa;
      border-radius: 8px;
      border: 1px solid #e0e0e0;
    }

    .formulario-respuesta.activo {
      display: block;
    }

    .formulario-respuesta textarea {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 5px;
      font-family: Arial, sans-serif;
      font-size: 13px;
      resize: vertical;
      min-height: 60px;
      box-sizing: border-box;
    }

    .formulario-respuesta textarea:focus {
      outline: none;
      border-color: #ff7b00;
      box-shadow: 0 0 5px rgba(255, 123, 0, 0.3);
    }

    .formulario-respuesta .botones-respuesta {
      display: flex;
      gap: 10px;
      margin-top: 10px;
    }

    .formulario-respuesta button {
      padding: 8px 16px;
      border-radius: 5px;
      cursor: pointer;
      font-size: 13px;
      font-weight: bold;
      border: none;
      transition: background 0.3s ease;
    }

    .formulario-respuesta button[type="submit"] {
      background: #ff7b00;
      color: white;
    }

    .formulario-respuesta button[type="submit"]:hover {
      background: #e66d00;
    }

    .formulario-respuesta button[type="button"] {
      background: #e0e0e0;
      color: #555;
    }

    .formulario-respuesta button[type="button"]:hover {
      background: #d0d0d0;
    }

    .respuestas-container {
      margin-top: 15px;
      padding-left: 25px;
      border-left: 2px solid #e0e0e0;
    }

    .respuesta {
      display: flex;
      gap: 10px;
      padding: 12px;
      background: #f8f9fa;
      border-radius: 8px;
      margin-bottom: 10px;
    }

    .respuesta-avatar {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      background: #ccc;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      overflow: hidden;
    }

    .respuesta-avatar img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .respuesta-content {
      flex: 1;
    }

    .respuesta-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 6px;
    }

    .respuesta-usuario {
      font-weight: bold;
      color: #1d2e42;
      font-size: 13px;
    }

    .respuesta-fecha {
      font-size: 11px;
      color: #999;
    }

    .respuesta-texto {
      color: #555;
      line-height: 1.5;
      font-size: 13px;
    }

    .sin-comentarios {
      text-align: center;
      color: #999;
      padding: 20px;
      font-style: italic;
    }

    /* Estilos para relatos relacionados */
    .relatos {
      display: flex;
      gap: 15px;
    }

    .relato {
      flex: 1;
      background: white;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      transition: all 0.3s ease;
      cursor: pointer;
      text-decoration: none;
      color: inherit;
      border: 2px solid transparent;
      display: flex;
      flex-direction: column;
    }

    .relato:hover {
      transform: translateY(-3px);
      box-shadow: 0 6px 15px rgba(0,0,0,0.15);
      border-color: #ff7b00;
    }

    .relato-imagen {
      width: 100%;
      height: 140px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      overflow: hidden;
    }

    .relato-imagen img {
      width: 100%;
      height: 100%;
      object-fit: contain;
    }

    .relato-contenido {
      padding: 12px;
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    .relato-titulo {
      font-size: 14px;
      font-weight: bold;
      margin-bottom: 6px;
      color: #333;
    }

    .relato-descripcion {
      font-size: 11px;
      color: #666;
      line-height: 1.3;
      margin-bottom: 8px;
      flex: 1;
    }

    .relato-meta {
      font-size: 10px;
      color: #999;
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: auto;
    }

    .relato-region {
      background: #ff7b00;
      color: white;
      padding: 1px 6px;
      border-radius: 8px;
      font-size: 9px;
      font-weight: bold;
    }

    .error {
      background: #f8d7da;
      color: #721c24;
      padding: 30px;
      border-radius: 10px;
      text-align: center;
      margin: 40px 0;
      border: 1px solid #f5c6cb;
    }

    .error h2 {
      margin: 0 0 15px 0;
      font-size: 20px;
    }

    footer {
      text-align: center;
      padding: 15px;
      font-size: 13px;
      color: gray;
    }

    @media (max-width: 768px) {
      .relatos {
        flex-direction: column;
        gap: 15px;
      }
      
      .relato-imagen {
        height: 100px;
      }

      header {
        flex-direction: column;
        gap: 10px;
      }

      header .botones {
        flex-direction: column;
        gap: 5px;
        width: 100%;
      }

      header button,
      header a.btn-pdf {
        padding: 8px 12px;
        font-size: 12px;
        width: 100%;
      }

      .respuestas-container {
        padding-left: 15px;
      }

      .meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
      }
    }
  </style>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</head>
<body id="contenido">

  <header>
    <div class="usuario">
      <div class="profile-pic">
        <?php 
          $fotoPerfil = obtenerFotoPerfil($nombreusuario, $fotoperfil);
          if ($fotoPerfil):
        ?>
          <div class="user-info" onclick="location.href='perfil.php'">
            <div class="profile-pic">
                <img src="usuarios/<?= htmlspecialchars($fotoPerfil) ?>" class="foto_perfil">
            </div>
        </div>
        <?php else: ?>
          <i class="fas fa-user"></i>
        <?php endif; ?>
      </div>
      <span><?php echo htmlspecialchars($username); ?></span>
    </div>
    <div class="botones">
      <button type="button" onclick="generarPDF()">Generar PDF</button>
      <button onclick="location.href='mapa.php'">Explorar mapa</button>
    </div>
  </header>

  <?php if ($error): ?>
    <div class="contenedor">
      <div class="error">
        <h2>⚠️ Error</h2>
        <p><?php echo htmlspecialchars($error); ?></p>
        <p><a href="mapa.php">← Volver al mapa</a></p>
      </div>
    </div>
  <?php else: ?>

  <div class="contenedor">
    <div class="imagen-principal">
      <?php if (!empty($mito['imagen'])): ?>
        <img src="mitos/<?php echo htmlspecialchars($mito['imagen']); ?>" 
        alt="<?php echo htmlspecialchars($mito['Titulo']); ?>" 
        onerror="this.parentElement.innerHTML='<span style=\"font-size: 72px;></span>'">
      <?php else: ?>
        <span style="font-size: 72px;"></span>
      <?php endif; ?>
    </div>

    <h1><?php echo htmlspecialchars($mito['Titulo']); ?></h1>
    <div class="meta">
      <span><?php echo htmlspecialchars($mito['Provincia']); ?> · Mitología Guaraní · <?php echo calcularTiempoLectura($mito['Descripcion']); ?> min</span>
      
      <form method="POST" style="margin: 0;">
        <input type="hidden" name="id_mito" value="<?php echo $mito['id_mitooleyenda']; ?>">
        <button type="submit" name="toggle_like" class="btn-like-mito <?php echo $mito['Votos'] ? 'liked' : ''; ?>">
          <i class="fas fa-heart"></i>
          <span><?php echo $mito['Votos']; ?> Me gusta</span>
        </button>
      </form>
    </div>

    <div class="card">
      <?php 
        $parrafos = explode("\n\n", $mito['Descripcion']);
        foreach ($parrafos as $parrafo) {
          $parrafo = trim($parrafo);
          if (!empty($parrafo)) {
            echo "<p>" . nl2br(htmlspecialchars($parrafo)) . "</p>";
          }
        }
      ?>
    </div>

    <div id="pdf-content" style="display:none;">
      <h1><?php echo htmlspecialchars($mito['Titulo']); ?></h1>
      <?php if (!empty($mito['imagen'])): ?>
        <img src="mitos/<?php echo htmlspecialchars($mito['imagen']); ?>" 
          alt="<?php echo htmlspecialchars($mito['Titulo']); ?>" 
          style="max-width: 100%; height: auto;">
      <?php endif; ?>
      <p><?php echo nl2br(htmlspecialchars($mito['Descripcion'])); ?></p>
    </div>

    <div class="card">
      <h2>Fuentes</h2>
      <ul>
        <li>Mitología Guaraní - Tradiciones Ancestrales</li>
        <li>Folklore Argentino - Leyendas Rurales</li>
        <li>Recopilación de Relatos Populares</li>
      </ul>
    </div>

    <!-- Mitos Relacionados (ahora primero) -->
    <?php if (count($mitosRelacionados) > 0): ?>
    <div class="card">
      <h2>Relatos relacionados</h2>
      
      <div class="relatos">
        <?php foreach ($mitosRelacionados as $relacionado): ?>
          <a href="mitos.php?id=<?php echo $relacionado['id_mitooleyenda']; ?>" class="relato">
            <div class="relato-imagen">
              <?php if (!empty($relacionado['imagen'])): ?>
                <img src="mitos/<?php echo htmlspecialchars($relacionado['imagen']); ?>" alt="<?php echo htmlspecialchars($relacionado['Titulo']); ?>" onerror="this.parentElement.innerHTML='<span style=\"font-size: 48px;></span>'">
              <?php else: ?>
                <span style="font-size: 48px;"></span>
              <?php endif; ?>
            </div>
            <div class="relato-contenido">
              <div class="relato-titulo"><?php echo htmlspecialchars($relacionado['Titulo']); ?></div>
              <div class="relato-descripcion">
                <?php 
                  $desc = !empty($relacionado['textobreve']) 
                          ? $relacionado['textobreve'] 
                          : mb_substr(strip_tags($relacionado['Descripcion']), 0, 100) . '...';
                  echo htmlspecialchars($desc);
                ?>
              </div>
              <div class="relato-meta">
                <span><?php echo calcularTiempoLectura($relacionado['Descripcion']); ?> min lectura</span>
                <span class="relato-region"><?php echo htmlspecialchars($relacionado['Provincia']); ?></span>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Sección de Comentarios (ahora después) -->
    <div class="card">
      <h2><i class="fas fa-comments"></i> Comentarios</h2>
      
      <div class="comentarios-container">
        <!-- Formulario para nuevo comentario -->
        <form method="POST" class="formulario-comentario">
          <h3>Dejar un comentario</h3>
          <textarea name="nuevo_comentario" placeholder="Comparte tu opinión sobre este mito..." required></textarea>
          <input type="hidden" name="id_mito" value="<?php echo $mito['id_mitooleyenda']; ?>">
          <button type="submit">Publicar comentario</button>
        </form>

        <!-- Listado de comentarios -->
        <?php if (count($comentarios) > 0): ?>
          <?php foreach ($comentarios as $comentario): ?>
            <div class="comentario">
              <div class="comentario-principal">
                <div class="comentario-avatar">
                  <?php 
                    $fotoComentarista = obtenerFotoPerfil($comentario['Nombre'], $comentario['foto']);
                    if ($fotoComentarista):
                  ?>
                    <img src="usuarios/<?= htmlspecialchars($fotoComentarista) ?>" alt="Foto de perfil">
                  <?php else: ?>
                    <i class="fas fa-user"></i>
                  <?php endif; ?>
                </div>
                <div class="comentario-content">
                  <div class="comentario-header">
                    <span class="comentario-usuario"><?php echo htmlspecialchars($comentario['Username']); ?></span>
                    <span class="comentario-fecha">
                      <?php 
                        $fecha = new DateTime($comentario['Fecha']);
                        echo $fecha->format('d/m/Y H:i');
                      ?>
                    </span>
                  </div>
                  <p class="comentario-texto"><?php echo nl2br(htmlspecialchars($comentario['Descripcion'])); ?></p>
                  <div class="comentario-acciones">
                    <button class="btn-responder" onclick="toggleFormularioRespuesta('comentario-<?php echo $comentario['id_comentario']; ?>')">
                      <i class="fas fa-reply"></i> Responder
                    </button>
                    <?php 
                      $total_respuestas = count($comentario['respuestas']);
                      if ($total_respuestas > 0): 
                    ?>
                      <span style="font-size: 13px; color: #999;">
                        <?php echo $total_respuestas; ?> 
                        <?php echo $total_respuestas === 1 ? 'respuesta' : 'respuestas'; ?>
                      </span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <!-- Formulario para responder al comentario -->
              <form method="POST" class="formulario-respuesta" id="form-comentario-<?php echo $comentario['id_comentario']; ?>">
                <textarea name="nueva_respuesta" placeholder="Escribe tu respuesta..." required></textarea>
                <input type="hidden" name="id_comentario" value="<?php echo $comentario['id_comentario']; ?>">
                <input type="hidden" name="id_mito" value="<?php echo $mito['id_mitooleyenda']; ?>">
                <div class="botones-respuesta">
                  <button type="submit">Publicar respuesta</button>
                  <button type="button" onclick="toggleFormularioRespuesta('comentario-<?php echo $comentario['id_comentario']; ?>')">Cancelar</button>
                </div>
              </form>

              <!-- Respuestas al comentario -->
              <?php if (count($comentario['respuestas']) > 0): ?>
                <div class="respuestas-container">
                  <?php foreach ($comentario['respuestas'] as $respuesta): ?>
                    <div class="respuesta">
                      <div class="respuesta-avatar">
                        <?php 
                          $fotoRespuesta = obtenerFotoPerfil($respuesta['Nombre'], $respuesta['foto']);
                          if ($fotoRespuesta):
                        ?>
                          <img src="usuarios/<?= htmlspecialchars($fotoRespuesta) ?>" alt="Foto de perfil">
                        <?php else: ?>
                          <i class="fas fa-user" style="font-size: 14px;"></i>
                        <?php endif; ?>
                      </div>
                      <div class="respuesta-content">
                        <div class="respuesta-header">
                          <span class="respuesta-usuario"><?php echo htmlspecialchars($respuesta['Username']); ?></span>
                          <span class="respuesta-fecha">
                            <?php 
                              $fechaResp = new DateTime($respuesta['Fecha']);
                              echo $fechaResp->format('d/m/Y H:i');
                            ?>
                          </span>
                        </div>
                        <p class="respuesta-texto"><?php echo nl2br(htmlspecialchars($respuesta['Descripcion'])); ?></p>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="sin-comentarios">
            <p>No hay comentarios aún. ¡Sé el primero en comentar!</p>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div>
  <?php endif; ?>

  <footer>
    © LeyendAR – Mitos y Leyendas de Argentina
  </footer>

  <script>
    function toggleFormularioRespuesta(idForm) {
      const form = document.getElementById('form-' + idForm);
      if (form.classList.contains('activo')) {
        form.classList.remove('activo');
        form.querySelector('textarea').value = '';
      } else {
        // Cerrar otros formularios abiertos
        document.querySelectorAll('.formulario-respuesta.activo').forEach(f => {
          f.classList.remove('activo');
          f.querySelector('textarea').value = '';
        });
        form.classList.add('activo');
        form.querySelector('textarea').focus();
      }
    }

    const pdfMito = {
      title: <?php echo json_encode($mito ? $mito['Titulo'] : ''); ?>,
      imagen: <?php echo json_encode($mito && !empty($mito['imagen']) ? 'mitos/' . $mito['imagen'] : ''); ?>,
      descripcion: <?php echo json_encode($mito ? strip_tags($mito['Descripcion']) : ''); ?>
    };

    function loadImageAsDataURL(url) {
      return new Promise((resolve, reject) => {
        if (!url) return resolve(null);
        const img = new Image();
        img.crossOrigin = 'anonymous';
        img.onload = () => {
          try {
            const canvas = document.createElement('canvas');
            canvas.width = img.naturalWidth;
            canvas.height = img.naturalHeight;
            canvas.getContext('2d').drawImage(img, 0, 0);
            const dataURL = canvas.toDataURL('image/jpeg', 0.92);
            resolve({ dataURL, width: img.naturalWidth, height: img.naturalHeight, type: 'JPEG' });
          } catch (e) {
            reject(e);
          }
        };
        img.onerror = () => reject(new Error('No se pudo cargar la imagen: ' + url));
        img.src = url + (url.indexOf('?') === -1 ? '?' : '&') + 'cb=' + Date.now();
      });
    }

    async function generarPDF() {
      try {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF({ unit: 'mm', format: 'a4' });
        const pageWidth = doc.internal.pageSize.getWidth();
        const pageHeight = doc.internal.pageSize.getHeight();
        const margin = 20;
        const maxWidth = pageWidth - margin * 2;
        let y = 20;

        doc.setFontSize(18);
        doc.text(pdfMito.title || 'Mito', pageWidth / 2, y, { align: 'center' });
        y += 12;

        if (pdfMito.imagen) {
          try {
            const img = await loadImageAsDataURL(pdfMito.imagen);
            if (img && img.dataURL) {
              const pxToMm = 0.264583;
              let imgWidthMM = Math.min(maxWidth, img.width * pxToMm);
              const imgHeightMM = (img.height / img.width) * imgWidthMM;
              if (y + imgHeightMM > pageHeight - margin) { doc.addPage(); y = 20; }
              doc.addImage(img.dataURL, img.type, margin, y, imgWidthMM, imgHeightMM);
              y += imgHeightMM + 8;
            }
          } catch (err) {
            console.warn('No se pudo incrustar la imagen:', err);
          }
        }

        doc.setFontSize(12);
        const texto = pdfMito.descripcion || '';
        const lines = doc.splitTextToSize(texto, maxWidth);
        const lineHeight = 7;
        for (let i = 0; i < lines.length; i++) {
          if (y + lineHeight > pageHeight - margin) { doc.addPage(); y = 20; }
          doc.text(lines[i], margin, y);
          y += lineHeight;
        }

        const fileName = (pdfMito.title || 'mito')
                      .replace(/[^a-z0-9áéíóúüñ\s]/gi, '')
                      .replace(/\s+/g, '_')
                      + '.pdf';
        doc.save(fileName);
      } catch (e) {
        console.error(e);
        alert('Error al generar PDF. Revisá la consola.');
      }
    }
  </script>
</body>
</html>