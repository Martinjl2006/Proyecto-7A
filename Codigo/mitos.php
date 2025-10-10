<?php
include 'main.php';

// Verificar si se recibió un ID válido por GET
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);

    // Consultar mito por ID (sin imagen porque tu tabla no la tiene)
    $sql = "SELECT id_mitooleyenda, Titulo, Descripcion FROM MitoLeyenda WHERE id_mitooleyenda = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $mito = $resultado->fetch_assoc();
    } else {
        $error = "No se encontró el mito solicitado.";
    }

    $stmt->close();
} else {
    $error = "No se especificó un mito válido.";
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pantalla de Mito - <?php echo isset($mito) ? htmlspecialchars($mito['Titulo']) : "Error"; ?></title>
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

    header button {
      background: #ff7b00;
      border: none;
      padding: 10px 15px;
      color: white;
      border-radius: 20px;
      cursor: pointer;
      font-weight: bold;
    }

    .contenedor {
      max-width: 900px;
      margin: 20px auto;
      padding: 0 15px;
    }

    .imagen-principal {
      width: 100%;
      height: 250px;
      background: linear-gradient(135deg, #8B0000 0%, #FF6347 100%);
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
    .relato-imagen.bruja { background: linear-gradient(135deg, #E6E6FA 0%, #4169E1 100%); }
    .relato-imagen.tunate { background: linear-gradient(135deg, #DEB887 0%, #8B4513 100%); }
    .relato-imagen.caracana { background: linear-gradient(135deg, #2F4F4F 0%, #696969 100%); }

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
      <a href="..\tcpdf.php".php?id=<?php echo $mito['id_mitooleyenda']; ?>" class="btn-pdf">Descargar PDF</a>
      <button onclick="location.href='mapa.html'">Explorar mapa</button>
    </div>
    <div class="back-btn">
        <a href="dashboard.php" class="btn btn-secondary">⬅ Volver</a>
    </div>
  </header>

  <div class="contenedor">
    <div class="imagen-principal">
      <img src="familiar.jpg" alt="El Familiar">
    </div>

    <h1><?php echo htmlspecialchars($mito['Titulo']); ?></h1>
    <div class="meta">Lorem ipsum dolor sit, amet consectetur adipisicing elit. Minima a, quaerat reprehenderit fugiat exercitationem odio corrupti iusto nihil labore accusantium assumenda, iste hic fugit sint. Dignissimos dolor nihil ex numquam.</div>

    <div class="card">
        <p><?php echo nl2br(htmlspecialchars($mito['Descripcion'])); ?></p>
    </div>

    <div class="card">
      <h2>Fuentes</h2>
      <ul>
        <li>Folklore de los Ingenios Azucareros</li>
        <li>Leyendas de Tucumán - Tradición Oral</li>
        <li>Historia Social del Noroeste Argentino</li>
        <li>Relatos de Trabajadores Rurales</li>
      </ul>
    </div>

    <div class="card">
      <h2>Comentarios</h2>
    </div>

    <div class="card">
      <h2>Relatos relacionados</h2>
      
      <div class="relatos">
         <a href="la-viuda-del-valle.html" class="relato">
          <div class="relato-imagen viuda">
            <img src="viuda-valle.jpg" alt="La Viuda del Valle">
          </div>
          <div class="relato-contenido">
            <div class="relato-titulo">La Viuda del Valle</div>
            <div class="relato-descripcion">
              Alma en pena de mujer abandonada que busca vengarse de los hombres infieles. Su presencia trae desgracias y mala suerte.
            </div>
            <div class="relato-meta">
              <span>4 min lectura</span>
              <span class="relato-region">Jujuy</span>
            </div>
          </div>
        </a>

        <a href="el-lobizon.html" class="relato">
          <div class="relato-imagen lobizon">
            <img src="lobizon.jpeg" alt="El Lobizón">
          </div>
          <div class="relato-contenido">
            <div class="relato-titulo">El Lobizón</div>
            <div class="relato-descripcion">
              Séptimo hijo varón maldito que se transforma en bestia durante las noches de luna llena. Leyenda guaraní.
            </div>
            <div class="relato-meta">
              <span>4 min lectura</span>
              <span class="relato-region">Buenos Aires</span>
            </div>
          </div>
        </a>

        <a href="el-caracana.html" class="relato">
          <div class="relato-imagen caracana">
            <img src="caracana.jpg" alt="El Caracaña">
          </div>
          <div class="relato-contenido">
            <div class="relato-titulo">El Caracaña</div>
            <div class="relato-descripcion">
              Criatura híbrida con cuerpo humano y cabeza de buitre que acecha en los campos formoseños.
            </div>
            <div class="relato-meta">
              <span>4 min lectura</span>
              <span class="relato-region">Formosa</span>
            </div>
          </div>
        </a>
      </div>
    </div>
  </div>

  <footer>
    © LeyendAR — Mitos y Leyendas de Argentina
  </footer>

</body>
</html>