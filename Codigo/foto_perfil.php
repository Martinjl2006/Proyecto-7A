<?php
session_start();

// Incluir la conexión desde main.php
include 'main.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['username'])) {
    header("Location: registro.html");
    exit();
}

// Obtener información del usuario
$username = htmlspecialchars($_SESSION['username']);
$sqlUser = "SELECT id_usuario, foto FROM Usuarios WHERE Username = ?";
$stmt = $conn->prepare($sqlUser);
$stmt->bind_param("s", $username);
$stmt->execute();
$resultUser = $stmt->get_result();
$user = $resultUser->fetch_assoc();
$stmt->close();

if (!$user) {
    echo "Usuario no encontrado.";
    exit();
}

$idUsuario = $user['id_usuario'];
$fotoActual = $user['foto'];

// Procesar cambio de foto
$mensajeExito = "";
$mensajeError = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['foto_seleccionada'])) {
    $fotoNueva = htmlspecialchars($_POST['foto_seleccionada']);
    
    // Validar que el archivo existe en la carpeta usuarios
    $rutaFoto = 'usuarios/' . $fotoNueva;
    
    if (file_exists($rutaFoto)) {
        // Actualizar en la base de datos
        $sqlUpdate = "UPDATE Usuarios SET foto = ? WHERE id_usuario = ?";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->bind_param("si", $fotoNueva, $idUsuario);
        
        if ($stmtUpdate->execute()) {
            $_SESSION['foto_perfil'] = $fotoNueva;
            $fotoActual = $fotoNueva;
            $mensajeExito = "Foto de perfil actualizada exitosamente.";
        } else {
            $mensajeError = "Error al actualizar la foto de perfil.";
        }
        $stmtUpdate->close();
    } else {
        $mensajeError = "La imagen seleccionada no existe.";
    }
}

// Obtener imágenes de la carpeta usuarios
$carpetaUsuarios = 'usuarios';
$imagenes = [];

