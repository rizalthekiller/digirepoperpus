<?php
session_start();
include '../includes/db.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../user/login.php");
    exit();
}

$message = "";

// Handle Settings Update
if (isset($_POST['update_site_settings'])) {
    $site_url = $conn->real_escape_string($_POST['site_url']);
    $home_url = $conn->real_escape_string($_POST['home_url']);
    $site_name = $conn->real_escape_string($_POST['site_name']);
    $site_tagline = $conn->real_escape_string($_POST['site_tagline']);
    $footer_text = $conn->real_escape_string($_POST['footer_text']);

    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('site_url', '$site_url') ON DUPLICATE KEY UPDATE setting_value = '$site_url'");
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('home_url', '$home_url') ON DUPLICATE KEY UPDATE setting_value = '$home_url'");
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('site_name', '$site_name') ON DUPLICATE KEY UPDATE setting_value = '$site_name'");
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('site_tagline', '$site_tagline') ON DUPLICATE KEY UPDATE setting_value = '$site_tagline'");
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('footer_about', '$footer_text') ON DUPLICATE KEY UPDATE setting_value = '$footer_text'");
    
    // Handle Logo Upload
    if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] == 0) {
        $target_dir = "../uploads/settings/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        $file_ext = pathinfo($_FILES['site_logo']['name'], PATHINFO_EXTENSION);
        $file_name = "site_logo_" . time() . "." . $file_ext;
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($_FILES['site_logo']['tmp_name'], $target_file)) {
            $db_path = "uploads/settings/" . $file_name;
            $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('site_logo', '$db_path') ON DUPLICATE KEY UPDATE setting_value = '$db_path'");
        }
    }

    $message = "<div class='alert alert-success border-0 shadow-sm'>Pengaturan Situs berhasil diperbarui!</div>";
}

// Fetch Settings
$settings = [];
$res = $conn->query("SELECT * FROM settings");
while($row = $res->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Default values if not set
if(!isset($settings['site_url'])) $settings['site_url'] = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
if(!isset($settings['home_url'])) $settings['home_url'] = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Situs - Admin DigiRepo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #1e293b; }
        .sidebar { width: 280px; height: 100vh; position: fixed; background: white; border-right: 1px solid #e2e8f0; padding: 30px 0; z-index: 1000; }
        .sidebar-brand { padding: 0 30px 40px 30px; border-bottom: 1px solid #f1f5f9; }
        .sidebar-nav .nav-link { color: #64748b; padding: 12px 30px; font-weight: 600; transition: 0.3s; text-decoration: none; display: flex; align-items: center; gap: 12px; }
        .sidebar-nav .nav-link i { width: 20px; text-align: center; font-size: 1.1rem; }
        .sidebar-nav .nav-link:hover { color: #2563eb; background: #f1f5f9; }
        .sidebar-nav .nav-link.active { color: #2563eb; background: #eff6ff; border-right: 4px solid #2563eb; }
        .main-content { margin-left: 280px; padding: 40px 60px; }
        .card { border: none; border-radius: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); border: 1px solid #f1f5f9; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-brand text-start">
            <h2 class="fw-bold mb-0 text-primary">DigiRepo.</h2>
            <small class="text-muted fw-semibold">ADMINISTRATOR</small>
        </div>
        <div class="sidebar-nav mt-5">
            <a href="dashboard.php" class="nav-link"><i class="fas fa-th-large"></i> Overview</a>
            <a href="verification_queue.php" class="nav-link"><i class="fas fa-clock"></i> Antrean Verifikasi</a>
            <a href="certificates_list.php" class="nav-link"><i class="fas fa-file-invoice"></i> Data Surat</a>
            <a href="theses.php" class="nav-link"><i class="fas fa-book"></i> Manajemen Skripsi</a>
            <a href="users.php" class="nav-link"><i class="fas fa-users"></i> Manajemen User</a>
            <a href="faculties.php" class="nav-link"><i class="fas fa-university"></i> Fakultas</a>
            <a href="departments.php" class="nav-link"><i class="fas fa-graduation-cap"></i> Program Studi</a>
            <a href="certificate_settings.php" class="nav-link"><i class="fas fa-certificate"></i> Pengaturan Surat</a>
            <a href="site_settings.php" class="nav-link active"><i class="fas fa-cogs"></i> Pengaturan Situs</a>
            <a href="reports.php" class="nav-link"><i class="fas fa-file-alt"></i> Laporan</a>
            <div class="px-4 mt-5">
                <a href="../logout.php" class="btn btn-outline-danger w-100 rounded-pill fw-bold small py-2">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </a>
            </div>
        </div>
    </div>

    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <h1 class="fw-bold mb-0">Pengaturan Situs</h1>
        </div>
        
        <?php echo $message; ?>

        <div class="card p-5">
            <form action="site_settings.php" method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="form-label fw-bold small text-secondary">Site Name</label>
                        <input type="text" name="site_name" class="form-control py-3 rounded-3" value="<?php echo htmlspecialchars($settings['site_name'] ?? 'DigiRepo'); ?>" required>
                    </div>
                    <div class="col-md-6 mb-4">
                        <label class="form-label fw-bold small text-secondary">Site Tagline</label>
                        <input type="text" name="site_tagline" class="form-control py-3 rounded-3" value="<?php echo htmlspecialchars($settings['site_tagline'] ?? 'Repositori Digital UINSI'); ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="form-label fw-bold small text-secondary">Site URL (Base URL)</label>
                        <input type="url" name="site_url" class="form-control py-3 rounded-3" value="<?php echo htmlspecialchars($settings['site_url']); ?>" placeholder="https://example.com" required>
                    </div>
                    <div class="col-md-6 mb-4">
                        <label class="form-label fw-bold small text-secondary">Home URL (Frontend)</label>
                        <input type="url" name="home_url" class="form-control py-3 rounded-3" value="<?php echo htmlspecialchars($settings['home_url']); ?>" placeholder="https://example.com" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="form-label fw-bold small text-secondary text-uppercase tracking-wider">Logo Website</label>
                        <input type="file" name="site_logo" class="form-control py-3 rounded-3 border-light-subtle shadow-sm">
                        <?php if(!empty($settings['site_logo'])): ?>
                            <div class="mt-3 text-center bg-light-subtle border p-3 rounded-4 shadow-sm">
                                <img src="../<?php echo $settings['site_logo']; ?>" class="img-fluid rounded border" style="max-height: 80px; object-fit: contain;">
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6 mb-4">
                        <label class="form-label fw-bold small text-secondary">Tentang Perpustakaan (Footer)</label>
                        <textarea name="footer_text" class="form-control rounded-4" rows="4"><?php echo htmlspecialchars($settings['footer_about'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="text-end mt-4">
                    <button type="submit" name="update_site_settings" class="btn btn-primary px-5 py-3 rounded-pill fw-bold">
                        <i class="fas fa-save me-2"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
