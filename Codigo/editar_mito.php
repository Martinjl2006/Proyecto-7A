<?php
session_start();
require_once "main.php";

if (!isset($_SESSION['id_usuario'])) {
    header("Location: registro.html");
    exit();
}

$id_usuario = $_SESSION['id_usuario'];
$username = $_SESSION['username'];

// Verificar que se pasó un ID de mito
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: mis_mitos.php");
    exit();
}

$id_mito = intval($_GET['id']);

// Obtener información del usuario
$sql_usuario = "SELECT foto FROM Usuarios WHERE id_usuario = ?";
$stmt_user = $conn->prepare($sql_usuario);
$stmt_user->bind_param("i", $id_usuario);
$stmt_user->execute();
$resultado_usuario = $stmt_user->get_result();
$usuario = $resultado_usuario->fetch_assoc();
$foto_perfil = $usuario['foto'] ?? null;

// Obtener datos del mito y verificar que pertenece al usuario
$stmt = $conn->prepare("SELECT * FROM MitoLeyenda WHERE id_mitooleyenda = ? AND id_usuario = ?");
$stmt->bind_param("ii", $id_mito, $id_usuario);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    header("Location: mis_mitos.php");
    exit();
}

$mito = $resultado->fetch_assoc();
$stmt->close();

// Obtener lista de provincias
$sql_provincias = "SELECT id_provincia, Nombre FROM Provincias ORDER BY Nombre";
$resultado_provincias = $conn->query($sql_provincias);
$provincias = [];
while($prov = $resultado_provincias->fetch_assoc()) {
    $provincias[] = $prov;
}

// Obtener ciudades de la provincia seleccionada
$ciudades = [];
if (!empty($mito['id_provincia'])) {
    $sql_ciudades = "SELECT id_ciudad, Nombre FROM Ciudad WHERE id_provincia = ? ORDER BY Nombre";
    $stmt_ciudades = $conn->prepare($sql_ciudades);
    $stmt_ciudades->bind_param("i", $mito['id_provincia']);
    $stmt_ciudades->execute();
    $resultado_ciudades = $stmt_ciudades->get_result();
    while($ciudad = $resultado_ciudades->fetch_assoc()) {
        $ciudades[] = $ciudad;
    }
}

