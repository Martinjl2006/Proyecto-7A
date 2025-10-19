<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once "main.php"; // Asegurate que aquí se conecta a la base de datos

// Verificar si el usuario está logueado
$usuarioLogueado = isset($_SESSION["username"]) && isset($_SESSION["id_usuario"]);
$nombreUsuario = $usuarioLogueado ? htmlspecialchars($_SESSION["username"]) : null;
$foto = ($usuarioLogueado && !empty($_SESSION["foto"])) ? htmlspecialchars($_SESSION["foto"]) : null;

// FUNCIÓN AUXILIAR: Obtener todos los mitos agrupados por provincia desde la BD
function obtenerMitosPorProvincia() {
    global $conn; // Variable de conexión desde main.php
    
    $mitosByProvince = [];
    
    try {
        // Consulta que une Mitoleyenda con Provincias
        $sql = "SELECT 
                    m.id_mitooleyenda,
                    m.Titulo,
                    m.textobreve,
                    m.Descripcion,
                    m.imagen,
                    m.tipo,
                    p.nombre AS provincia_nombre
                FROM Mitoleyenda m
                INNER JOIN Provincias p ON m.id_provincia = p.id_provincia
                ORDER BY p.nombre ASC, m.Titulo ASC";
        
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $provincia = $row['provincia_nombre'];
                
                if (!isset($mitosByProvince[$provincia])) {
                    $mitosByProvince[$provincia] = [];
                }
                
                // Usar textobreve si existe, sino usar descripcion truncada
                $descripcion = !empty($row['textobreve']) 
                    ? $row['textobreve'] 
                    : substr($row['Descripcion'], 0, 150) . '...';
                
                $mitosByProvince[$provincia][] = [
                    'id' => $row['id_mitooleyenda'],
                    'titulo' => htmlspecialchars($row['Titulo']),
                    'descripcion' => htmlspecialchars($descripcion),
                    'tipo' => htmlspecialchars($row['tipo'] ?? ''),
                    'imagen' => htmlspecialchars($row['imagen'] ?? ''),
                    'link' => 'mitos.php?id=' . $row['id_mitooleyenda']
                ];
            }
        }
    } catch (Exception $e) {
        error_log("Error al obtener mitos: " . $e->getMessage());
    }
    
    return $mitosByProvince;
}

