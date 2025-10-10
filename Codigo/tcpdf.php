<?php
include 'main.php';



/* Importar Librería TCPDF */
require_once('tcpdf/TCPDF-main/tcpdf.php');

// Verificar si se recibió un ID válido por GET
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);

    // Consultar mito por ID
    $sql = "SELECT id_mitooleyenda, Titulo, Descripcion FROM MitoLeyenda WHERE id_mitooleyenda = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $mito = $resultado->fetch_assoc();
        
        // Crear una nueva instancia de TCPDF
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Propiedades PDF
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('LeyendAR');
        $pdf->SetTitle($mito['Titulo']);
        $pdf->SetSubject('Mitología Argentina');
        $pdf->SetKeywords('mito, leyenda, argentina');

        // Remover encabezado y pie de página
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Establecer márgenes
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);

        // Agregar una página
        $pdf->AddPage();

        // Construir el HTML para el PDF
        $html = '
        <style>
            body { font-family: Arial, sans-serif; color: #333; }
            h1 { font-size: 24px; color: #2c3e50; margin-bottom: 10px; }
            .meta { font-size: 12px; color: #666; margin-bottom: 15px; }
            .card { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px; }
            .card h2 { font-size: 16px; color: #2c3e50; margin-bottom: 10px; }
            p { font-size: 11px; line-height: 1.6; margin-bottom: 10px; text-align: justify; }
            ul { font-size: 10px; margin-left: 20px; }
            li { margin-bottom: 5px; }
            a { color: #0066cc; text-decoration: underline; }
        </style>
        
        <h1>' . htmlspecialchars($mito['Titulo']) . '</h1>
        <div class="meta">Mitología Argentina · LeyendAR</div>
        
        <div class="card">
            <p>' . nl2br(htmlspecialchars($mito['Descripcion'])) . '</p>
        </div>
        
        <div class="card">
            <h2>Fuentes</h2>
            <ul>
                <li>Folklore Argentino - Tradiciones Orales</li>
                <li>Mitología Regional Argentina</li>
                <li>Leyendas y Relatos Populares</li>
            </ul>
        </div>
        ';

        // Convertir HTML a PDF
        $pdf->writeHTML($html, true, false, true, false, '');

        // Crear nombre de archivo limpio (sin caracteres especiales)
        $nombreArchivo = preg_replace('/[^a-zA-Z0-9]/', '-', $mito['Titulo']) . '.pdf';

        // Salida del archivo (D = descargar)
        $pdf->Output($nombreArchivo, 'D');
        
    } else {
        die("Error: No se encontró el mito solicitado.");
    }

    $stmt->close();
} else {
    die("Error: No se especificó un ID de mito válido.");
}

$conn->close();
?>