// Procesar el formulario
$errores = [];
$exito = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $textobreve = trim($_POST['textobreve'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $tipo = $_POST['tipo'] ?? '';
    $id_provincia = $_POST['id_provincia'] ?? '';
    $id_ciudad = $_POST['id_ciudad'] ?? null;
    
    // Validaciones
    if (empty($titulo)) {
        $errores[] = "El título es obligatorio";
    }
    if (empty($textobreve)) {
        $errores[] = "El texto breve es obligatorio";
    }
    if (empty($descripcion)) {
        $errores[] = "La descripción es obligatoria";
    }
    if (empty($id_provincia)) {
        $errores[] = "Debes seleccionar una provincia";
    }
    
    // Procesar imagen si se subió una nueva
    $imagen_nombre = $mito['imagen']; // Mantener la imagen actual por defecto
    
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $imagen_tmp = $_FILES['imagen']['tmp_name'];
        $imagen_original = $_FILES['imagen']['name'];
        $extension = strtolower(pathinfo($imagen_original, PATHINFO_EXTENSION));
        $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!in_array($extension, $extensiones_permitidas)) {
            $errores[] = "Solo se permiten imágenes (jpg, jpeg, png, gif, webp)";
        } else {
            $imagen_nombre = uniqid() . '_' . time() . '.' . $extension;
            $ruta_destino = 'mitos/' . $imagen_nombre;
            
            if (!move_uploaded_file($imagen_tmp, $ruta_destino)) {
                $errores[] = "Error al subir la imagen";
                $imagen_nombre = $mito['imagen']; // Revertir si falló
            } else {
                // Eliminar imagen anterior si existe y es diferente
                if (!empty($mito['imagen']) && file_exists('mitos/' . $mito['imagen'])) {
                    unlink('mitos/' . $mito['imagen']);
                }
            }
        }
    }
    
    // Si no hay errores, actualizar en la base de datos
    if (empty($errores)) {
        $sql_update = "UPDATE MitoLeyenda SET 
                       Titulo = ?, 
                       textobreve = ?, 
                       Descripcion = ?, 
                       tipo = ?, 
                       imagen = ?, 
                       id_provincia = ?, 
                       id_ciudad = ? 
                       WHERE id_mitooleyenda = ? AND id_usuario = ?";
        
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("sssssiiis", 
            $titulo, 
            $textobreve, 
            $descripcion, 
            $tipo, 
            $imagen_nombre, 
            $id_provincia, 
            $id_ciudad, 
            $id_mito, 
            $id_usuario
        );
        
        if ($stmt_update->execute()) {
            $exito = true;
            // Recargar datos actualizados
            $stmt = $conn->prepare("SELECT * FROM MitoLeyenda WHERE id_mitooleyenda = ?");
            $stmt->bind_param("i", $id_mito);
            $stmt->execute();
            $resultado = $stmt->get_result();
            $mito = $resultado->fetch_assoc();
        } else {
            $errores[] = "Error al actualizar el mito";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar Mito - LeyendAR</title>
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

        .btn-back {
            background-color: #6c757d;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.85rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background-color 0.2s;
        }

        .btn-back:hover {
            background-color: #5a6268;
        }

        main {
            flex: 1;
            padding: 2rem;
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
        }

        .page-title {
            font-size: 2rem;
            color: #1d2e42;
            font-weight: 700;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-title i {
            color: #667eea;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert i {
            font-size: 1.2rem;
        }

        .form-container {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-size: 1rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .form-group label .required {
            color: #dc3545;
        }

        .form-group input[type="text"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-family: 'Quicksand', sans-serif;
            font-size: 1rem;
            transition: border-color 0.2s;
        }

        .form-group input[type="text"]:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .form-group.textarea-large textarea {
            min-height: 200px;
        }

        .form-group input[type="file"] {
            display: none;
        }

        .file-upload-wrapper {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn-file-upload {
            background: #e8eeff;
            color: #2b4ab8;
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-family: 'Quicksand', sans-serif;
            font-size: 0.95rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            border: 2px solid #d0dcff;
        }

        .btn-file-upload:hover {
            background: #d0dcff;
            transform: translateY(-1px);
        }

        .file-name {
            color: #666;
            font-size: 0.9rem;
        }

        .image-preview {
            margin-top: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .image-preview img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 12px;
            border: 2px solid #ddd;
        }

        .image-preview-info {
            color: #666;
            font-size: 0.9rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 32px;
            border-radius: 25px;
            border: none;
            font-family: 'Quicksand', sans-serif;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #e0e0e0;
            color: #555;
        }

        .btn-secondary:hover {
            background: #d0d0d0;
        }

        .form-help {
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.3rem;
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

            main {
                padding: 1rem;
            }

            .form-container {
                padding: 1.5rem;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
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
            <a href="mis_mitos.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </header>

    <main>
        <h1 class="page-title">
            <i class="fas fa-edit"></i> Editar Mito o Leyenda
        </h1>

        <?php if ($exito): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span>¡Mito actualizado exitosamente!</span>
            </div>
        <?php endif; ?>

        <?php if (!empty($errores)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    <?php foreach ($errores as $error): ?>
                        <div><?= htmlspecialchars($error) ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="titulo">
                        Título <span class="required">*</span>
                    </label>
                    <input type="text" id="titulo" name="titulo" value="<?= htmlspecialchars($mito['Titulo']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="textobreve">
                        Texto breve <span class="required">*</span>
                    </label>
                    <textarea id="textobreve" name="textobreve" required><?= htmlspecialchars($mito['textobreve']) ?></textarea>
                    <div class="form-help">Una descripción corta que aparecerá en las vistas previas (máximo 200 caracteres recomendados)</div>
                </div>

                <div class="form-group textarea-large">
                    <label for="descripcion">
                        Descripción completa <span class="required">*</span>
                    </label>
                    <textarea id="descripcion" name="descripcion" required><?= htmlspecialchars($mito['Descripcion']) ?></textarea>
                    <div class="form-help">La historia completa del mito o leyenda</div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="tipo">Tipo</label>
                        <select id="tipo" name="tipo">
                            <option value="">Sin clasificar</option>
                            <option value="Mito" <?= $mito['tipo'] === 'Mito' ? 'selected' : '' ?>>Mito</option>
                            <option value="Leyenda" <?= $mito['tipo'] === 'Leyenda' ? 'selected' : '' ?>>Leyenda</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="id_provincia">
                            Provincia <span class="required">*</span>
                        </label>
                        <select id="id_provincia" name="id_provincia" required onchange="cargarCiudades(this.value)">
                            <option value="">Seleccionar provincia</option>
                            <?php foreach($provincias as $prov): ?>
                                <option value="<?= $prov['id_provincia'] ?>" <?= $mito['id_provincia'] == $prov['id_provincia'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($prov['Nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="id_ciudad">Ciudad (opcional)</label>
                    <select id="id_ciudad" name="id_ciudad">
                        <option value="">Seleccionar ciudad</option>
                        <?php foreach($ciudades as $ciudad): ?>
                            <option value="<?= $ciudad['id_ciudad'] ?>" <?= $mito['id_ciudad'] == $ciudad['id_ciudad'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($ciudad['Nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Imagen</label>
                    <div class="file-upload-wrapper">
                        <label for="imagen" class="btn-file-upload">
                            <i class="fas fa-upload"></i> Cambiar imagen
                        </label>
                        <input type="file" id="imagen" name="imagen" accept="image/*" onchange="mostrarNombreArchivo(this)">
                        <span class="file-name" id="fileName">Ningún archivo seleccionado</span>
                    </div>
                    <div class="form-help">Formatos permitidos: JPG, PNG, GIF, WEBP (máx. 5MB)</div>
                    
                    <?php if (!empty($mito['imagen'])): ?>
                        <div class="image-preview">
                            <img src="mitos/<?= htmlspecialchars($mito['imagen']) ?>" alt="Imagen actual">
                            <div class="image-preview-info">
                                <i class="fas fa-info-circle"></i> Imagen actual<br>
                                <small>Se mantendrá si no subes una nueva</small>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar cambios
                    </button>
                    <a href="mis_mitos.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </main>

    <footer>
        © 2025 leyendAR - Mitos y Leyendas Argentinas
    </footer>

    <script>
        function mostrarNombreArchivo(input) {
            const fileName = document.getElementById('fileName');
            if (input.files && input.files[0]) {
                fileName.textContent = input.files[0].name;
            } else {
                fileName.textContent = 'Ningún archivo seleccionado';
            }
        }

        function cargarCiudades(provinciaId) {
            const ciudadSelect = document.getElementById('id_ciudad');
            ciudadSelect.innerHTML = '<option value="">Cargando...</option>';
            
            if (!provinciaId) {
                ciudadSelect.innerHTML = '<option value="">Seleccionar ciudad</option>';
                return;
            }

            fetch(`obtener_ciudades.php?id_provincia=${provinciaId}`)
                .then(response => response.json())
                .then(ciudades => {
                    ciudadSelect.innerHTML = '<option value="">Seleccionar ciudad</option>';
                    ciudades.forEach(ciudad => {
                        const option = document.createElement('option');
                        option.value = ciudad.id_ciudad;
                        option.textContent = ciudad.Nombre;
                        ciudadSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error al cargar ciudades:', error);
                    ciudadSelect.innerHTML = '<option value="">Error al cargar ciudades</option>';
                });
        }
    </script>
</body>
</html>