if (is_dir($carpetaUsuarios)) {
    $archivos = scandir($carpetaUsuarios);
    $extensionesValidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    foreach ($archivos as $archivo) {
        if ($archivo !== '.' && $archivo !== '..') {
            $extension = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
            if (in_array($extension, $extensionesValidas)) {
                $imagenes[] = $archivo;
            }
        }
    }
    sort($imagenes);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cambiar Foto de Perfil - leyendAR</title>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;700&display=swap" rel="stylesheet">
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

        .btn-volver {
            background: linear-gradient(135deg, #1d2e42, #3c506d);
            color: white;
            padding: 10px 24px;
            border-radius: 25px;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 600;
            transition: transform 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-volver:hover {
            transform: translateY(-2px);
        }

        main {
            flex: 1;
            padding: 2rem;
            max-width: 1000px;
            margin: 0 auto;
            width: 100%;
        }

        .container-perfil {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        h1 {
            color: #1d2e42;
            margin-bottom: 2rem;
            font-size: 2rem;
            text-align: center;
        }

        .foto-actual {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 3rem;
            padding-bottom: 2rem;
            border-bottom: 2px solid #e0e0e0;
        }

        .foto-actual h3 {
            color: #1d2e42;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }

        .foto-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .foto-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .mensajes {
            margin-bottom: 2rem;
        }

        .mensaje-exito {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid #28a745;
            margin-bottom: 1rem;
        }

        .mensaje-error {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid #dc3545;
            margin-bottom: 1rem;
        }

        .galeria-titulo {
            color: #1d2e42;
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
            font-weight: 700;
        }

        .galeria-imagenes {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .imagen-item {
            position: relative;
            cursor: pointer;
            border-radius: 50%;
            overflow: visible;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            height: 120px;
        }

        .imagen-item:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .imagen-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            
        }

        .imagen-item input[type="radio"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
            margin: 0;
        }

        .imagen-item label {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0,0,0,0);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .imagen-item input[type="radio"]:checked + label {
            background: rgba(43, 74, 184, 0.3);
            border: 3px solid #2b4ab8;
        }

        .checkmark {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 24px;
            height: 24px;
            background: #2b4ab8;
            border-radius: 50%;
            display: none;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
        }

        .imagen-item input[type="radio"]:checked ~ label .checkmark {
            display: flex;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .botones {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }

        .btn-guardar {
            background: linear-gradient(135deg, #2b4ab8, #4a6cd6);
            color: white;
            padding: 12px 40px;
            border-radius: 25px;
            border: none;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .btn-guardar:hover {
            transform: scale(1.05);
        }

        .btn-guardar:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .sin-imagenes {
            text-align: center;
            color: #666;
            padding: 2rem;
            font-size: 1.1rem;
        }

        footer {
            background: #e0e0e0;
            text-align: center;
            padding: 1.5rem;
            color: #666;
            font-size: 0.9rem;
            margin-top: auto;
        }

        @media (max-width: 768px) {
            header {
                flex-direction: column;
                gap: 15px;
            }

            main {
                padding: 1rem;
            }

            .container-perfil {
                padding: 1.5rem;
            }

            h1 {
                font-size: 1.5rem;
            }

            .galeria-imagenes {
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
                gap: 1rem;
            }

            .imagen-item {
                height: 100px;
                border: 5px;
                border-radius: 50%;
            }

            .botones {
                flex-direction: column;
            }

            .btn-guardar, .btn-volver {
                width: 100%;
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
        <a href="dashboard.php" class="btn-volver">← Volver</a>
    </header>

    <main>
        <div class="container-perfil">
            <h1>Cambiar Foto de Perfil</h1>

            <?php if (!empty($mensajeExito)): ?>
                <div class="mensajes">
                    <div class="mensaje-exito"><?= $mensajeExito ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($mensajeError)): ?>
                <div class="mensajes">
                    <div class="mensaje-error"><?= $mensajeError ?></div>
                </div>
            <?php endif; ?>

            <div class="foto-actual">
                <h3>Tu foto actual</h3>
                <div class="foto-preview">
                    <?php if (!empty($fotoActual)): ?>
                        <img src="usuarios/<?= htmlspecialchars($fotoActual) ?>" alt="Foto de perfil" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22120%22 height=%22120%22%3E%3Crect fill=%22%23ddd%22 width=%22120%22 height=%22120%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 font-family=%22Arial%22 font-size=%2214%22 fill=%22%23888%22%3ESin foto%3C/text%3E%3C/svg%3E'">
                    <?php else: ?>
                        <span style="color: #888; text-align: center;">Sin foto de perfil</span>
                    <?php endif; ?>
                </div>
            </div>

            <form method="POST">
                <div>
                    <h3 class="galeria-titulo">Selecciona una nueva foto</h3>

                    <?php if (empty($imagenes)): ?>
                        <div class="sin-imagenes">
                            <p>No hay imágenes disponibles en la carpeta de usuarios.</p>
                        </div>
                    <?php else: ?>
                        <div class="galeria-imagenes">
                            <?php foreach ($imagenes as $imagen): ?>
                                <div class="imagen-item">
                                    <input type="radio" id="img_<?= htmlspecialchars($imagen) ?>" name="foto_seleccionada" value="<?= htmlspecialchars($imagen) ?>" <?= ($fotoActual === $imagen) ? 'checked' : '' ?>>
                                    <label for="img_<?= htmlspecialchars($imagen) ?>">
                                        <img src="usuarios/<?= htmlspecialchars($imagen) ?>" alt="<?= htmlspecialchars($imagen) ?>">
                                        <div class="checkmark">✓</div>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="botones">
                    <button type="submit" class="btn-guardar" <?= empty($imagenes) ? 'disabled' : '' ?>>
                        Guardar foto de perfil
                    </button>
                </div>
            </form>
        </div>
    </main>

    <footer>
        © 2025 leyendAR - Mitos y Leyendas Argentinas
    </footer>
</body>
</html>