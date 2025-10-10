<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include("main.php"); // conexión en $conn

// Recibir datos del formulario
$titulo      = $_POST['titulo']      ?? '';
$descripcion = $_POST['descripcion'] ?? '';
$texto       = $_POST['textobreve']  ?? '';
$fecha       = $_POST['fecha']       ?? date("Y-m-d"); 
$id_ciudad   = 1;
$nombre_provincia = $_POST['provincia'] ?? '';
$id_usuario  = $_SESSION['id_usuario'] ?? ($_POST['id_usuario'] ?? 0); 

// ================== SUBIDA DE IMAGEN ==================
$foto = null;

if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == UPLOAD_ERR_OK) {
    $carpetaDestino = "mitos/";

    // Crear carpeta si no existe
    if (!file_exists($carpetaDestino)) {
        mkdir($carpetaDestino, 0777, true);
    }

    // Nombre único para evitar choques
    $nombreImagen = time() . "_" . basename($_FILES["imagen"]["name"]);
    $rutaDestino  = $carpetaDestino . $nombreImagen;

    if (move_uploaded_file($_FILES["imagen"]["tmp_name"], $rutaDestino)) {
        $foto = $nombreImagen; // guardamos solo el nombre en la BD
    } else {
        die("❌ Error al mover la imagen al directorio 'mitos/'.");
    }
}

// ================== OBTENER ID PROVINCIA ==================
$stmt = $conn->prepare("SELECT id_provincia FROM Provincias WHERE Nombre = ?");
$stmt->bind_param("s", $nombre_provincia);
$stmt->execute();
$result = $stmt->get_result();

if ($fila = $result->fetch_assoc()) {
    $id_provincia = (int) $fila['id_provincia'];
} else {
    die("❌ La provincia '$nombre_provincia' no existe en la tabla Provincias");
}
$stmt->close();

// ================== INSERTAR REGISTRO ==================
$stmt = $conn->prepare("
    INSERT INTO MitoLeyenda 
    (Titulo, Descripcion, textobreve, Fecha, imagen, id_ciudad, id_provincia, id_usuario)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param("sssssiii", 
    $titulo, 
    $descripcion, 
    $texto, 
    $fecha, 
    $foto,       // ahora es el nombre del archivo
    $id_ciudad, 
    $id_provincia, 
    $id_usuario
);

if ($stmt->execute()) {
    echo "✅ Registro cargado correctamente con ID " . $stmt->insert_id;
} else {
    echo "❌ Error: " . $stmt->error;
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vista previa</title>
</head>
<body>
    <h2>Vista previa del registro</h2>
    <div><b>Título:</b> <?php echo htmlspecialchars($titulo); ?></div>
    <div><b>Descripción:</b> <?php echo nl2br(htmlspecialchars($descripcion)); ?></div>
    <div><b>Texto breve:</b> <?php echo nl2br(htmlspecialchars($texto)); ?></div>
    <div><b>Fecha:</b> <?php echo htmlspecialchars($fecha); ?></div>
    <div><b>Foto:</b> 
        <?php if ($foto): ?>
            <img src="mitos/<?php echo htmlspecialchars($foto); ?>" alt="Imagen del mito" width="200">
        <?php else: ?>
            No se subió imagen
        <?php endif; ?>
    </div>
    <div><b>Ciudad ID:</b> <?php echo htmlspecialchars($id_ciudad); ?></div>
    <div><b>Provincia ID:</b> <?php echo htmlspecialchars($id_provincia); ?></div>
    <div><b>Usuario ID:</b> <?php echo htmlspecialchars($id_usuario); ?></div>
</body>
</html>