// Obtener mitos desde BD
$mitosPorProvincia = obtenerMitosPorProvincia();
$mitosPorProvinciaJSON = json_encode($mitosPorProvincia);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <title>Mapa Interactivo de Mitos y Leyendas</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 0;
      background-color: #f4f4f4;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    /* --- Estilos del panel lateral emergente --- */
    /* tarjetas de mito */
    .mito-card{
      background:#fff;
      border:1px solid #e5e7eb;
      border-radius:12px;
      padding:12px 14px;
      margin-bottom:12px;
      box-shadow:0 2px 6px rgba(0,0,0,.06);
      cursor:pointer;
      transition:transform .15s ease, box-shadow .15s ease;
      text-decoration: none;
      color: inherit;
      display:block;
    }
    .mito-card:hover{ transform:translateY(-2px); box-shadow:0 6px 14px rgba(0,0,0,.12); }
    .mito-card h4{
      margin:0 0 6px 0;
      font-size:1rem;
      font-weight:700;
      color:#1E3A8A;
    }
    .mito-card p{
      margin:0;
      font-size:.9rem;
      line-height:1.35rem;
      color:#4B5563;
    }

    /* panel-mitos: fijado a la izquierda, fuera de la vista al iniciar */
    #panel-mitos {
      position: fixed;
      top: 80px; /* bajo el header */
      left: -380px; /* oculto al inicio */
      width: 360px;
      height: calc(100% - 120px); /* deja espacio para header/footer */
      background-color: #f9f9f9;
      border-right: 2px solid #ddd;
      box-shadow: 2px 0 8px rgba(0,0,0,0.12);
      overflow-y: auto;
      transition: left 0.28s cubic-bezier(.2,.9,.2,1);
      padding: 18px;
      z-index: 1400;
      border-radius: 0 8px 8px 0;
    }
    /* clase que deja el panel visible */
    #panel-mitos.open { left: 16px; }

    .panel-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; }
    #btn-toggle { background:#ef4444; color:white; border:none; padding:6px 10px; border-radius:6px; cursor:pointer; }
    #btn-toggle:hover { background:#dc2626; }

    :root {
      --azul-profundo: #1d2e42;
      --fondo-textura: #f2efe9;
      --texto-gris: #555555;
      --sombra: rgba(0, 0, 0, 0.05);
    }

    header {
      background: var(--fondo-textura);
      padding: 20px 32px;
      display: flex;
      width: 97%;
      justify-content: space-between;
      align-items: center;
      border-bottom: 2px solid #ddd;
      box-shadow: 0 2px 10px var(--sombra);
    }

    html { overflow-x: hidden; }

    .logo { display: flex; align-items: center; }

    .logo img { height: 42px; margin-right: 12px; }

    .logo span {
      font-size: 1.9rem;
      font-weight: 800;
      color: var(--azul-profundo);
    }

    nav a {
      color: var(--azul-profundo);
      margin-left: 20px;
      text-decoration: none;
      font-weight: 500;
      transition: color 0.3s ease;
    }

    nav a:hover { color: #000; }

    .login-button {
      background-color: #1E3A8A;
      color: #FFFFFF;
      font-size: 1rem;
      font-weight: 500;
      padding: 8px 16px;
      border: none;
      border-radius: 9999px;
      cursor: pointer;
      transition: background-color 0.3s ease;
      margin-left: 20px;
    }

    .login-button:hover {
      background-color: #2563EB;
    }

    .main-container {
      display: flex;
      width: 97%;
      margin: 1rem auto;
    }

    #map {
      flex: 1;
      height: 800px;
      border-radius: 10px;
      box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.3);
    }

    .cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 30px;
      margin-bottom: 60px;
      max-width: 1200px;
      padding: 0 20px;
    }

    .card {
      background: white;
      border-radius: 18px;
      padding: 30px 20px;
      border: 1px solid #ddd;
      box-shadow: 0 6px 14px rgba(0,0,0,0.05);
      transition: all 0.3s ease;
    }

    .card:hover {
      transform: translateY(-6px);
      box-shadow: 0 12px 24px rgba(0,0,0,0.1);
    }

    .card-icon { font-size: 2.2rem; margin-bottom: 12px; }

    .card-title {
      font-size: 1.25rem;
      font-weight: 700;
      color: var(--azul-profundo);
      margin-bottom: 10px;
    }

    .card-text { font-size: 0.95rem; color: var(--texto-gris); }

    footer {
      margin-top: 60px;
      padding: 20px;
      width: 97%;
      background: #eceae4;
      text-align: center;
      font-size: 0.9rem;
      color: #555;
    }

    .info {
      padding: 8px 12px;
      background: rgba(255,255,255,0.9);
      box-shadow: 0 0 15px rgba(0,0,0,0.2);
      border-radius: 6px;
      font: 14px/1.4 Arial, sans-serif;
    }
    .info h4 { margin: 0 0 6px; color: #777; font-size: 16px; }

    .legend {
      line-height: 18px;
      color: #555;
      background: rgba(255,255,255,0.9);
      padding: 8px 12px;
      border-radius: 6px;
      box-shadow: 0 0 15px rgba(0,0,0,0.2);
    }
    .legend i {
      width: 18px;
      height: 18px;
      float: left;
      margin-right: 8px;
      opacity: 0.7;
    }

  </style>
</head>
<body>
  <header>
    <div class="logo">
      <img src="logo-removebg-preview-2.png" alt="logo"/>
      <span>leyendAR</span>
    </div>
    <nav>
<?php if ($usuarioLogueado): ?>
  <div style="display: flex; align-items: center; gap: 12px;">
    <?php
      $src = null;
      if (!empty($foto)) {
          $fotoNorm = str_replace('\\', '/', $foto);
          $dir  = dirname($fotoNorm);
          $file = basename($fotoNorm);
          $encFile = rawurlencode($file);

          $fsCandidates = [
              rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . ltrim($fotoNorm, '/'),
              __DIR__ . '/' . $fotoNorm,
              __DIR__ . '/usuarios/' . $file,
              rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/usuarios/' . $file
          ];

          foreach ($fsCandidates as $fs) {
              if (file_exists($fs)) {
                  $docroot = rtrim($_SERVER['DOCUMENT_ROOT'], '/');
                  if (strpos($fs, $docroot) === 0) {
                      $web = substr($fs, strlen($docroot));
                      $web = str_replace($file, $encFile, $web);
                      $src = $web;
                  } else {
                      if ($dir === '.' || $dir === '/') {
                          $src = $encFile;
                      } else {
                          $src = $dir . '/' . $encFile;
                      }
                  }
                  break;
              }
          }

          if (!$src) {
              if ($dir === '.' || $dir === '/') {
                  $src = $encFile;
              } else {
                  $src = $dir . '/' . $encFile;
              }
          }
      }
    ?>

    <?php if (!empty($src)): ?>
      <img src="<?php echo htmlspecialchars($src); ?>" width="50" alt="Foto de perfil" style="object-fit:cover;border-radius:50%">
    <?php else: ?>
      <div style="height: 40px; width: 40px; border-radius: 50%; background-color: #ccc; display: flex; align-items: center; justify-content: center; font-weight: bold;">
        <?= strtoupper(substr($nombreUsuario, 0, 1)) ?>
      </div>
    <?php endif; ?>

    <span style="font-weight: 600;"><?= $nombreUsuario ?></span>
    <a href="logout.php" style="margin-left: 10px; color: #1E3A8A; text-decoration: underline;">Cerrar sesión</a>
  </div>
<?php else: ?>
  <a href="inicio.html" class="login-button">Iniciar sesión</a>
<?php endif; ?>
</nav>

  </header>

  <div class="main-container">
    <div id="map"></div>
  </div>

  <!-- Panel lateral: UNA sola vez -->
  <div id="panel-mitos" aria-hidden="true">
    <div class="panel-header">
      <h3 id="titulo-panel" style="margin:0">Mitos</h3>
      <button id="btn-toggle" title="Cerrar panel">Cerrar</button>
    </div>
    <div id="contenido-mitos"></div>
  </div>

  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
          integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
  <script>
    /* ========================
       DATOS DESDE LA BASE DE DATOS
       ======================== */
    
    // Los mitos vienen desde PHP en JSON
    const mitosByProvince = <?php echo $mitosPorProvinciaJSON; ?>;
    
    // Valores por provincia (pueden venir de BD también si lo necesitas)
    const valuesByProvince = {};
    Object.keys(mitosByProvince).forEach(provincia => {
      valuesByProvince[provincia] = mitosByProvince[provincia].length;
    });

    /* ========================
       Normalización y helpers
       ======================== */

    function slugify(str){
      return String(str || '')
        .normalize('NFD').replace(/[\u0300-\u036f]/g,'')
        .toLowerCase().replace(/[^a-z0-9]+/g,'-')
        .replace(/(^-|-$)/g,'');
    }

    const canonicalNames = Array.from(new Set(Object.keys(mitosByProvince)));
    const normalizedToCanonical = {};
    canonicalNames.forEach(n => { normalizedToCanonical[n.normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase().trim()] = n; });

    function normalizeKey(s) {
      if (!s && s !== 0) return '';
      return String(s)
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9\s]/g, '')
        .replace(/\s+/g, ' ')
        .trim();
    }

    function resolveProvinceName(props) {
      if (!props) return null;
      const candidates = [
        props.name, props.nombre, props.NOMBRE, props.provincia, props.PROVINCIA,
        props.NOM, props.NOM_PROV, props.NOMBRE_PROV, props.admin, props.PROVINCE, props.PROV
      ];
      for (const c of candidates) {
        if (!c) continue;
        const norm = normalizeKey(c);
        if (normalizedToCanonical[norm]) return normalizedToCanonical[norm];
      }
      for (const c of candidates) {
        if (!c) continue;
        const normC = normalizeKey(c);
        for (const normKey in normalizedToCanonical) {
          if (normKey.includes(normC) || normC.includes(normKey)) {
            return normalizedToCanonical[normKey];
          }
        }
      }
      return props.name || props.nombre || props.NOMBRE || null;
    }

    /* ========================
       Inicialización del mapa
       ======================== */

    const map = L.map('map', {
      minZoom: 3,
      maxZoom: 12,
      attributionControl: true
    }).setView([-38.4161, -63.6167], 4);

    map.setMaxBounds([
      [-55, -75],
      [-20, -50]
    ]);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a> contributors'
    }).addTo(map);

    var blackTiles = L.tileLayer('', {
      attribution: '',
      minZoom: 0,
      maxZoom: 20
    });
    blackTiles.createTile = function(coords, done) {
      var tile = document.createElement('div');
      tile.style.width = tile.style.height = this.getTileSize().x + "px";
      tile.style.background = "black";
      done(null, tile);
      return tile;
    };
    blackTiles.addTo(map);

    function getColor(d) {
      return d > 10 ? '#800026' :
             d > 5  ? '#BD0026' :
             d > 3  ? '#E31A1C' :
             d > 1  ? '#FC4E2A' :
                      '#FFEDA0';
    }

    function style(feature) {
      const nombreCanonical = feature.properties._provName || feature.properties.name || feature.properties.provincia;
      const cantidad = (mitosByProvince[nombreCanonical] && mitosByProvince[nombreCanonical].length) || 0;
      return {
        weight: 1,
        opacity: 1,
        color: '#FFFFFF',
        dashArray: '3',
        fillOpacity: 0.8,
        fillColor: getColor(cantidad)
      };
    }

    /* ========================
       Control informativo
       ======================== */
    const info = L.control();

    info.onAdd = function () {
      this._div = L.DomUtil.create('div', 'info');
      this.update();
      return this._div;
    };

    info.update = function (props) {
      const title = '<h4>Argentina — Indicador por provincia</h4>';
      if (!props) {
        this._div.innerHTML = title + 'Pasá el mouse por una provincia';
      } else {
        const nombre = props._provName || props.name || props.provincia || 'Provincia';
        const cantidad = (mitosByProvince[nombre] && mitosByProvince[nombre].length) || 0;
        this._div.innerHTML = `${title}<b>${nombre}</b><br/>Mitos: <b>${cantidad}</b>`;
      }
    };

    info.addTo(map);

    /* ========================
       Panel lateral: DOM refs y funciones
       ======================== */
    const panel = document.getElementById('panel-mitos');
    const contenidoMitos = document.getElementById('contenido-mitos');
    const btnToggle = document.getElementById('btn-toggle');

    function openPanel() {
      if (!panel) return;
      panel.classList.add('open');
      panel.setAttribute('aria-hidden', 'false');
      setTimeout(()=> map.invalidateSize(), 260);
    }
    function closePanel() {
      if (!panel) return;
      panel.classList.remove('open');
      panel.setAttribute('aria-hidden', 'true');
      setTimeout(()=> map.invalidateSize(), 200);
    }

    if (btnToggle) btnToggle.addEventListener('click', closePanel);

    function renderMitosDeProvincia(nombreProv){
      const lista = mitosByProvince[nombreProv] || [];
      const html = (lista.length ? lista.map(m => `
        <a class="mito-card" href="${m.link}" aria-label="${m.titulo}">
          <h4>${m.titulo}</h4>
          <p>${m.descripcion}</p>
        </a>
      `).join('') : `<p>No hay mitos cargados para esta provincia.</p>`);

      const tituloEl = document.getElementById('titulo-panel');
      if (tituloEl) tituloEl.textContent = nombreProv || 'Mitos';

      if (contenidoMitos) contenidoMitos.innerHTML = html;
      openPanel();
      panel.scrollTop = 0;
    }

    /* ========================
       Interacción con GeoJSON
       ======================== */
    let geojsonLayer = null;

    function highlightFeature(e) {
      const layer = e.target;
      layer.setStyle({
        weight: 3,
        color: '#666',
        dashArray: '',
        fillOpacity: 0.85
      });
      if (!L.Browser.ie && !L.Browser.opera && !L.Browser.edge) {
        layer.bringToFront();
      }
      info.update(layer.feature.properties);
    }

    function resetHighlight(e) {
      if (geojsonLayer) geojsonLayer.resetStyle(e.target);
      info.update();
    }

    function zoomToFeature(e) {
      try { map.fitBounds(e.target.getBounds(), { maxZoom: 8 }); } catch (err) { }
      const props = e.target.feature && e.target.feature.properties || {};
      const nombre = props._provName || resolveProvinceName(props) || props.name || props.provincia || 'Provincia';
      renderMitosDeProvincia(nombre);
      info.update(props);
    }

    function onEachFeature(feature, layer) {
      layer.on({
        mouseover: highlightFeature,
        mouseout: resetHighlight,
        click: zoomToFeature
      });
      const nombre = feature.properties._provName || feature.properties.name || feature.properties.provincia || 'Provincia';
      const cantidad = mitosByProvince[nombre] ? mitosByProvince[nombre].length : 0;
      layer.bindTooltip(`${nombre}: ${cantidad}`, { sticky: true });
    }

    /* ========================
       Carga GeoJSON
       ======================== */
    fetch('argentina-provincias.geojson')
      .then(r => {
        if (!r.ok) throw new Error('status ' + r.status);
        return r.json();
      })
      .then(data => {
        data.features.forEach(f => {
          const props = f.properties || {};
          const resolved = resolveProvinceName(props);
          props._provName = resolved || (props.name || props.provincia || props.NOMBRE || '');
          const cantidad = (mitosByProvince[props._provName] && mitosByProvince[props._provName].length) || 0;
          props._valueForChoro = cantidad;
          f.properties = props;
        });

        geojsonLayer = L.geoJson(data, {
          style,
          onEachFeature
        }).addTo(map);

        try { map.fitBounds(geojsonLayer.getBounds(), { padding: [10, 10] }); } catch(e){}

        const legend = L.control({ position: 'bottomright' });
        legend.onAdd = function () {
          const div = L.DomUtil.create('div', 'legend');
          const grades = [0, 1, 3, 5, 10];
          let labels = [];
          for (let i = 0; i < grades.length; i++) {
            const from = grades[i];
            const to = grades[i + 1];
            const color = getColor(from + 0.01);
            labels.push(`<i style="background:${color}"></i> ${from}${to ? '&ndash;' + to : '+'}`);
          }
          div.innerHTML = labels.join('<br>');
          return div;
        };
        legend.addTo(map);
      })
      .catch(err => {
        console.error('Error al cargar el GeoJSON:', err);
        alert('No se pudo cargar "argentina-provincias.geojson". Verificá la ruta y la consola.');
      });

    /* ========================
       Función pública para agregar mitos dinámicamente
       ======================== */
    function agregarMito(provincia, mitoObj) {
      if (!provincia || !mitoObj) return false;
      const canon = normalizeKey(provincia);
      const resolved = normalizedToCanonical[canon] || provincia;
      if (!mitosByProvince[resolved]) mitosByProvince[resolved] = [];
      mitosByProvince[resolved].push(mitoObj);
      if (geojsonLayer) geojsonLayer.eachLayer(l => {
        const p = l.feature && l.feature.properties;
        if (!p) return;
        if (p._provName === resolved) {
          l.setStyle(style(l.feature));
        }
      });
      return true;
    }
    window.agregarMito = agregarMito;

  </script>

