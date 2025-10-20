<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include("main.php"); // conexión en $conn

// Recibir datos del formulario
$titulo       = $_POST['titulo']       ?? '';
$descripcion  = $_POST['descripcion']  ?? '';
$texto        = $_POST['textobreve']   ?? '';
$tipo         = $_POST['tipo']         ?? null;
$fecha        = $_POST['fecha']        ?? date("Y-m-d"); 
$id_provincia = $_POST['id_provincia'] ?? 0;
$id_ciudad    = $_POST['id_ciudad']    ?? null;
$id_usuario   = $_SESSION['id_usuario'] ?? ($_POST['id_usuario'] ?? 0); 

// Validar campos obligatorios
$errores = [];

if (empty($titulo)) {
    $errores[] = "El título es obligatorio";
}

if (empty($texto)) {
    $errores[] = "El texto breve es obligatorio";
}

if (empty($descripcion)) {
    $errores[] = "La descripción completa es obligatoria";
}

if (empty($id_provincia) || $id_provincia == 0) {
    $errores[] = "Debes seleccionar una provincia";
}

// Si hay errores, mostrarlos y detener
if (!empty($errores)) {
    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Error - LeyendAR</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background: #f5f5f5;
                padding: 20px;
            }
            .error-container {
                max-width: 600px;
                margin: 50px auto;
                background: white;
                padding: 30px;
                border-radius: 12px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            }
            h2 {
                color: #dc3545;
                margin-bottom: 20px;
            }
            .error-list {
                list-style: none;
                padding: 0;
            }
            .error-list li {
                background: #f8d7da;
                color: #721c24;
                padding: 10px 15px;
                margin-bottom: 10px;
                border-radius: 6px;
                border-left: 4px solid #dc3545;
            }
            .btn {
                display: inline-block;
                margin-top: 20px;
                padding: 10px 20px;
                background: #667eea;
                color: white;
                text-decoration: none;
                border-radius: 6px;
                font-weight: bold;
            }
            .btn:hover {
                background: #5568d3;
            }
        </style>
    </head>
    <body>
        <div class='error-container'>
            <h2>❌ Se encontraron errores</h2>
            <ul class='error-list'>";
    
    foreach ($errores as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    
    echo "</ul>
            <a href='javascript:history.back()' class='btn'>← Volver al formulario</a>
        </div>
    </body>
    </html>";
    exit();
}

// Si id_ciudad está vacío, convertirlo a NULL
if (empty($id_ciudad)) {
    $id_ciudad = null;
}

// ================== SUBIDA DE IMAGEN ==================
$foto = null;

if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == UPLOAD_ERR_OK) {
    $carpetaDestino = "mitos/";

    // Crear carpeta si no existe
    if (!file_exists($carpetaDestino)) {
        mkdir($carpetaDestino, 0777, true);
    }

    // Validar tipo de archivo
    $tipoArchivo = strtolower(pathinfo($_FILES["imagen"]["name"], PATHINFO_EXTENSION));
    $tiposPermitidos = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($tipoArchivo, $tiposPermitidos)) {
        die("❌ Error: Solo se permiten imágenes (JPG, JPEG, PNG, GIF, WEBP)");
    }

    // Validar tamaño (máx 5MB)
    if ($_FILES["imagen"]["size"] > 5242880) {
        die("❌ Error: La imagen no puede superar los 5MB");
    }

    // Nombre único para evitar choques
    $nombreImagen = uniqid() . "_" . time() . "." . $tipoArchivo;
    $rutaDestino  = $carpetaDestino . $nombreImagen;

    if (move_uploaded_file($_FILES["imagen"]["tmp_name"], $rutaDestino)) {
        $foto = $nombreImagen; // guardamos solo el nombre en la BD
    } else {
        die("❌ Error al mover la imagen al directorio 'mitos/'.");
    }
} else {
    $foto = "default.png";
}

// ================== INSERTAR REGISTRO ==================
$stmt = $conn->prepare("
    INSERT INTO MitoLeyenda 
    (Titulo, Descripcion, textobreve, tipo, Fecha, imagen, id_provincia, id_ciudad, id_usuario)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param("ssssssiis", 
    $titulo, 
    $descripcion, 
    $texto,
    $tipo,
    $fecha, 
    $foto,
    $id_provincia,
    $id_ciudad, 
    $id_usuario
);

$success = false;
$insert_id = 0;

if ($stmt->execute()) {
    $success = true;
    $insert_id = $stmt->insert_id;
} else {
    $error_message = $stmt->error;
}

$stmt->close();

// Obtener nombre de la provincia para mostrar
$nombre_provincia = "";
if ($id_provincia > 0) {
    $stmt = $conn->prepare("SELECT nombre FROM Provincias WHERE id_provincia = ?");
    $stmt->bind_param("i", $id_provincia);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $nombre_provincia = $row['nombre'];
    }
    $stmt->close();
}

