<?php
session_start();

// Incluir la conexión desde main.php
include 'main.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['username'])) {
    header("Location: registro.html");
    exit();
}

// Obtener el ID del usuario y su foto
$username = $_SESSION['username'];
$sql_usuario = "SELECT id_usuario, foto FROM Usuarios WHERE username = ?";
$stmt = $conn->prepare($sql_usuario);
$stmt->bind_param("s", $username);
$stmt->execute();
$resultado_usuario = $stmt->get_result();
$usuario = $resultado_usuario->fetch_assoc();
$id_usuario = $usuario['id_usuario'] ?? null;
$foto_perfil = $usuario['foto'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Crear Nuevo Mito - LeyendAR</title>
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

        .page-subtitle {
            color: #666;
            font-size: 1rem;
            margin-bottom: 2rem;
            line-height: 1.6;
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
            <a href="dashboard.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </header>

    <main>
        <h1 class="page-title">
            <i class="fas fa-plus-circle"></i> Crear Nuevo Mito
        </h1>

        <p class="page-subtitle">
            Subí tu mito o leyenda para que otros puedan descubrirlo en el mapa interactivo de LeyendAR.
        </p>

        <div class="form-container">
            <form action="cargarmitos.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="titulo">
                        Título del mito <span class="required">*</span>
                    </label>
                    <input type="text" id="titulo" name="titulo" placeholder="Ej: El Lobizón" required>
                </div>

                <div class="form-group">
                    <label for="textobreve">
                        Texto breve <span class="required">*</span>
                    </label>
                    <textarea id="textobreve" name="textobreve" placeholder="Resuminos tu mito" required></textarea>
                    <div class="form-help">Una descripción corta que aparecerá en las vistas previas (máximo 200 caracteres recomendados)</div>
                </div>

                <div class="form-group textarea-large">
                    <label for="descripcion">
                        Descripción completa <span class="required">*</span>
                    </label>
                    <textarea id="descripcion" name="descripcion" placeholder="Contanos la historia del mito..." required></textarea>
                    <div class="form-help">La historia completa del mito o leyenda</div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="tipo">Tipo</label>
                        <select id="tipo" name="tipo">
                            <option value="">Sin clasificar</option>
                            <option value="Mito">Mito</option>
                            <option value="Leyenda">Leyenda</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="id_provincia">
                            Provincia <span class="required">*</span>
                        </label>
                        <select id="id_provincia" name="id_provincia" required onchange="cargarCiudades(this.value)">
                            <option value="">Seleccioná una provincia</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="id_ciudad">Ciudad (opcional)</label>
                    <select id="id_ciudad" name="id_ciudad">
                        <option value="">Seleccionar ciudad</option>
                    </select>
                    <div class="form-help">Primero seleccioná una provincia para ver las ciudades disponibles</div>
                </div>

                <div class="form-group">
                    <label>Imagen del mito (opcional)</label>
                    <div class="file-upload-wrapper">
                        <label for="imagen" class="btn-file-upload">
                            <i class="fas fa-upload"></i> Seleccionar imagen
                        </label>
                        <input type="file" id="imagen" name="imagen" accept="image/*" onchange="mostrarNombreArchivo(this)">
                        <span class="file-name" id="fileName">Ningún archivo seleccionado</span>
                    </div>
                    <div class="form-help">Formatos permitidos: JPG, PNG, GIF, WEBP (máx. 5MB)</div>
                    
                    <div class="image-preview" id="imagePreview" style="display: none;">
                        <img id="previewImg" src="" alt="Vista previa">
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check-circle"></i> Subir mito
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary">
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
        // Cargar provincias al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            cargarProvincias();
        });

        function cargarProvincias() {
            const provinciaSelect = document.getElementById('id_provincia');
            
            fetch('get_provincias.php')
                .then(response => response.json())
                .then(data => {
                    console.log('Provincias recibidas:', data); // Para debug
                    if (Array.isArray(data)) {
                        data.forEach(provincia => {
                            const option = document.createElement('option');
                            option.value = provincia.id_provincia;
                            // El archivo get_provincias.php devuelve 'nombre' en minúscula
                            option.textContent = provincia.nombre;
                            provinciaSelect.appendChild(option);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error al cargar provincias:', error);
                });
        }

        function mostrarNombreArchivo(input) {
            const fileName = document.getElementById('fileName');
            const imagePreview = document.getElementById('imagePreview');
            const previewImg = document.getElementById('previewImg');
            
            if (input.files && input.files[0]) {
                fileName.textContent = input.files[0].name;
                
                // Mostrar vista previa de la imagen
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    imagePreview.style.display = 'flex';
                };
                reader.readAsDataURL(input.files[0]);
            } else {
                fileName.textContent = 'Ningún archivo seleccionado';
                imagePreview.style.display = 'none';
            }
        }

        function cargarCiudades(provinciaId) {
            const ciudadSelect = document.getElementById('id_ciudad');
            ciudadSelect.innerHTML = '<option value="">Cargando...</option>';
            
            if (!provinciaId) {
                ciudadSelect.innerHTML = '<option value="">Seleccionar ciudad</option>';
                return;
            }

            fetch(`get_ciudades.php?id_provincia=${provinciaId}`)
                .then(response => response.json())
                .then(data => {
                    console.log('Ciudades recibidas:', data); // Para debug
                    ciudadSelect.innerHTML = '<option value="">Seleccionar ciudad</option>';
                    
                    if (Array.isArray(data) && data.length > 0) {
                        data.forEach(ciudad => {
                            const option = document.createElement('option');
                            option.value = ciudad.id_ciudad;
                            // El archivo get_ciudades.php devuelve 'nombre' en minúscula
                            option.textContent = ciudad.nombre;
                            ciudadSelect.appendChild(option);
                        });
                    } else {
                        ciudadSelect.innerHTML = '<option value="">No hay ciudades disponibles</option>';
                    }
                })
                .catch(error => {
                    console.error('Error al cargar ciudades:', error);
                    ciudadSelect.innerHTML = '<option value="">Error al cargar ciudades</option>';
                });
        }
    </script>
</body>
</html>