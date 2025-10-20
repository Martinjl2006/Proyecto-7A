<?php
$host = "localhost";
$user = "root";
$password = "";
$db = "LegendAR";

$conn = new mysqli($host, $user, $password, $db);
if ($conn->connect_error) {
    die("❌ Error de conexión: " . $conn->connect_error);
}

$token = $_REQUEST['token'] ?? ''; // Funciona para GET y POST
$mensaje_error = "";
$mostrar_formulario = false;

if (empty($token)) {
    $mensaje_error = "Token faltante. El enlace puede estar incompleto.";
}

// --- LÓGICA DE POST: Actualizar la contraseña ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clave = $_POST['clave'] ?? '';
    $clave_confirm = $_POST['clave_confirm'] ?? '';

    // Validar token
    $stmt = $conn->prepare("SELECT id_usuario FROM Usuarios WHERE token_verificacion = ? AND token_reset_expira > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        die("<script>alert('❌ Error: El token es inválido o ha expirado. Por favor, solicitá un nuevo enlace.'); window.location='recuperar.html';</script>");
    }
    
    $user = $result->fetch_assoc();
    $id_usuario = $user['id_usuario'];
    $stmt->close();

    // Validar contraseñas
    if ($clave !== $clave_confirm) {
        die("<script>alert('❌ Las contraseñas no coinciden.'); window.history.back();</script>");
    }
    if (strlen($clave) < 8 || !preg_match('/[A-Z]/', $clave) || !preg_match('/[a-z]/', $clave) || !preg_match('/[0-9]/', $clave)) {
        die("<script>alert('❌ La contraseña no cumple los requisitos de seguridad (mín. 8 caracteres, 1 mayúscula, 1 minúscula, 1 número).'); window.history.back();</script>");
    }

    // --- ¡HASHEAR LA CONTRASEÑA! ---
    // Usamos el mismo método que DEBERÍAS usar en registro.php
    $contraseña_hash = $clave;

    // Actualizar BD: Hashear contraseña y limpiar tokens
    $update = $conn->prepare("UPDATE Usuarios SET contrasena = ?, token_verificacion = NULL, token_reset_expira = NULL WHERE id_usuario = ?");
    $update->bind_param("si", $contraseña_hash, $id_usuario);
    $update->execute();
    $update->close();
    $conn->close();

    // Redirigir con éxito
    echo "<script>alert('✅ ¡Contraseña actualizada con éxito! Ya podés iniciar sesión.'); window.location='inicio.html';</script>";
    exit;
}

