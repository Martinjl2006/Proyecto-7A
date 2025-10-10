<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include("main.php"); // aquí debería estar tu $conn

// Recibir datos del formulario
$titulo      = $_POST['titulo']      ?? '';
$descripcion = $_POST['descripcion'] ?? '';
$fecha       = $_POST['fecha']       ?? date("Y-m-d"); // usa fecha actual si no la mandan
$foto        = $_POST['imagen']        ?? ''; // puede ser ruta de la imagen o nombre de archivo
$id_ciudad   = 1;
$nombre_provincia= $_POST['provincia'];
$id_usuario  = $_SESSION['id_usuario'] ?? ($_POST['id_usuario'] ?? 0); 
// lo ideal es tomarlo de la sesión si es un usuario logueado

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

// Preparar consulta
$stmt = $conn->prepare("
    INSERT INTO MitoLeyenda 
    (Titulo, Descripcion, Fecha, foto, id_ciudad, id_provincia, id_usuario)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param("ssssiii", $titulo, $descripcion, $fecha, $foto, $id_ciudad, $id_provincia, $id_usuario);

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
    <div><b>Fecha:</b> <?php echo htmlspecialchars($fecha); ?></div>
    <div><b>Foto:</b> <?php echo htmlspecialchars($foto); ?></div>
    <div><b>Ciudad ID:</b> <?php echo htmlspecialchars($id_ciudad); ?></div>
    <div><b>Provincia ID:</b> <?php echo htmlspecialchars($id_provincia); ?></div>
    <div><b>Usuario ID:</b> <?php echo htmlspecialchars($id_usuario); ?></div>
</body>
</html>
