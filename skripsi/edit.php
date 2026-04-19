<?php
session_start();
include '../includes/db.php';

// Check login and role
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'mahasiswa' && $_SESSION['role'] != 'dosen' && $_SESSION['role'] != 'admin')) {
    header("Location: ../user/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$id = isset($_GET['id']) ? $conn->real_escape_string($_GET['id']) : '';

if (!$id) {
    header("Location: ../user/dashboard.php");
    exit();
}

// Check for URL notification parameters
$success = null;
$error = null;

// Check session for success message (from previous redirect)
if (isset($_SESSION['edit_success'])) {
    $success = $_SESSION['edit_success'];
    unset($_SESSION['edit_success']);
}

// Check URL for success parameter
if (isset($_GET['success']) && $_GET['success'] == '1') {
    if (empty($success)) {
        $success = "Pengajuan berhasil diperbarui dan dikirim kembali untuk verifikasi!";
    }
}

// Check URL for error parameter
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}

$submitDisabled = !empty($success);

// Fetch existing data - Allow access if:
// 1. User is mahasiswa and owns the thesis
// 2. User is dosen and thesis belongs to their department
// 3. User is admin (can access any thesis)
$sql = "SELECT t.* FROM theses t";
if ($_SESSION['role'] == 'mahasiswa') {
    $sql .= " WHERE t.id = '$id' AND t.user_id = '$user_id'";
} elseif ($_SESSION['role'] == 'dosen') {
    $sql .= " JOIN users u ON t.user_id = u.id 
              WHERE t.id = '$id' AND u.department_id = (SELECT department_id FROM users WHERE id = '$user_id' LIMIT 1)";
} else {
    // admin
    $sql .= " WHERE t.id = '$id'";
}

$result = $conn->query($sql);

if ($result->num_rows == 0) {
    die("<div class='alert alert-danger m-5'><strong>Error:</strong> Akses ditolak atau data tidak ditemukan. Anda hanya dapat mengedit karya milik Anda sendiri.</div>");
}

$row = $result->fetch_assoc();

// Prevent editing if approved
if ($row['status'] == 'approved') {
    die("<div class='alert alert-warning m-5'><strong>Informasi:</strong> Skripsi yang sudah diverifikasi tidak dapat diubah. Silakan hubungi admin jika perlu perubahan.</div>");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $conn->real_escape_string($_POST['title']);
    $type = $conn->real_escape_string($_POST['type']);
    $year = (int)$_POST['year']; // Convert to integer
    $abstract = $conn->real_escape_string($_POST['abstract']);
    $keywords = $conn->real_escape_string($_POST['keywords']);
    $supervisor_name = $conn->real_escape_string($_POST['supervisor_name']);
    
    $file_path = $row['file_path']; // Default to old path

    // Determine if we are updating or inserting a new revision
    $is_rejected = ($row['status'] == 'rejected');
    $new_id = $is_rejected ? uniqid() : $id;

    // If old file is missing, require a new upload
    if (empty($_FILES['file']['name'])) {
        if (empty($file_path) || !file_exists("../" . $file_path)) {
            $error = "File skripsi lama tidak ditemukan. Silakan unggah file PDF baru.";
        }
    }

    // File upload logic if new file is provided
    if (!empty($_FILES['file']['name'])) {
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
            $file_name = $new_id . ".pdf"; // Forced extension for safety
            $target_file = $target_dir . $file_name;
            
            if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
                $new_file_path = "uploads/theses/" . $file_name;
                // Delete old file if updating and filename is different
                if (!$is_rejected && !empty($row['file_path']) && $row['file_path'] != $new_file_path && file_exists("../" . $row['file_path'])) {
                    @unlink("../" . $row['file_path']);
                }
                $file_path = $new_file_path;
            } else {
                $error = "Gagal memindahkan file yang diunggah.";
            }
        }
    } else {
        // Copy old file if exists and we are creating a new revision
        if (empty($error) && $is_rejected) {
            $target_dir = "../uploads/theses/";
            $file_name = $new_id . ".pdf";
            $target_file = $target_dir . $file_name;
            if (copy("../" . $row['file_path'], $target_file)) {
                $file_path = "uploads/theses/" . $file_name;
            } else {
                $error = "Gagal menyalin file skripsi lama.";
            }
        }
    }

        // Update only if the upload validation did not produce an error (and no error from URL param)
        if (empty($error) && !isset($_GET['error'])) {
            if ($is_rejected) {
                $insert_sql = "INSERT INTO theses (id, user_id, title, type, year, abstract, keywords, supervisor_name, file_path, status, created_at) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
                $stmt = $conn->prepare($insert_sql);
                $uid = $row['user_id'];
                $stmt->bind_param("ssssissss", $new_id, $uid, $title, $type, $year, $abstract, $keywords, $supervisor_name, $file_path);
            } else {
                $update_sql = "UPDATE theses SET 
                               title = ?, 
                               type = ?, 
                               year = ?, 
                               abstract = ?, 
                               keywords = ?, 
                               supervisor_name = ?,
                               file_path = ?,
                               status = 'pending',
                               rejection_reason = '',
                               was_rejected = 0,
                               created_at = NOW()
                               WHERE id = ?";
                $stmt = $conn->prepare($update_sql);
                $stmt->bind_param("ssisssss", $title, $type, $year, $abstract, $keywords, $supervisor_name, $file_path, $id);
            }

        if ($stmt->execute()) {
            // Notify Admin if it's a new revision
            if ($is_rejected) {
                require_once '../includes/notification_service.php';
                NotificationService::notifyAdminNewThesis($_SESSION['name'], $title);
            }

            $msg = "Pengajuan berhasil diperbarui dan dikirim kembali untuk verifikasi!";
            $stmt->close();
            // Use session to store message across redirect
            $_SESSION['edit_success'] = $msg;
            header("Location: ../user/dashboard.php", true, 303);
            exit();
        } else {
            $error = "Gagal memperbarui: " . $stmt->error;
        }
        $stmt->close();
    }
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
                                <small class="text-muted" style="font-size: 0.75rem;">Gunakan tanda koma (,) atau titik koma (;) untuk lebih dari satu pembimbing.</small>
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
                                <small class="text-muted" style="font-size: 0.75rem;">Gunakan tanda koma (,) untuk memisahkan lebih dari satu kata kunci.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary">Ganti File PDF (Opsional)</label>
                                <input type="file" name="file" class="form-control" accept=".pdf">
                                <small class="text-muted">Biarkan kosong jika tidak ingin mengubah file.</small>
                            </div>
                        </div>
                        <?php if (!empty($row['file_path']) && file_exists('../' . $row['file_path'])): ?>
                            <div class="mb-4 p-3 bg-light rounded-4 border">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div>
                                        <span class="fw-bold">File PDF Saat Ini</span>
                                        <div class="text-muted small">Klik untuk membuka atau mengunduh file skripsi Anda sebelum menyimpan perubahan.</div>
                                    </div>
                                    <span class="badge bg-success text-white">Tersedia</span>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="../<?php echo htmlspecialchars($row['file_path']); ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-4">
                                        <i class="fas fa-eye me-1"></i> Lihat PDF
                                    </a>
                                    <a href="../<?php echo htmlspecialchars($row['file_path']); ?>" download class="btn btn-sm btn-outline-success rounded-pill px-4">
                                        <i class="fas fa-download me-1"></i> Unduh PDF
                                    </a>
                                </div>
                            </div>
                        <?php elseif (!empty($row['file_path'])): ?>
                            <div class="alert alert-warning border-0 rounded-3 mb-4">
                                <i class="fas fa-exclamation-triangle me-2"></i> File PDF saat ini tidak ditemukan di server. Silakan unggah file baru untuk menggantinya.
                            </div>
                        <?php endif; ?>
                        <button type="submit" id="edit-submit-btn" name="submit" class="btn btn-primary w-100" <?php echo !empty($success) ? 'disabled' : ''; ?>>
                            <i class="fas fa-save me-2"></i> Simpan Perubahan
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var editForm = document.querySelector('form');
            var submitBtn = document.getElementById('edit-submit-btn');
            if (editForm && submitBtn) {
                editForm.addEventListener('submit', function() {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Mengirim...';
                });
            }
        });
    </script>
</body>
</html>