// Obtener nombre de la ciudad si existe
$nombre_ciudad = "";
if ($id_ciudad) {
    $stmt = $conn->prepare("SELECT nombre FROM Ciudad WHERE id_ciudad = ?");
    $stmt->bind_param("i", $id_ciudad);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $nombre_ciudad = $row['nombre'];
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $success ? 'Mito Creado' : 'Error' ?> - LeyendAR</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .result-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 700px;
            width: 100%;
            overflow: hidden;
        }

        .result-header {
            padding: 30px;
            text-align: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .result-header.error {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        }

        .result-icon {
            font-size: 4rem;
            margin-bottom: 15px;
        }

        .result-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .result-header p {
            font-size: 1rem;
            opacity: 0.9;
        }

        .result-body {
            padding: 30px;
        }

        .info-grid {
            display: grid;
            gap: 20px;
        }

        .info-item {
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 15px;
        }

        .info-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .info-label {
            font-weight: 700;
            color: #667eea;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-value {
            color: #333;
            line-height: 1.6;
        }

        .image-preview {
            margin-top: 10px;
        }

        .image-preview img {
            max-width: 100%;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .btn {
            flex: 1;
            min-width: 150px;
            padding: 12px 24px;
            border-radius: 25px;
            border: none;
            font-family: 'Quicksand', sans-serif;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.3s;
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

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px 20px;
            border-radius: 12px;
            border-left: 4px solid #dc3545;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="result-container">
        <div class="result-header <?= $success ? '' : 'error' ?>">
            <div class="result-icon">
                <?= $success ? '✅' : '❌' ?>
            </div>
            <h1><?= $success ? '¡Mito creado exitosamente!' : 'Error al crear el mito' ?></h1>
            <p><?= $success ? 'Tu mito ha sido agregado a LeyendAR' : 'Ocurrió un problema al procesar tu solicitud' ?></p>
        </div>

        <div class="result-body">
            <?php if ($success): ?>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-heading"></i> Título
                        </div>
                        <div class="info-value"><?= htmlspecialchars($titulo) ?></div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-align-left"></i> Texto breve
                        </div>
                        <div class="info-value"><?= nl2br(htmlspecialchars($texto)) ?></div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-file-alt"></i> Descripción completa
                        </div>
                        <div class="info-value"><?= nl2br(htmlspecialchars($descripcion)) ?></div>
                    </div>

                    <?php if (!empty($tipo)): ?>
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-tag"></i> Tipo
                        </div>
                        <div class="info-value"><?= htmlspecialchars($tipo) ?></div>
                    </div>
                    <?php endif; ?>

                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-map-marker-alt"></i> Ubicación
                        </div>
                        <div class="info-value">
                            <?= htmlspecialchars($nombre_provincia) ?>
                            <?php if (!empty($nombre_ciudad)): ?>
                                - <?= htmlspecialchars($nombre_ciudad) ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-calendar"></i> Fecha
                        </div>
                        <div class="info-value"><?= htmlspecialchars($fecha) ?></div>
                    </div>

                    <?php if ($foto): ?>
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-image"></i> Imagen
                        </div>
                        <div class="image-preview">
                            <img src="mitos/<?= htmlspecialchars($foto) ?>" alt="Imagen del mito">
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-hashtag"></i> ID del registro
                        </div>
                        <div class="info-value">#<?= htmlspecialchars($insert_id) ?></div>
                    </div>
                </div>

                <div class="action-buttons">
                    <a href="crearmito.html" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Crear otro mito
                    </a>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-home"></i> Ir al inicio
                    </a>
                </div>
            <?php else: ?>
                <div class="error-message">
                    <strong>Error:</strong> <?= htmlspecialchars($error_message ?? 'Error desconocido') ?>
                </div>

                <div class="action-buttons">
                    <button onclick="history.back()" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Volver al formulario
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-home"></i> Ir al inicio
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>