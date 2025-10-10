<?php
session_start();
include 'main.php';

// Inicializar variables
$mito = null;
$error = null;

// Verificar si se recibió un ID válido por GET
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);

    // Consultar mito por ID con JOIN a Provincias
    $sql = "SELECT m.id_mitooleyenda, m.Titulo, m.textobreve, m.Descripcion, m.imagen, p.Nombre as Provincia 
            FROM MitoLeyenda m
            INNER JOIN Provincias p ON m.id_provincia = p.id_provincia
            WHERE m.id_mitooleyenda = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows > 0) {
            $mito = $resultado->fetch_assoc();
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

// Obtener mitos relacionados (3 aleatorios)
$mitosRelacionados = [];
if ($mito) {
    $sqlRelacionados = "SELECT m.id_mitooleyenda, m.Titulo, m.textobreve, m.Descripcion, p.Nombre as Provincia 
                        FROM MitoLeyenda m
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

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $mito ? htmlspecialchars($mito['Titulo']) : "Error"; ?> - Pantalla de Mito</title>
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
    }

    header .usuario {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    header img {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: #ccc;
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
    }

    .card p {
      line-height: 1.6;
      margin-bottom: 15px;
    }

    .card ul {
      line-height: 1.8;
    }

    /* Estilos para la sección de relatos relacionados - diseño horizontal */
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

    /* Placeholder para imágenes con gradientes únicos */
    .relato-imagen.salamanca { background: linear-gradient(135deg, #8B4513 0%, #D2691E 100%); }
    .relato-imagen.fantasma { background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); }
    .relato-imagen.familiar { background: linear-gradient(135deg, #c0392b 0%, #8e44ad 100%); }

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

    /* Responsive */
    @media (max-width: 768px) {
      .relatos {
        flex-direction: column;
        gap: 15px;
      }
      
      .relato-imagen {
        height: 100px;
      }

      header .botones {
        flex-direction: column;
        gap: 5px;
      }

      header button,
      header a.btn-pdf {
        padding: 8px 12px;
        font-size: 12px;
      }
    }
  </style>
</head>
<body>

  <header>
    <div class="usuario">
      <img src="#" alt="Foto usuario">
      <span>Aquí va el nombre de usuario</span>
    </div>
    <div class="botones">
      <a href="tcpdf.php?id=<?php echo $mito ? $mito['id_mitooleyenda'] : ''; ?>" class="btn-pdf" target="_blank">Descargar PDF</a>
      <button onclick="location.href='mapa.html'">Explorar mapa</button>
    </div>
  </header>

  <?php if ($error): ?>
    <div class="contenedor">
      <div class="error">
        <h2>⚠️ Error</h2>
        <p><?php echo htmlspecialchars($error); ?></p>
        <p><a href="mapa.html">← Volver al mapa</a></p>
      </div>
    </div>
  <?php else: ?>

  <div class="contenedor">
    <div class="imagen-principal">
      <?php if (!empty($mito['imagen'])): ?>
        <img src="<?php echo htmlspecialchars($mito['imagen']); ?>" alt="<?php echo htmlspecialchars($mito['Titulo']); ?>">
      <?php else: ?>
        <span style="font-size: 72px;">📖</span>
      <?php endif; ?>
    </div>

    <h1><?php echo htmlspecialchars($mito['Titulo']); ?></h1>
    <div class="meta">
      <?php echo htmlspecialchars($mito['Provincia']); ?> · Mitología Guaraní · <?php echo calcularTiempoLectura($mito['Descripcion']); ?> min
    </div>

    <div class="card">
      <?php 
        // Dividir descripción en párrafos
        $parrafos = explode("\n\n", $mito['Descripcion']);
        foreach ($parrafos as $parrafo) {
          $parrafo = trim($parrafo);
          if (!empty($parrafo)) {
            echo "<p>" . nl2br(htmlspecialchars($parrafo)) . "</p>";
          }
        }
      ?>
    </div>

    <div class="card">
      <h2>Fuentes</h2>
      <ul>
        <li>Mitología Guaraní - Tradiciones Ancestrales</li>
        <li>Folklore Argentino - Leyendas Rurales</li>
        <li>Recopilación de Relatos Populares</li>
      </ul>
    </div>

    <div class="card">
      <h2>Comentarios</h2>
      <p style="color: #999; text-align: center; padding: 20px;">
        Sistema de comentarios próximamente...
      </p>
    </div>

    <?php if (count($mitosRelacionados) > 0): ?>
    <div class="card">
      <h2>Relatos relacionados</h2>
      
      <div class="relatos">
        <?php foreach ($mitosRelacionados as $relacionado): ?>
          <a href="mitos.php?id=<?php echo $relacionado['id_mitooleyenda']; ?>" class="relato">
            <div class="relato-imagen">
              <span style="font-size: 48px;">📖</span>
            </div>
            <div class="relato-contenido">
              <div class="relato-titulo"><?php echo htmlspecialchars($relacionado['Titulo']); ?></div>
              <div class="relato-descripcion">
                <?php 
                  // Usar textobreve o primeros 100 caracteres
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
  </div>

  <?php endif; ?>

  <footer>
    © LeyendAR — Mitos y Leyendas de Argentina
  </footer>

</body>
</html>