// --- LÓGICA DE GET: Validar token y mostrar formulario ---
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !$mensaje_error) {
    $stmt = $conn->prepare("SELECT id_usuario FROM Usuarios WHERE token_verificacion = ? AND token_reset_expira > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $mostrar_formulario = true;
    } else {
        $mensaje_error = "Token inválido o expirado. Por favor, solicitá un nuevo enlace de recuperación.";
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Restablecer Contraseña - LeyendAR</title>
  <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* Usamos los mismos estilos de registro.html */
    :root { --azul: #1d2e42; --azul-claro: #3c506d; --beige: #f7f4ee; --blanco: #f4f1ed; }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    html { width: 100%; overflow-x: hidden; }
    body {
      font-family: 'Quicksand', sans-serif;
      background-color: #c49b5b;
      background-image: url('fondo-login.png');
      background-repeat: no-repeat;
      background-position: center center;
      background-attachment: fixed;
      background-size: cover;
      width: 100%;
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      overflow-x: hidden;
      overflow-y: auto;
      position: relative;
      padding: 80px 1rem 40px;
    }
    .register-box {
      background: rgba(244, 241, 237, 0.95);
      padding: 40px 35px;
      border-radius: 24px;
      box-shadow: 0 10px 40px rgba(0,0,0,0.2);
      width: 100%;
      max-width: 420px;
      text-align: center;
      z-index: 1;
      position: relative;
    }
    h2 { font-size: clamp(1.5rem, 5vw, 2rem); color: var(--azul); margin-bottom: 20px; }
    input {
      width: 100%; padding: 12px 14px; margin: 8px 0; border-radius: 12px;
      border: 1px solid #ccc; font-size: clamp(0.9rem, 3vw, 1rem);
      background: #fafafa; transition: 0.3s ease; height: 48px;
    }
    input:focus { outline: none; border-color: var(--azul-claro); box-shadow: 0 0 8px rgba(29, 46, 66, 0.3); }
    .password-container { position: relative; width: 100%; }
    .password-strength { width: 100%; height: 4px; background: #ddd; border-radius: 2px; margin-top: 5px; overflow: hidden; }
    .password-strength-bar { height: 100%; width: 0%; transition: width 0.3s ease, background 0.3s ease; }
    .password-strength-text { font-size: 0.75rem; margin-top: 3px; text-align: left; color: #666; }
    .weak { background: #e74c3c; } .medium { background: #f39c12; } .strong { background: #27ae60; }
    .error-message { color: #e74c3c; font-size: 0.8rem; text-align: left; margin: 3px 0; display: none; }
    button[type="submit"] {
      width: 100%; padding: 14px; margin-top: 20px; border: none; border-radius: 30px;
      background: linear-gradient(135deg, var(--azul), var(--azul-claro));
      color: white; font-size: clamp(0.9rem, 3vw, 1rem); font-weight: 600;
      cursor: pointer; transition: background 0.3s ease, transform 0.2s ease;
      box-shadow: 0 4px 15px rgba(0,0,0,0.2); position: relative; overflow: hidden; height: 50px;
    }
    button[type="submit"]:hover { background: linear-gradient(135deg, var(--azul-claro), var(--azul)); transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.3); }
    p { margin-top: 20px; font-size: clamp(0.85rem, 2.5vw, 0.95rem); }
    a { color: var(--azul-claro); text-decoration: none; } a:hover { text-decoration: underline; }

    /* Estilos de error (como en verificar.php) */
    .error-box {
        background: linear-gradient(135deg, #ffebee, #ffcdd2);
        border-left: 4px solid #f44336;
        border-radius: 12px;
        padding: 20px;
        margin: 25px 0;
        text-align: left;
        color: #c62828;
    }
    .error-box h3 { font-size: 1.1rem; margin-bottom: 10px; display: flex; align-items: center; gap: 10px; }
  </style>
</head>
<body>

  <div class="register-box">
    <?php if ($mostrar_formulario): ?>
      <h2>Restablecer Contraseña</h2>
      <form action="restablecer.php" method="POST" id="resetForm">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
        
        <div class="password-container">
          <input type="password" name="clave" id="password" placeholder="Nueva Contraseña" required>
          <div class="password-strength">
            <div class="password-strength-bar" id="strengthBar"></div>
          </div>
          <div class="password-strength-text" id="strengthText"></div>
          <div class="error-message" id="passwordError"></div>
        </div>

        <div class="password-container">
          <input type="password" name="clave_confirm" id="clave_confirm" placeholder="Confirmar Contraseña" required>
          <div class="error-message" id="confirmError"></div>
        </div>

        <button type="submit">Guardar Contraseña</button>
      </form>
    
    <?php else: ?>
      <h2 style="color: #c62828;">Error</h2>
      <div class="error-box">
          <h3>
              <i class="fas fa-exclamation-triangle"></i>
              No se puede continuar
          </h3>
          <p><?php echo htmlspecialchars($mensaje_error); ?></p>
      </div>
      <p><a href="recuperar.html">Solicitar un nuevo enlace</a></p>
      <p><a href="inicio.html">Ir al inicio</a></p>
    <?php endif; ?>
  </div>

  <script>
    // Solo ejecutar JS si el formulario se muestra
    if (document.getElementById('resetForm')) {
      
      const passwordInput = document.getElementById('password');
      const confirmInput = document.getElementById('clave_confirm');
      const form = document.getElementById('resetForm');
      
      // Validar fortaleza de contraseña
      passwordInput.addEventListener('input', function() {
        const password = this.value;
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');
        const errorDiv = document.getElementById('passwordError');
        
        let strength = 0;
        let feedback = [];
        
        if (password.length >= 8) strength += 25; else feedback.push('mín. 8 caracteres');
        if (/[A-Z]/.test(password)) strength += 25; else feedback.push('una mayúscula');
        if (/[a-z]/.test(password)) strength += 25; else feedback.push('una minúscula');
        if (/[0-9]/.test(password)) strength += 25; else feedback.push('un número');
        
        strengthBar.style.width = strength + '%';
        
        if (strength < 50) {
          strengthBar.className = 'password-strength-bar weak';
          strengthText.textContent = 'Débil'; strengthText.style.color = '#e74c3c';
        } else if (strength < 100) {
          strengthBar.className = 'password-strength-bar medium';
          strengthText.textContent = 'Media'; strengthText.style.color = '#f39c12';
        } else {
          strengthBar.className = 'password-strength-bar strong';
          strengthText.textContent = 'Fuerte'; strengthText.style.color = '#27ae60';
        }
        
        if (feedback.length > 0 && password.length > 0) {
          errorDiv.textContent = 'Falta: ' + feedback.join(', ');
          errorDiv.style.display = 'block';
          this.setCustomValidity('Contraseña insegura');
        } else {
          errorDiv.style.display = 'none';
          this.setCustomValidity('');
        }
      });

      // Validar coincidencia de contraseñas
      function validatePasswordMatch() {
        const errorDiv = document.getElementById('confirmError');
        if (passwordInput.value !== confirmInput.value) {
          errorDiv.textContent = 'Las contraseñas no coinciden';
          errorDiv.style.display = 'block';
          confirmInput.setCustomValidity('Las contraseñas no coinciden');
        } else {
          errorDiv.style.display = 'none';
          confirmInput.setCustomValidity('');
        }
      }
      
      passwordInput.addEventListener('change', validatePasswordMatch);
      confirmInput.addEventListener('input', validatePasswordMatch);

      // Validación final antes de enviar
      form.addEventListener('submit', function(e) {
        const password = passwordInput.value;
        
        if (password.length < 8 || 
            !/[A-Z]/.test(password) || 
            !/[a-z]/.test(password) || 
            !/[0-9]/.test(password)) {
          e.preventDefault();
          alert('La contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula y un número');
          return;
        }

        if (passwordInput.value !== confirmInput.value) {
          e.preventDefault();
          alert('Las contraseñas no coinciden.');
          return;
        }
      });
    }
  </script>
</body>
</html>