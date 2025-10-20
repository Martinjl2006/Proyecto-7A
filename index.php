<?php
// Iniciar sesión al principio del archivo
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include 'Codigo/main.php';

// Verificar si el usuario está logueado

$usuario_logueado = isset($_SESSION["username"]) && isset($_SESSION["id_usuario"]);

if ($usuario_logueado) {
    // Obtenemos los datos directamente desde la sesión
    $id_usuario    = $_SESSION["id_usuario"];
    $username      = htmlspecialchars($_SESSION["username"]);
    $nombre        = htmlspecialchars($_SESSION["nombre"] ?? $_SESSION["username"]);
    

    $stmt_foto = $conn->prepare("SELECT foto FROM Usuarios WHERE id_usuario = ?");
    $stmt_foto->bind_param("i", $id_usuario);
    $stmt_foto->execute();
    $resultado_foto = $stmt_foto->get_result();
    $fila_foto = $resultado_foto->fetch_assoc();
    $fotoperfil = $fila_foto['foto'] ?? null;
} else {
    $id_usuario  = null;
    $username    = "";
    $nombre      = "";
    $fotoperfil  = null;
}


// Debug: agregar información al HTML como comentarios (solo en desarrollo)
$debug_mode = false; // Cambiar a false en producción
$debug_info = "";
if ($debug_mode) {
    $debug_info = "<!--\nDEBUG INFO:\n";
    $debug_info .= "Session Status: " . session_status() . "\n";
    $debug_info .= "Session ID: " . session_id() . "\n";
    $debug_info .= "Usuario logueado: " . ($usuario_logueado ? 'SI' : 'NO') . "\n";
    $debug_info .= "Username: " . $username . "\n";
    $debug_info .= "Nombre: " . $nombre . "\n";
    $debug_info .= "SESSION data: " . print_r($_SESSION, true) . "\n";
    $debug_info .= "-->\n";
}




