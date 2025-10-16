<?php
session_start();
include 'main.php';

if (!isset($_SESSION['username'])) {
    header("Location: registro.html");
    exit();
}

$username = $_SESSION['username'];
$sql_usuario = "SELECT id_usuario, Username, Nombre, foto FROM Usuarios WHERE Username = ?";
$stmt = $conn->prepare($sql_usuario);
$stmt->bind_param("s", $username);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$id_usuario = $usuario['id_usuario'];
$fotoperfil = $usuario['foto'];
$nombreusuario = $usuario['Nombre'];

// Procesar nuevo comentario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuevo_comentario'])) {
    $comentario = trim($_POST['nuevo_comentario']);
    $id_mito = intval($_POST['id_mito']);
    
    if (!empty($comentario)) {
        $sql = "INSERT INTO Comentarios (Descripcion, Fecha, id_usuario, id_mitooleyenda) VALUES (?, NOW(), ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $comentario, $id_usuario, $id_mito);
        $stmt->execute();
        header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id_mito);
        exit();
    }
}

// Procesar respuesta a comentario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nueva_respuesta'])) {
    $respuesta = trim($_POST['nueva_respuesta']);
    $id_comentario = intval($_POST['id_comentario']);
    $id_mito = intval($_POST['id_mito']);
    
    if (!empty($respuesta)) {
        $sql = "INSERT INTO Respuestas (Descripcion, Fecha, id_comentario, id_usuario) VALUES (?, NOW(), ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $respuesta, $id_comentario, $id_usuario);
        $stmt->execute();
        header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id_mito);
        exit();
    }
}

// Obtener mito
$id = intval($_GET['id'] ?? 0);
$sql = "SELECT m.*, p.Nombre as Provincia FROM MitoLeyenda m 
        INNER JOIN Provincias p ON m.id_provincia = p.id_provincia 
        WHERE m.id_mitooleyenda = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$mito = $stmt->get_result()->fetch_assoc();

if (!$mito) {
    die("Mito no encontrado");
}

// Obtener comentarios con respuestas
$sql = "SELECT c.*, u.Username, u.Nombre, u.foto FROM Comentarios c
        INNER JOIN Usuarios u ON c.id_usuario = u.id_usuario
        WHERE c.id_mitooleyenda = ? ORDER BY c.Fecha DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$comentarios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Obtener todas las respuestas
$respuestas_por_comentario = [];
if (count($comentarios) > 0) {
    $ids_comentarios = array_column($comentarios, 'id_comentario');
    $placeholders = implode(',', array_fill(0, count($ids_comentarios), '?'));
    $sql = "SELECT r.*, u.Username, u.Nombre, u.foto FROM Respuestas r
            INNER JOIN Usuarios u ON r.id_usuario = u.id_usuario
            WHERE r.id_comentario IN ($placeholders) ORDER BY r.Fecha ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(str_repeat('i', count($ids_comentarios)), ...$ids_comentarios);
    $stmt->execute();
    $respuestas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    foreach ($respuestas as $resp) {
        $respuestas_por_comentario[$resp['id_comentario']][] = $resp;
    }
}

// Mitos relacionados
$sql = "SELECT m.*, p.Nombre as Provincia FROM MitoLeyenda m
        INNER JOIN Provincias p ON m.id_provincia = p.id_provincia
        WHERE m.id_mitooleyenda != ? ORDER BY RAND() LIMIT 3";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$relacionados = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

function tiempoLectura($texto) {
    return ceil(str_word_count(strip_tags($texto)) / 200);
}