<div class="cards" style="max-width:1200px; width:97%; margin: 1rem auto;">
  
  <a href="dashboard.php" class="card" style="text-decoration:none; color:inherit;">
    <div class="card-icon">📌</div>
    <div class="card-title">Inicio</div>
    <div class="card-text">Accedé al minimapa, descubrí un mito recomendado y navegá por las opciones principales.</div>
  </a>

  <a href="sobre.html" class="card" style="text-decoration:none; color:inherit;">
    <div class="card-icon">📖</div>
    <div class="card-title">Información sobre el proyecto</div>
    <div class="card-text">Enterate de la finalidad, inspiración y cómo se construyó este mapa interactivo.</div>
  </a>

  <a href="lista_mitos.php" class="card" style="text-decoration:none; color:inherit;">
    <div class="card-icon">👻</div>
    <div class="card-title">Mitos</div>
    <div class="card-text">Explorá la colección completa de leyendas y relatos de cada región de Argentina.</div>
  </a>

  <a href="crearmito.html" class="card" style="text-decoration:none; color:inherit;">
    <div class="card-icon">🔍</div>
    <div class="card-title">Agregar mito</div>
    <div class="card-text">¿Conocés una historia o leyenda local? ¡Sumala al mapa para que otros la descubran!</div>
  </a>

</div>

  <footer>© 2025 Mitos y Leyendas de Argentina</footer>
</body>
</html>