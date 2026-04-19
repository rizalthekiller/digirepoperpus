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
if (isset($_POST['update_settings'])) {
    $template = $conn->real_escape_string($_POST['cert_template']);
    $last_number = $conn->real_escape_string($_POST['cert_last_number']);

    // Handle KOP Image Upload (Replaces old logo system)
    if (isset($_FILES['header_img']) && $_FILES['header_img']['error'] == 0) {
        $target_dir = "../uploads/settings/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        // Remove old kop/logo if exists
        $old_logo_res = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'cert_header_img'");
        if ($old_logo_res->num_rows > 0) {
            $old_logo_path = $old_logo_res->fetch_assoc()['setting_value'];
            $full_old_path = "../" . $old_logo_path;
            if (file_exists($full_old_path) && !is_dir($full_old_path)) {
                @unlink($full_old_path);
            }
        }

        $file_ext = pathinfo($_FILES['header_img']['name'], PATHINFO_EXTENSION);
        $file_name = "kop_surat_" . time() . "." . $file_ext;
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($_FILES['header_img']['tmp_name'], $target_file)) {
            $db_path = "uploads/settings/" . $file_name;
            $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('cert_header_img', '$db_path') ON DUPLICATE KEY UPDATE setting_value = '$db_path'");
        }
    }

    $conn->query("UPDATE settings SET setting_value = '$template' WHERE setting_key = 'cert_template'");
    $conn->query("UPDATE settings SET setting_value = '$last_number' WHERE setting_key = 'cert_last_number'");
    
    $message = "<div class='alert alert-success border-0 shadow-sm'>Pengaturan berhasil diperbarui!</div>";
}

// Handle Reset Default Template (Updated to use Image KOP)
if (isset($_POST['reset_template'])) {
    $default_tpl = "<div style=\"font-family: Arial, Helvetica, sans-serif; color: #000; padding: 0; line-height: 1.5;\">
    <div style=\"text-align: center; margin-bottom: 0;\">
        [LOGO]
    </div>

    <div style=\"padding: 10px 40px;\">
        <div style=\"text-align: center; margin-bottom: 25px; margin-top: 10px;\">
            <h3 style=\"margin: 0; text-decoration: underline; text-transform: uppercase; font-size: 18px; font-weight: bold;\">SURAT KETERANGAN</h3>
            <p style=\"margin: 5px 0; font-weight: bold;\">Nomor : [NOMOR_SURAT]</p>
        </div>

        <div style=\"text-align: justify; margin-bottom: 20px;\">
            <p>Kepala Perpustakaan Universitas Islam Negeri Sultan Aji Muhammad Idris (UINSI) Samarinda menerangkan bahwa :</p>
            
            <table style=\"width: 100%; margin: 20px 0; border: none; border-collapse: collapse;\">
                <tr>
                    <td style=\"width: 150px; padding: 5px 0; vertical-align: top;\">NAMA</td>
                    <td style=\"width: 20px; vertical-align: top;\">:</td>
                    <td style=\"font-weight: bold; text-transform: uppercase; vertical-align: top;\">[NAMA]</td>
                </tr>
                <tr>
                    <td style=\"padding: 5px 0; vertical-align: top;\">N.I.M.</td>
                    <td style=\"vertical-align: top;\">:</td>
                    <td style=\"vertical-align: top;\">[NIM]</td>
                </tr>
                <tr>
                    <td style=\"padding: 5px 0; vertical-align: top;\">FAKULTAS</td>
                    <td style=\"vertical-align: top;\">:</td>
                    <td style=\"vertical-align: top;\">[FAKULTAS]</td>
                </tr>
                <tr>
                    <td style=\"padding: 5px 0; vertical-align: top;\">PRODI</td>
                    <td style=\"vertical-align: top;\">:</td>
                    <td style=\"vertical-align: top;\">[PRODI]</td>
                </tr>
            </table>

            <p>Yang bersangkutan telah selesai melakukan unggah Skripsi/Disertasi pada Repositori Perpustakaan Universitas Islam Negeri Sultan Aji Muhammad Idris (UINSI) Samarinda.</p>
            <p>Demikian Surat Keterangan ini diberikan, agar dapat dipergunakan sebagaimana mestinya.</p>
        </div>

        <table style=\"width: 100%; margin-top: 30px; border: none; border-collapse: collapse;\">
            <tr>
                <td style=\"width: 55%;\"></td>
                <td style=\"text-align: left;\">
                    <p style=\"margin: 0;\">Samarinda, [TANGGAL]</p>
                    <p style=\"margin: 0;\">Kepala Perpustakaan,</p>
                    <div style=\"margin: 10px 0;\">[QR_CODE]</div>
                    <p style=\"margin: 0; font-weight: bold; text-decoration: underline;\">Abdurrahman, S.Ag., S.S., M.Hum.</p>
                    <p style=\"margin: 0;\">NIP. 197005112003121002</p>
                </td>
            </tr>
        </table>
    </div>
</div>";
    $conn->query("UPDATE settings SET setting_value = '" . $conn->real_escape_string($default_tpl) . "' WHERE setting_key = 'cert_template'");
    $message = "<div class='alert alert-info border-0 shadow-sm'>Template telah dikembalikan ke standar UINSI (Mode Gambar Kop)!</div>";
}