function formatearFecha($fecha) {
    return date('d/m/Y H:i', strtotime($fecha));
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($mito['Titulo']) ?> - LegendAR</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; background: #f8f9fa; color: #333; }
    
    header { display: flex; justify-content: space-between; align-items: center; padding: 15px 20px; 
             background: white; border-bottom: 1px solid #ddd; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .usuario { display: flex; align-items: center; gap: 10px; }
    .profile-pic { width: 40px; height: 40px; border-radius: 50%; background: #ccc; 
                   display: flex; align-items: center; justify-content: center; overflow: hidden; }
    .profile-pic img { width: 100%; height: 100%; object-fit: cover; }
    .botones { display: flex; gap: 10px; }
    .btn { background: #ff7b00; border: none; padding: 10px 20px; color: white; border-radius: 20px; 
           cursor: pointer; font-weight: bold; text-decoration: none; transition: all 0.3s; }
    .btn:hover { background: #e66d00; }
    
    .contenedor { max-width: 900px; margin: 20px auto; padding: 0 15px; }
    .imagen-principal { width: 100%; height: 250px; background: linear-gradient(135deg, #2c3e50 0%, #8b4513 100%);
                        display: flex; justify-content: center; align-items: center; border-radius: 10px; 
                        margin-bottom: 20px; overflow: hidden; }
    .imagen-principal img { max-width: 100%; max-height: 100%; object-fit: contain; }
    
    h1 { font-size: 28px; margin-bottom: 10px; }
    .meta { font-size: 14px; color: #666; margin-bottom: 20px; }
    .card { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
    .card h2 { font-size: 20px; margin-bottom: 15px; color: #1d2e42; }
    .card p { line-height: 1.6; margin-bottom: 15px; }
    
    /* COMENTARIOS */
    .form-comentario { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
    .form-comentario textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; 
                                font-family: Arial; font-size: 14px; resize: vertical; min-height: 80px; }
    .form-comentario textarea:focus { outline: none; border-color: #ff7b00; }
    .form-comentario button { background: #ff7b00; color: white; border: none; padding: 10px 20px; 
                              border-radius: 5px; cursor: pointer; font-weight: bold; margin-top: 10px; }
    
    .comentario { background: white; padding: 15px; border-radius: 8px; border-left: 4px solid #ff7b00; 
                  margin-bottom: 15px; }
    .comentario-header { display: flex; gap: 12px; margin-bottom: 10px; }
    .avatar { width: 40px; height: 40px; border-radius: 50%; background: #ccc; 
              display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; }
    .avatar img { width: 100%; height: 100%; object-fit: cover; }
    .comentario-info { flex: 1; }
    .comentario-usuario { font-weight: bold; color: #1d2e42; }
    .comentario-fecha { font-size: 12px; color: #999; margin-left: 10px; }
    .comentario-texto { color: #555; line-height: 1.5; margin-bottom: 10px; }
    
    .btn-responder { background: none; border: none; color: #ff7b00; cursor: pointer; 
                     font-size: 13px; font-weight: bold; padding: 5px 0; }
    .btn-responder:hover { text-decoration: underline; }
    
    .respuestas { margin-left: 52px; margin-top: 10px; border-left: 2px solid #e0e0e0; padding-left: 15px; }
    .respuesta { background: #f8f9fa; padding: 12px; border-radius: 6px; margin-bottom: 10px; }
    .respuesta-header { display: flex; gap: 10px; align-items: center; margin-bottom: 8px; }
    .respuesta .avatar { width: 32px; height: 32px; }
    .respuesta-texto { font-size: 14px; color: #555; }
    .contador-respuestas { font-size: 13px; color: #666; margin-top: 5px; }
    
    .form-respuesta { margin-left: 52px; margin-top: 10px; display: none; }
    .form-respuesta.activo { display: block; }
    .form-respuesta textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; 
                               font-size: 13px; min-height: 60px; }
    .form-respuesta .botones-respuesta { display: flex; gap: 10px; margin-top: 8px; }
    .form-respuesta button { padding: 8px 15px; font-size: 13px; border-radius: 5px; cursor: pointer; 
                             font-weight: bold; }
    .btn-enviar { background: #ff7b00; color: white; border: none; }
    .btn-cancelar { background: #e0e0e0; color: #333; border: none; }
    
    /* RELATOS */
    .relatos { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; }
    .relato { background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); 
              text-decoration: none; color: inherit; transition: all 0.3s; }
    .relato:hover { transform: translateY(-3px); box-shadow: 0 6px 15px rgba(0,0,0,0.15); }
    .relato-imagen { width: 100%; height: 140px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                     display: flex; align-items: center; justify-content: center; }
    .relato-imagen img { width: 100%; height: 100%; object-fit: contain; }
    .relato-contenido { padding: 12px; }
    .relato-titulo { font-size: 14px; font-weight: bold; margin-bottom: 6px; }
    .relato-descripcion { font-size: 12px; color: #666; margin-bottom: 8px; }
    .relato-meta { font-size: 11px; color: #999; display: flex; justify-content: space-between; }
    .relato-region { background: #ff7b00; color: white; padding: 2px 8px; border-radius: 10px; }
    
    @media (max-width: 768px) {
      .relatos { grid-template-columns: 1fr; }
      header { flex-direction: column; gap: 10px; }
      .botones { width: 100%; }
      .btn { width: 100%; }
    }
  </style>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</head>
<body>
  <header>
    <div class="usuario">
      <div class="profile-pic">
        <?php if ($fotoperfil): ?>
          <img src="usuarios/<?= htmlspecialchars($fotoperfil) ?>" alt="Perfil">
        <?php else: ?>
          <i class="fas fa-user"></i>
        <?php endif; ?>
      </div>
      <span><?= htmlspecialchars($username) ?></span>
    </div>
    <div class="botones">
      <button class="btn" onclick="generarPDF()">Generar PDF</button>
      <a href="mapa.php" class="btn">Explorar mapa</a>
    </div>
  </header>

  <div class="contenedor">
    <div class="imagen-principal">
      <?php if (!empty($mito['imagen'])): ?>
        <img src="mitos/<?= htmlspecialchars($mito['imagen']) ?>" alt="<?= htmlspecialchars($mito['Titulo']) ?>">
      <?php else: ?>
        <span style="font-size: 72px;">📖</span>
      <?php endif; ?>
    </div>

    <h1><?= htmlspecialchars($mito['Titulo']) ?></h1>
    <div class="meta">
      <?= htmlspecialchars($mito['Provincia']) ?> · <?= tiempoLectura($mito['Descripcion']) ?> min lectura
    </div>

    <div class="card">
      <?php 
        foreach (explode("\n\n", $mito['Descripcion']) as $parrafo) {
          if (trim($parrafo)) echo "<p>" . nl2br(htmlspecialchars($parrafo)) . "</p>";
        }
      ?>
    </div>

    <div class="card">
      <h2><i class="fas fa-comments"></i> Comentarios (<?= count($comentarios) ?>)</h2>
      
      <form method="POST" class="form-comentario">
        <textarea name="nuevo_comentario" placeholder="Comparte tu opinión sobre este mito..." required></textarea>
        <input type="hidden" name="id_mito" value="<?= $mito['id_mitooleyenda'] ?>">
        <button type="submit">Publicar comentario</button>
      </form>

      <?php foreach ($comentarios as $com): ?>
        <div class="comentario">
          <div class="comentario-header">
            <div class="avatar">
              <?php if ($com['foto']): ?>
                <img src="usuarios/<?= htmlspecialchars($com['foto']) ?>" alt="Avatar">
              <?php else: ?>
                <i class="fas fa-user"></i>
              <?php endif; ?>
            </div>
            <div class="comentario-info">
              <span class="comentario-usuario"><?= htmlspecialchars($com['Username']) ?></span>
              <span class="comentario-fecha"><?= formatearFecha($com['Fecha']) ?></span>
            </div>
          </div>
          <p class="comentario-texto"><?= nl2br(htmlspecialchars($com['Descripcion'])) ?></p>
          
          <?php 
            $respuestas = $respuestas_por_comentario[$com['id_comentario']] ?? [];
            $num_respuestas = count($respuestas);
          ?>
          
          <button class="btn-responder" onclick="toggleRespuesta(<?= $com['id_comentario'] ?>)">
            <i class="fas fa-reply"></i> Responder
            <?php if ($num_respuestas > 0): ?>
              · <?= $num_respuestas ?> respuesta<?= $num_respuestas > 1 ? 's' : '' ?>
            <?php endif; ?>
          </button>

          <?php if ($num_respuestas > 0): ?>
            <div class="respuestas">
              <?php foreach ($respuestas as $resp): ?>
                <div class="respuesta">
                  <div class="respuesta-header">
                    <div class="avatar">
                      <?php if ($resp['foto']): ?>
                        <img src="usuarios/<?= htmlspecialchars($resp['foto']) ?>" alt="Avatar">
                      <?php else: ?>
                        <i class="fas fa-user"></i>
                      <?php endif; ?>
                    </div>
                    <div>
                      <span class="comentario-usuario"><?= htmlspecialchars($resp['Username']) ?></span>
                      <span class="comentario-fecha"><?= formatearFecha($resp['Fecha']) ?></span>
                    </div>
                  </div>
                  <p class="respuesta-texto"><?= nl2br(htmlspecialchars($resp['Descripcion'])) ?></p>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <form method="POST" class="form-respuesta" id="form-resp-<?= $com['id_comentario'] ?>">
            <textarea name="nueva_respuesta" placeholder="Escribe tu respuesta..." required></textarea>
            <input type="hidden" name="id_comentario" value="<?= $com['id_comentario'] ?>">
            <input type="hidden" name="id_mito" value="<?= $mito['id_mitooleyenda'] ?>">
            <div class="botones-respuesta">
              <button type="submit" class="btn-enviar">Enviar</button>
              <button type="button" class="btn-cancelar" onclick="toggleRespuesta(<?= $com['id_comentario'] ?>)">Cancelar</button>
            </div>
          </form>
        </div>
      <?php endforeach; ?>

      <?php if (count($comentarios) == 0): ?>
        <p style="text-align: center; color: #999; padding: 20px;">No hay comentarios aún. ¡Sé el primero en comentar!</p>
      <?php endif; ?>
    </div>

    <?php if (count($relacionados) > 0): ?>
      <div class="card">
        <h2>Relatos relacionados</h2>
        <div class="relatos">
          <?php foreach ($relacionados as $rel): ?>
            <a href="mitos.php?id=<?= $rel['id_mitooleyenda'] ?>" class="relato">
              <div class="relato-imagen">
                <?php if (!empty($rel['imagen'])): ?>
                  <img src="mitos/<?= htmlspecialchars($rel['imagen']) ?>" alt="<?= htmlspecialchars($rel['Titulo']) ?>">
                <?php else: ?>
                  <span style="font-size: 48px;">📖</span>
                <?php endif; ?>
              </div>
              <div class="relato-contenido">
                <div class="relato-titulo"><?= htmlspecialchars($rel['Titulo']) ?></div>
                <div class="relato-descripcion">
                  <?= htmlspecialchars(mb_substr(strip_tags($rel['Descripcion']), 0, 100)) ?>...
                </div>
                <div class="relato-meta">
                  <span><?= tiempoLectura($rel['Descripcion']) ?> min</span>
                  <span class="relato-region"><?= htmlspecialchars($rel['Provincia']) ?></span>
                </div>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <script>
    function toggleRespuesta(id) {
      const form = document.getElementById('form-resp-' + id);
      form.classList.toggle('activo');
      if (form.classList.contains('activo')) {
        form.querySelector('textarea').focus();
      }
    }

    const pdfData = {
      title: <?= json_encode($mito['Titulo']) ?>,
      imagen: <?= json_encode(!empty($mito['imagen']) ? 'mitos/' . $mito['imagen'] : '') ?>,
      texto: <?= json_encode(strip_tags($mito['Descripcion'])) ?>
    };

    async function generarPDF() {
      const { jsPDF } = window.jspdf;
      const doc = new jsPDF();
      const pageWidth = doc.internal.pageSize.getWidth();
      const margin = 20;
      let y = 20;

      doc.setFontSize(18);
      doc.text(pdfData.title, pageWidth / 2, y, { align: 'center' });
      y += 15;

      if (pdfData.imagen) {
        try {
          const img = await loadImage(pdfData.imagen);
          if (img) {
            const imgW = Math.min(170, img.width * 0.26);
            const imgH = (img.height / img.width) * imgW;
            doc.addImage(img.data, img.type, margin, y, imgW, imgH);
            y += imgH + 10;
          }
        } catch(e) { console.log('Error imagen:', e); }
      }

      doc.setFontSize(11);
      const lines = doc.splitTextToSize(pdfData.texto, 170);
      lines.forEach(line => {
        if (y > 270) { doc.addPage(); y = 20; }
        doc.text(line, margin, y);
        y += 6;
      });

      doc.save(pdfData.title.replace(/[^a-z0-9]/gi, '_') + '.pdf');
    }

    function loadImage(url) {
      return new Promise((resolve, reject) => {
        const img = new Image();
        img.crossOrigin = 'anonymous';
        img.onload = () => {
          const canvas = document.createElement('canvas');
          canvas.width = img.naturalWidth;
          canvas.height = img.naturalHeight;
          canvas.getContext('2d').drawImage(img, 0, 0);
          resolve({ 
            data: canvas.toDataURL('image/jpeg'), 
            type: 'JPEG',
            width: img.naturalWidth,
            height: img.naturalHeight
          });
        };
        img.onerror = () => resolve(null);
        img.src = url;
      });
    }
  </script>
</body>
</html>