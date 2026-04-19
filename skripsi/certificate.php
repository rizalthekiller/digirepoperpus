<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../user/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$thesis_id = isset($_GET['id']) ? $conn->real_escape_string($_GET['id']) : '';

// Ensure user only downloads their own or if admin
if ($_SESSION['role'] == 'admin') {
    $sql = "SELECT certificate_content, certificate_number FROM theses WHERE id = '$thesis_id' AND status = 'approved'";
} else {
    $sql = "SELECT certificate_content, certificate_number FROM theses WHERE id = '$thesis_id' AND user_id = '$user_id' AND status = 'approved'";
}

$res = $conn->query($sql);

if ($res->num_rows == 0) {
    die("Sertifikat tidak ditemukan atau skripsi belum disetujui.");
}

$row = $res->fetch_assoc();

if (empty($row['certificate_content'])) {
    die("Data sertifikat belum digenerate oleh admin.");
}

// Handle PDF Download request
if (isset($_GET['download']) && $_GET['download'] == '1') {
    try {
        require_once '../includes/dompdf/autoload.inc.php';
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('chroot', realpath(dirname(__DIR__)));
        $dompdf = new \Dompdf\Dompdf($options);
        
        // Convert URLs to local paths for reliability in Dompdf
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
        $baseUrl = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/";
        
        // Speed Optimization: resolve local paths before Dompdf sees them
        $localContent = str_replace($baseUrl, dirname(__DIR__) . "/", $row['certificate_content']);
        $localContent = str_replace('src="uploads/', 'src="' . dirname(__DIR__) . '/uploads/', $localContent);

        $html = "<style>
            @page { margin: 0; }
            body { font-family: Arial, Helvetica, sans-serif; padding: 0; margin: 0; }
            .content-area { padding: 40px; }
            img { max-width: 100%; height: auto; display: block; margin: 0 auto; }
        </style><div class='content-area'>" . $localContent . "</div>";
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        $safe_num = str_replace(['/', '\\', ' ', '.'], '_', $row['certificate_number']);
        $dompdf->stream("Sertifikat_$safe_num.pdf", ["Attachment" => true]);
        exit();
    } catch (Exception $e) {
        die("Gagal membuat PDF: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Keterangan - <?php echo $row['certificate_number']; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Arial', sans-serif; background: #f1f5f9; padding: 20px; color: #334155; }
        .cert-container { 
            background: white; 
            width: 100%;
            max-width: 850px; 
            margin: 0 auto; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            position: relative;
            border-radius: 8px;
            overflow: hidden;
        }
        .cert-body-wrapper { padding: 40px; }
        img { max-width: 100%; height: auto; display: block; }
        @media print {
            body { background: white; padding: 0; }
            .cert-container { box-shadow: none; width: 100%; max-width: none; padding: 0; border-radius: 0; }
            .no-print { display: none; }
            .cert-body-wrapper { padding: 0; }
        }
        .btn-group-custom {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin: 20px auto;
            max-width: 500px;
        }
        .btn-action {
            display: inline-block;
            padding: 12px 25px;
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 50px;
            font-family: sans-serif;
            font-weight: bold;
            transition: 0.3s;
        }
        .btn-print { background: #1e3a8a; }
        .btn-download { background: #10b981; }
        .btn-action:hover { opacity: 0.9; transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="no-print">
        <div class="btn-group-custom">
            <a href="javascript:window.print()" class="btn-action btn-print">
                <i class="fas fa-print me-2"></i> Cetak Sekarang
            </a>
            <a href="?id=<?php echo $thesis_id; ?>&download=1" class="btn-action btn-download">
                <i class="fas fa-file-pdf me-2"></i> Download PDF
            </a>
        </div>
    </div>

    <div class="cert-container">
        <div class="cert-body-wrapper">
            <div class="cert-body">
                <?php echo $row['certificate_content']; ?>
            </div>
            <div style="margin-top: 50px; text-align: center; font-size: 0.8rem; color: #666;">
                Diterbitkan oleh Sistem Digital Repository (DigiRepo)
            </div>
        </div>
    </div>
</body>
</html>
