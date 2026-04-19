<?php
session_start();
include '../includes/db.php';

// Check login and role
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'mahasiswa' && $_SESSION['role'] != 'dosen')) {
    header("Location: ../user/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$id = isset($_GET['id']) ? $conn->real_escape_string($_GET['id']) : '';

if (!$id) {
    header("Location: ../user/dashboard.php");
    exit();
}

// Fetch existing data
$sql = "SELECT * FROM theses WHERE id = '$id' AND user_id = '$user_id'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    die("Akses ditolak atau data tidak ditemukan.");
}

$row = $result->fetch_assoc();

// Prevent editing if approved
if ($row['status'] == 'approved') {
    die("Skripsi yang sudah diverifikasi tidak dapat diubah. Silakan hubungi admin.");
}

if (isset($_POST['submit'])) {
    $title = $conn->real_escape_string($_POST['title']);
    $type = $conn->real_escape_string($_POST['type']);
    $year = $conn->real_escape_string($_POST['year']);
    $abstract = $conn->real_escape_string($_POST['abstract']);
    $keywords = $conn->real_escape_string($_POST['keywords']);
    $supervisor_name = $conn->real_escape_string($_POST['supervisor_name']);
    
    $file_path = $row['file_path']; // Default to old path

    // File upload logic if new file is provided
    if ($_FILES['file']['name']) {
        $target_dir = "../uploads/theses/";
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
            if ($mime != "application/pdf") {
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
                // Delete old file if exists
                if (file_exists("../" . $row['file_path'])) {
                    @unlink("../" . $row['file_path']);
                }
                $file_path = "uploads/theses/" . $file_name;
            } else {
                $error = "Gagal memindahkan file yang diunggah.";
            }
        }
    }

    // Menggunakan Prepared Statement untuk keamanan
    // Jika status sebelumnya 'rejected', maka reset kembali ke 'pending' agar admin bisa verifikasi ulang
    $update_sql = "UPDATE theses SET 
                   title = ?, 
                   type = ?, 
                   year = ?, 
                   abstract = ?, 
                   keywords = ?, 
                   supervisor_name = ?,
                   file_path = ?,
                   status = 'pending'
                   WHERE id = ?";
    
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ssisssss", $title, $type, $year, $abstract, $keywords, $supervisor_name, $file_path, $id);

    if ($stmt->execute()) {
        $success = "Pengajuan berhasil diperbarui dan dikirim kembali untuk verifikasi!";
        // Refresh data
        $row['title'] = $title;
        $row['type'] = $type;
        $row['year'] = $year;
        $row['abstract'] = $abstract;
        $row['keywords'] = $keywords;
        $row['supervisor_name'] = $supervisor_name;
        $row['status'] = 'pending';
    } else {
        $error = "Gagal memperbarui: " . $stmt->error;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pengajuan - DigiRepo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; }
        .form-card { border: none; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); background: white; padding: 40px; }
        .btn-primary { background-color: #1e3a8a; border: none; border-radius: 12px; padding: 12px 24px; font-weight: 700; }
        .form-control, .form-select { border-radius: 10px; padding: 12px; border: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm py-3" style="background-color: #0f172a;">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../index.php">DigiRepo.</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link text-white small fw-bold" href="../user/dashboard.php">Kembali ke Dashboard</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="form-card">
                    <h2 class="fw-bold mb-4">Edit Pengajuan Karya</h2>

                    <?php if(isset($success)): ?>
                        <div class="alert alert-success border-0 rounded-3 mb-4 fw-bold shadow-sm">
                            <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    <?php if(isset($error)): ?>
                        <div class="alert alert-danger border-0 rounded-3 mb-4 fw-bold shadow-sm">
                            <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form action="edit.php?id=<?php echo $id; ?>" method="POST" enctype="multipart/form-data">
                        <div class="mb-4">
                            <label class="form-label fw-bold text-secondary">Judul Lengkap</label>
                            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($row['title']); ?>" required>
                        </div>
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-secondary">Tipe Dokumen</label>
                                <select name="type" class="form-select" required>
                                    <option value="Skripsi" <?php echo $row['type'] == 'Skripsi' ? 'selected' : ''; ?>>Skripsi (S1)</option>
                                    <option value="Thesis" <?php echo $row['type'] == 'Thesis' ? 'selected' : ''; ?>>Thesis (S2)</option>
                                    <option value="Disertasi" <?php echo $row['type'] == 'Disertasi' ? 'selected' : ''; ?>>Disertasi (S3)</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-secondary">Nama Pembimbing</label>
                                <input type="text" name="supervisor_name" class="form-control" value="<?php echo htmlspecialchars($row['supervisor_name']); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-secondary">Tahun Lulus</label>
                                <input type="number" name="year" class="form-control" value="<?php echo $row['year']; ?>" required>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold text-secondary">Abstrak</label>
                            <textarea name="abstract" class="form-control" rows="8" required><?php echo htmlspecialchars($row['abstract']); ?></textarea>
                        </div>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary">Kata Kunci</label>
                                <input type="text" name="keywords" class="form-control" value="<?php echo htmlspecialchars($row['keywords']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary">Ganti File PDF (Opsional)</label>
                                <input type="file" name="file" class="form-control" accept=".pdf">
                                <small class="text-muted">Biarkan kosong jika tidak ingin mengubah file.</small>
                            </div>
                        </div>
                        <button type="submit" name="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save me-2"></i> Simpan Perubahan
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