?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>LeyendAR</title>
  <link rel="stylesheet" href="Codigo/main.css">
  <style>
    /* Estilos para el perfil en header */
    .user-profile {
      display: flex;
      align-items: center;
      gap: 10px;
      text-decoration: none;
      color: black;
      transition: all 0.3s ease;
      background: rgba(255,255,255,0.1);
      padding: 8px 15px;
      border-radius: 25px;
      border: 2px solid rgba(15, 14, 14, 0.56);
      overflow: hidden;
    }
    

    .foto_perfil{
      width: 50px;
      height: 50px;
      border-radius: 50%;
    }

    .user-profile:hover {
      background: rgba(255,255,255,0.2);
      transform: translateY(-1px);
    }
    
    .profile-pic-small {
      width: 35px;
      height: 35px;
      background: linear-gradient(45deg, #667eea, #764ba2);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: bold;
      font-size: 12px;
    }
    
    .user-name {
      font-weight: 600;
      color: black;
    }
    
    .login-btn {
      background: rgba(255,255,255,0.1);
      color: white;
      padding: 10px 20px;
      border-radius: 25px;
      text-decoration: none;
      border: 2px solid rgba(255,255,255,0.3);
      transition: all 0.3s ease;
    }
    
    .login-btn:hover {
      background: rgba(255,255,255,0.2);
      transform: translateY(-1px);
    }
    
    /* Debug info */
    .debug-info {
      position: fixed;
      top: 10px;
      right: 10px;
      background: rgba(0,0,0,0.9);
      color: white;
      padding: 15px;
      border-radius: 8px;
      font-size: 12px;
      z-index: 1000;
      max-width: 300px;
      max-height: 400px;
      overflow-y: auto;
      font-family: monospace;
    }
    
    .debug-toggle {
      position: fixed;
      bottom: 20px;
      right: 20px;
      background: #333;
      color: white;
      border: none;
      padding: 12px;
      border-radius: 50%;
      cursor: pointer;
      z-index: 1001;
      width: 45px;
      height: 45px;
      font-size: 16px;
    }
    
    .welcome-message {
      text-align: center;
      margin-top: 15px;
      padding: 10px;
      background: rgba(102, 126, 234, 0.1);
      border-radius: 10px;
      color: #667eea;
      font-weight: 600;
    }


    
  </style>
</head>
<body>
  <?php echo $debug_info; ?>
  
  <!-- Debug info visible (solo si debug_mode está activo) -->
  <?php if ($debug_mode): ?>
  <div class="debug-info" id="debugInfo" style="display: none;">
    <strong>🔍 Estado de Sesión PHP:</strong><br><br>
    <strong>Usuario logueado:</strong> <?php echo $usuario_logueado ? '✅ SÍ' : '❌ NO'; ?><br>
    <strong>Username:</strong> <?php echo $username ?: 'N/A'; ?><br>
    <strong>Nombre:</strong> <?php echo $nombre ?: 'N/A'; ?><br>
    <strong>Session ID:</strong> <?php echo session_id(); ?><br>
    <strong>Session Status:</strong> <?php echo session_status(); ?><br><br>
    
    <strong>Datos de $_SESSION:</strong><br>
    <pre style="font-size: 10px; background: rgba(255,255,255,0.1); padding: 5px; border-radius: 3px; max-height: 100px; overflow-y: auto;">
    <?php print_r($_SESSION); ?>
    </pre>
    
    <button onclick="toggleDebug()" style="margin-top: 10px; padding: 5px 10px; background: #667eea; color: white; border: none; border-radius: 3px; cursor: pointer;">Cerrar</button>
  </div>
  
  <button class="debug-toggle" onclick="toggleDebug()" title="Información de Debug">🐛</button>
  <?php endif; ?>

  <header>
    <div class="brand">
      <img src="Codigo/logo.jpg" alt="Logo LeyendAR" />
      <span>LeyendAR</span>
    </div>
    <nav>
    <?php if ($usuario_logueado): ?>
      <a href="Codigo/dashboard.php" class="user-profile" title="Ir al Dashboard">
        <div class="profile-pic-small">
          <img src="Codigo/usuarios/<?= htmlspecialchars($fotoperfil) ?>" class="foto_perfil">
        </div>
        <span class="user-name"><?php echo $username; ?></span>
      </a>
    <?php else: ?>
      <a href="Codigo/inicio.html" class="login-btn">Iniciar sesión</a>
    <?php endif; ?>
    </nav>
  </header>
  <section class="hero">
    <h1>
      <?php if ($usuario_logueado): ?>
        ¡Hola, <?php echo $nombre; ?>! 👋
      <?php else: ?>
        Bienvenido a LeyendAR
      <?php endif; ?>
    </h1>


    <?php if ($usuario_logueado): ?>
      <!-- Usuario logueado: ir al dashboard -->
      <button class="btn" onclick="location.href='Codigo/dashboard.php'">Explorar Mapa Completo</button>
      <div class="welcome-message">
        ¡Bienvenido de vuelta! Puedes explorar todas las leyendas disponibles 🎉
      </div>
    <?php else: ?>
      <!-- Usuario no logueado: opciones -->
      <button class="btn" onclick="redirectToMap()">Explorar Mapa</button>
    <?php endif; ?>

    <div class="features">
      <div class="feature">
        <img src="https://img.icons8.com/ios-filled/50/1f2f40/marker.png" alt="Mapa">
        <p>Mapa interactivo de Argentina</p>
      </div>
      <div class="feature">
        <img src="https://img.icons8.com/ios-filled/50/1f2f40/book.png" alt="Leyendas">
        <p>Relatos populares de cada región</p>
      </div>
      <div class="feature">
        <img src="https://img.icons8.com/ios-filled/50/1f2f40/brain.png" alt="Educación">
        <p>Fomento cultural y educativo</p>
      </div>
      <div class="feature">
        <img src="https://img.icons8.com/ios-filled/50/1f2f40/compass.png" alt="Exploración">
        <p>Explorá y descubrí nuevas historias</p>
      </div>
    </div>
  </section>
    
  <footer>
    © 2025 LeyendAR – Proyecto educativo interactivo sobre mitos argentinos.
  </footer>

  <script>
    // Función para mostrar/ocultar debug
    function toggleDebug() {
      const debug = document.getElementById('debugInfo');
      if (debug) {
        debug.style.display = debug.style.display === 'none' ? 'block' : 'none';
      }
    }

    // Función para usuarios no logueados
    function redirectToMap() {
      const userChoice = confirm(
        '¿Quieres explorar el mapa?\n\n' +
        '✅ OK = Iniciar sesión para acceso completo\n' +
        '❌ Cancelar = Ver mapa público (limitado)'
      );
      
      if (userChoice) {
        window.location.href = 'Codigo/inicio.html';
      } else {
        window.location.href = 'Codigo/mapa.html';
      }
    }

    // Verificar estado de la sesión al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
      // Verificar que PHP esté funcionando
      const phpWorking = <?php echo json_encode(true); ?>;
      const userLoggedIn = <?php echo json_encode($usuario_logueado); ?>;
      const username = <?php echo json_encode($username); ?>;
      
      console.log('🔍 Estado de la aplicación:');
      console.log('- PHP funcionando:', phpWorking);
      console.log('- Usuario logueado:', userLoggedIn);
      console.log('- Username:', username);
      
      if (!phpWorking) {
        console.error('❌ PHP no está funcionando correctamente');
        alert('Error: PHP no está configurado correctamente en el servidor');
      } else {
        console.log('✅ PHP funcionando correctamente');
      }
      
      // Si hay un usuario logueado, mostrar confirmación en consola
      if (userLoggedIn) {
        console.log('✅ Sesión activa para:', username);
      } else {
        console.log('ℹ️ No hay sesión activa');
      }
    });

    // Función para cerrar sesión (opcional, puedes agregar un botón más tarde)
    function logout() {
      if (confirm('¿Estás seguro que quieres cerrar sesión?')) {
        window.location.href = 'Codigo/logout.php';
      }
    }
  </script>
</body>
</html>