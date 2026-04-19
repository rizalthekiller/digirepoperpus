<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'mahasiswa' && $_SESSION['role'] != 'dosen')) {
    header("Location: ../user/login.php");
    exit();
}

if ($_SESSION['is_verified'] == 0 && $_SESSION['role'] != 'admin') {
    die("<div style='font-family: Arial; padding: 50px; text-align: center;'>
        <h2 style='color: #1e3a8a;'>Verifikasi Diperlukan</h2>
        <p>Akun Anda belum diverifikasi oleh admin. Anda belum diizinkan untuk mengunggah dokumen.</p>
        <a href='../index.php' style='color: #1e3a8a; font-weight: bold;'>Kembali ke Beranda</a>
    </div>");
}

// Check: Mahasiswa hanya boleh 1 karya aktif. Jika ada thesis (pending/rejected), harus revisi dulu
$user_id = $_SESSION['user_id'];
$existing_thesis = $conn->query("SELECT id, status FROM theses WHERE user_id = '$user_id' ORDER BY created_at DESC LIMIT 1");

if ($existing_thesis->num_rows > 0) {
    $thesis = $existing_thesis->fetch_assoc();
    
    if ($thesis['status'] == 'pending') {
        die("<div style='font-family: Arial; padding: 50px; text-align: center;'>
            <h2 style='color: #1e3a8a;'>Unggah Dibatasi</h2>
            <p>Anda memiliki pengajuan yang sedang dalam proses verifikasi admin.</p>
            <p>Silakan tunggu atau <b>edit/update pengajuan Anda</b> melalui link berikut:</p>
            <p style='margin-top: 20px;'><a href='edit.php?id=" . $thesis['id'] . "' style='background: #1e3a8a; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Edit Pengajuan</a></p>
            <p><a href='../user/dashboard.php' style='color: #1e3a8a; font-weight: bold;'>Kembali ke Dashboard</a></p>
        </div>");
    } elseif ($thesis['status'] == 'rejected') {
        die("<div style='font-family: Arial; padding: 50px; text-align: center;'>
            <h2 style='color: #dc3545;'>Pengajuan Ditolak</h2>
            <p>Pengajuan skripsi Anda <b>ditolak</b>. Anda harus melakukan <b>revisi</b> sebelum mengajukan yang baru.</p>
            <p style='margin-top: 20px;'><a href='edit.php?id=" . $thesis['id'] . "' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Ajukan Revisi Sekarang</a></p>
            <p><a href='../user/dashboard.php' style='color: #1e3a8a; font-weight: bold;'>Kembali ke Dashboard</a></p>
        </div>");
    }
    // If approved, allow new upload (user can have multiple approved works)
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $conn->real_escape_string($_POST['title']);
    $type = $conn->real_escape_string($_POST['type']);
    $year = (int)$_POST['year']; // Proper type casting
    $abstract = $conn->real_escape_string($_POST['abstract']);
    $keywords = $conn->real_escape_string($_POST['keywords']);
    $supervisor_name = $conn->real_escape_string($_POST['supervisor_name']);
    $user_id = $_SESSION['user_id'];
    $id = uniqid();

    // File upload logic
    $target_dir = "../uploads/theses/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $original_name = basename($_FILES["file"]["name"]);
    $file_extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
    $uploadOk = 1;
    
    // Security: Validate file extension and secondary extensions
    $forbidden_extensions = ['php', 'php3', 'php4', 'php5', 'phtml', 'exe', 'sh', 'js'];
    $parts = explode('.', strtolower($original_name));
    foreach ($parts as $part) {
        if (in_array($part, $forbidden_extensions)) {
            $error = "File berisi ekstensi terlarang ($part).";
            $uploadOk = 0;
            break;
        }
    }

    if ($file_extension != "pdf") {
        $error = "Hanya file PDF yang diperbolehkan.";
        $uploadOk = 0;
    }

    // Security: Check Mime Type using finfo
    if ($uploadOk == 1) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES["file"]["tmp_name"]);
        finfo_close($finfo);
        // Check if MIME type contains 'pdf' (handles variations like "application/pdf; charset=binary")
        if (stripos($mime, 'pdf') === false) {
            $error = "Konten file bukan merupakan PDF yang valid (Mime: $mime).";
            $uploadOk = 0;
        }
    }

    // Security: Maximum file size (20MB)
    if ($_FILES["file"]["size"] > 20 * 1024 * 1024) {
        $error = "Ukuran file terlalu besar (Maksimal 20MB).";
        $uploadOk = 0;
    }

    if ($uploadOk == 1) {
        $file_name = $id . ".pdf"; // Forced extension for safety
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
            $file_path = "uploads/theses/" . $file_name;
            
            // Menggunakan Prepared Statement untuk keamanan
            $stmt = $conn->prepare("INSERT INTO theses (id, user_id, title, type, year, abstract, keywords, supervisor_name, file_path, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->bind_param("ssssissss", $id, $user_id, $title, $type, $year, $abstract, $keywords, $supervisor_name, $file_path);

            if ($stmt->execute()) {
                // Notify Admin
                require_once '../includes/notification_service.php';
                NotificationService::notifyAdminNewThesis($_SESSION['name'], $title);

                $success = "Skripsi berhasil diunggah dan sedang menunggu verifikasi admin.";
            } else {
                $error = "Terjadi kesalahan database: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = "Terjadi kesalahan saat mengunggah file.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unggah Skripsi - DigiRepo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; }
        .form-card { border: none; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); background: white; padding: 40px; }
        .btn-primary { background-color: #1e3a8a; border: none; border-radius: 12px; padding: 12px 24px; font-weight: 700; }
        .btn-primary:hover { background-color: #1e40af; }
        .form-control, .form-select { border-radius: 10px; padding: 12px; border: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm py-3" style="background-color: #0f172a;">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../index.php">DigiRepo.</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link text-white small fw-bold" href="../logout.php"><i class="fas fa-sign-out-alt me-1"></i> Keluar</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="form-card">
                    <div class="d-flex align-items-center mb-4">
                        <a href="../index.php" class="btn btn-light rounded-circle me-3"><i class="fas fa-arrow-left"></i></a>
                        <h2 class="fw-bold mb-0">Unggah Skripsi Baru</h2>
                    </div>

                    <?php if(!empty($success)): ?>
                        <div class="alert alert-success border-0 border-start border-success rounded-0 mb-4 shadow-sm alert-dismissible fade show" role="alert" style="border-left: 4px solid #198754; background-color: #f1f9f6;">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-check-circle me-3" style="color: #198754; font-size: 1.2rem; margin-top: 2px;"></i>
                                <div>
                                    <strong>Berhasil!</strong>
                                    <div><?php echo htmlspecialchars($success); ?></div>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    <?php if(!empty($error)): ?>
                        <div class="alert alert-danger border-0 border-start border-danger rounded-0 mb-4 shadow-sm alert-dismissible fade show" role="alert" style="border-left: 4px solid #dc3545; background-color: #fdf8f8;">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-exclamation-triangle me-3" style="color: #dc3545; font-size: 1.2rem; margin-top: 2px;"></i>
                                <div>
                                    <strong>Terjadi Kesalahan!</strong>
                                    <div><?php echo htmlspecialchars($error); ?></div>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form action="tambah.php" method="POST" enctype="multipart/form-data">
                        <div class="mb-4">
                            <label class="form-label fw-bold text-secondary">Judul Skripsi</label>
                            <input type="text" name="title" class="form-control" placeholder="Masukkan judul lengkap skripsi" required>
                        </div>
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-secondary">Tipe Dokumen</label>
                                <select name="type" class="form-select" required>
                                    <option value="Skripsi">Skripsi (S1)</option>
                                    <option value="Thesis">Thesis (S2)</option>
                                    <option value="Disertasi">Disertasi (S3)</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-secondary">Nama Pembimbing</label>
                                <input type="text" name="supervisor_name" class="form-control" placeholder="Nama lengkap & gelar" required>
                                <small class="text-muted" style="font-size: 0.75rem;">Gunakan tanda koma (,) atau titik koma (;) untuk lebih dari satu pembimbing.</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-secondary">Tahun Lulus</label>
                                <input type="number" name="year" class="form-control" value="<?php echo date('Y'); ?>" required>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold text-secondary">Abstrak</label>
                            <textarea name="abstract" class="form-control" rows="8" placeholder="Tuliskan abstrak skripsi di sini..." required></textarea>
                        </div>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary">Kata Kunci</label>
                                <input type="text" name="keywords" class="form-control" placeholder="AI, Machine Learning, Web Development" required>
                                <small class="text-muted" style="font-size: 0.75rem;">Gunakan tanda koma (,) untuk memisahkan lebih dari satu kata kunci.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary">File Dokumen (PDF)</label>
                                <input type="file" name="file" class="form-control" accept=".pdf" required>
                            </div>
                        </div>
                        <button type="submit" name="submit" class="btn btn-primary w-100" <?php echo !empty($success) ? 'disabled' : ''; ?>>
                            <i class="fas fa-paper-plane me-2"></i> Ajukan Skripsi
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
