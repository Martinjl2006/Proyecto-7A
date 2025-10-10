<?php
// Archivo de prueba para verificar conexión a la base de datos

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test de Conexión a Base de Datos</h2>";

// 1. Verificar si main.php existe
if (file_exists('main.php')) {
    echo "✅ El archivo main.php existe<br>";
    include 'main.php';
} else {
    die("❌ ERROR: No se encuentra el archivo main.php");
}

// 2. Verificar variable $conn
if (isset($conn)) {
    echo "✅ Variable \$conn está definida<br>";
} else {
    die("❌ ERROR: La variable \$conn NO está definida en main.php");
}

// 3. Verificar conexión
if ($conn->connect_error) {
    die("❌ ERROR de conexión: " . $conn->connect_error);
} else {
    echo "✅ Conexión a la base de datos exitosa<br>";
}

// 4. Verificar que existe la tabla MitoLeyenda
$test = $conn->query("SHOW TABLES LIKE 'MitoLeyenda'");
if ($test->num_rows > 0) {
    echo "✅ Tabla MitoLeyenda existe<br>";
} else {
    die("❌ ERROR: La tabla MitoLeyenda NO existe");
}

// 5. Contar mitos en la base de datos
$result = $conn->query("SELECT COUNT(*) as total FROM MitoLeyenda");
$row = $result->fetch_assoc();
echo "✅ Hay {$row['total']} mitos en la base de datos<br><br>";

// 6. Listar los primeros 5 mitos con sus provincias
echo "<h3>Primeros 5 mitos:</h3>";
$sql = "SELECT m.id_mitooleyenda, m.Titulo, p.Nombre as Provincia 
        FROM MitoLeyenda m
        INNER JOIN Provincias p ON m.id_provincia = p.id_provincia
        LIMIT 5";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<ul>";
    while($row = $result->fetch_assoc()) {
        echo "<li><strong>ID {$row['id_mitooleyenda']}</strong>: {$row['Titulo']} ({$row['Provincia']}) 
              - <a href='mitos.php?id={$row['id_mitooleyenda']}' target='_blank'>Ver mito</a></li>";
    }
    echo "</ul>";
} else {
    echo "❌ No se encontraron mitos";
}

$conn->close();
?>