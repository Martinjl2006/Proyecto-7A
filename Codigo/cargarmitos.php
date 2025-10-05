<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include("main.php"); // aquí debería estar tu $conn

// Recibir datos del formulario
$titulo       = $_POST['titulo']       ?? '';
$textobreve   = $_POST['textobreve']   ?? '';
$descripcion  = $_POST['descripcion']  ?? '';
$fecha        = $_POST['fecha']        ?? date("Y-m-d"); 
$nombre_provincia = $_POST['provincia'] ?? '';
$id_usuario   = $_SESSION['id_usuario'] ?? ($_POST['id_usuario'] ?? 0); 

// ---- Manejo de la imagen ----
$imagen = '';
if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == UPLOAD_ERR_OK) {
    $carpetaDestino = "mitos/"; // carpeta donde se guardarán las imágenes
    if (!is_dir($carpetaDestino)) {
        mkdir($carpetaDestino, 0777, true); // crea la carpeta si no existe
    }

    // Crear nombre único para evitar colisiones
    $nombreArchivo = time() . "_" . basename($_FILES["imagen"]["name"]);
    $rutaDestino   = $carpetaDestino . $nombreArchivo;

    if (move_uploaded_file($_FILES["imagen"]["tmp_name"], $rutaDestino)) {
        $imagen = $rutaDestino; // guardamos la ruta relativa
    } else {
        die("❌ Error al mover la imagen al directorio destino");
    }
}

// ---- Obtener id_provincia ----
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

// ---- Insertar en la tabla ----
$stmt = $conn->prepare("
    INSERT INTO MitoLeyenda 
    (Titulo, textobreve, Descripcion, imagen, Fecha, id_provincia, id_usuario)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param("sssssis", $titulo, $textobreve, $descripcion, $imagen, $fecha, $id_provincia, $id_usuario);

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
    <div><b>Texto breve:</b> <?php echo nl2br(htmlspecialchars($textobreve)); ?></div>
    <div><b>Descripción:</b> <?php echo nl2br(htmlspecialchars($descripcion)); ?></div>
    <div><b>Fecha:</b> <?php echo htmlspecialchars($fecha); ?></div>
    <div><b>Imagen:</b> 
        <?php if ($imagen): ?>
            <img src="<?php echo htmlspecialchars($imagen); ?>" width="200">
        <?php else: ?>
            Sin imagen
        <?php endif; ?>
    </div>
    <div><b>Provincia ID:</b> <?php echo htmlspecialchars($id_provincia); ?></div>
    <div><b>Usuario ID:</b> <?php echo htmlspecialchars($id_usuario); ?></div>
</body>
</html>