// Fetch Settings
$settings = [];
$res = $conn->query("SELECT * FROM settings");
while($row = $res->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Kop & Surat - Admin DigiRepo</title>
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
            <a href="certificate_settings.php" class="nav-link active"><i class="fas fa-certificate"></i> Pengaturan Surat</a>
            <a href="site_settings.php" class="nav-link"><i class="fas fa-cogs"></i> Pengaturan Situs</a>
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
            <h1 class="fw-bold mb-0">Pengaturan Kop Gambar</h1>
            <div class="text-muted small">Optimasi: PDF Local Path Enabled</div>
        </div>
        
        <?php echo $message; ?>

        <div class="card p-5">
            <form action="certificate_settings.php" method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="form-label fw-bold small text-secondary text-uppercase tracking-wider">Nomor Surat Terakhir / Format</label>
                        <input type="text" name="last_number" class="form-control py-3 rounded-3 border-light-subtle shadow-sm" value="<?php echo htmlspecialchars($settings['cert_last_number'] ?? ''); ?>" placeholder="Contoh: 001/UNIV/2026">
                    </div>
                    <div class="col-md-6 mb-4">
                        <label class="form-label fw-bold small text-secondary text-uppercase tracking-wider">Upload Gambar Kop Surat (Full Width)</label>
                        <input type="file" name="header_img" class="form-control py-3 rounded-3 border-light-subtle shadow-sm">
                        <?php if(!empty($settings['cert_header_img'])): ?>
                            <div class="mt-3 text-center bg-light-subtle border p-3 rounded-4 shadow-sm">
                                <img src="../<?php echo $settings['cert_header_img']; ?>" class="img-fluid rounded border" style="max-height: 150px; width: 100%; object-fit: contain;">
                                <div class="form-text mt-2 small text-success"><i class="fas fa-check-circle"></i> Kop Gambar Aktif. Gunakan <code>[LOGO]</code> di template.</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold small text-secondary text-uppercase tracking-wider">Template Isi Surat</label>
                    <textarea name="cert_template" class="form-control rounded-4 border-light-subtle shadow-sm" style="font-family: 'Courier New', Courier, monospace; font-size: 13px;" rows="15" required><?php echo htmlspecialchars($settings['cert_template'] ?? ''); ?></textarea>
                    <div class="mt-3 bg-primary-subtle border border-primary-subtle p-3 rounded-4">
                        <div class="fw-bold text-primary mb-2 small"><i class="fas fa-info-circle me-1"></i> TIPS RESPONSIVE:</div>
                        <p class="small text-muted mb-0">PDF sekarang di-generate menggunakan jalur file lokal untuk kecepatan maksimal. Jangan gunakan URL eksternal di dalam template jika ingin proses cepat.</p>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-5">
                    <button type="submit" name="reset_template" class="btn btn-outline-secondary px-4 py-2 rounded-pill fw-bold border-2" onclick="return confirm('Ganti template ke mode Gambar Kop (Full Image)? Semua desain HTML Kop lama akan hilang.')">
                        <i class="fas fa-image me-2"></i> Mode Gambar Kop
                    </button>
                    <button type="submit" name="update_settings" class="btn btn-primary px-5 py-3 rounded-pill fw-bold shadow">
                        <i class="fas fa-save me-2"></i> Simpan Pengaturan
                    </button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
