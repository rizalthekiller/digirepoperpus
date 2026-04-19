<?php
session_start();
include '../includes/db.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die("Access denied");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['html'])) {
    try {
        require_once '../includes/dompdf/autoload.inc.php';
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('chroot', realpath(dirname(__DIR__)));
        $dompdf = new \Dompdf\Dompdf($options);
        
        $content = $_POST['html'];
        
        // Convert URLs to local paths for reliability in Dompdf
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
        $baseUrl = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/";
        
        // Optimize: Use relative paths for local images to speed up Dompdf
        $localContent = str_replace($baseUrl, dirname(__DIR__) . "/", $content);
        // Also handle relative uploads
        $localContent = str_replace('src="uploads/', 'src="' . dirname(__DIR__) . '/uploads/', $localContent);

        $html = "<style>
            @page { margin: 0; }
            body { font-family: Arial, Helvetica, sans-serif; padding: 0; margin: 0; }
            .container { padding: 40px; }
            img { max-width: 100%; height: auto; display: block; }
        </style>" . $localContent;
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        // Output PDF to browser for iframe preview
        header('Content-Type: application/pdf');
        echo $dompdf->output();
        exit();
    } catch (Exception $e) {
        die("PDF Generation Error: " . $e->getMessage());
    }
} else {
    die("Invalid request");
}
?>
