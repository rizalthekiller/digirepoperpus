<?php
session_start();
include '../includes/db.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../user/login.php");
    exit();
}

$message = "";
if (isset($_SESSION['queue_msg'])) {
    $message = $_SESSION['queue_msg'];
    unset($_SESSION['queue_msg']);
}

// Handle Action (Approve/Reject)
if (isset($_POST['action'])) {
    $thesis_id = $conn->real_escape_string($_POST['thesis_id']);
    $action = $_POST['action'];
    $status = ($action == 'approve') ? 'approved' : 'rejected';
    $reason = isset($_POST['reason']) ? $conn->real_escape_string($_POST['reason']) : '';
    $cert_number = isset($_POST['cert_number']) ? $conn->real_escape_string($_POST['cert_number']) : '';
    $cert_content = isset($_POST['cert_content']) ? $_POST['cert_content'] : ''; // Content can have HTML

    // If rejected, mark as was_rejected = 1
    $was_rejected_part = ($status == 'rejected') ? ", was_rejected = 1" : "";
    
    // If approving with certificate
    $v_hash = isset($_POST['verification_hash']) ? $conn->real_escape_string($_POST['verification_hash']) : "";
    $cert_saved_content = "";
    if (empty($v_hash) && !empty($cert_number)) {
        $v_hash = bin2hex(random_bytes(16)); // 32 chars unique hash
    }
    
    if (!empty($cert_number)) {
        $cert_saved_content = $conn->real_escape_string($cert_content);
    }

    $cert_part = (!empty($cert_number)) ? ", certificate_number = '$cert_number', verification_hash = '$v_hash', was_rejected = 0" : "";

    $sql = "UPDATE theses SET status = '$status', rejection_reason = '$reason' $was_rejected_part $cert_part WHERE id = '$thesis_id'";
    
    if ($conn->query($sql) === TRUE) {
        $msg_text = ($status == 'approved') ? "Skripsi disetujui!" : "Skripsi ditolak!";
        $_SESSION['queue_msg'] = "<div class='alert alert-success border-0 shadow-sm'>
            <i class='fas fa-check-circle me-2'></i> $msg_text (Sistem sedang memproses pengiriman email di latar belakang)
        </div>";

        // Fetch User and Thesis info
        $thesisQuery = $conn->query("SELECT t.title, t.year, u.email, u.name as author_name, u.nim, f.name as fac_name, d.name as dept_name 
                                     FROM theses t 
                                     JOIN users u ON t.user_id = u.id 
                                     JOIN departments d ON u.department_id = d.id
                                     JOIN faculties f ON d.faculty_id = f.id
                                     WHERE t.id = '$thesis_id'");
        $thesisData = $thesisQuery->fetch_assoc();

        // Security/Responsiveness: Send success header immediately if possible
        if (function_exists('fastcgi_finish_request')) {
            header("Location: verification_queue.php");
            session_write_close();
            fastcgi_finish_request();
            ignore_user_abort(true);
            set_time_limit(300); // Give it 5 minutes
        }

        require_once '../includes/notification_service.php';
        
        if ($status == 'approved') {
            $is_cert_sent = false;
            // Check if certificate was just generated
            if (!empty($cert_number) && !empty($cert_content)) {
                $v_link = "http://" . $_SERVER['HTTP_HOST'] . "/verify.php?h=" . $v_hash;
                // QR local proxy/cache or optimization could be here, but for now we optimize the flow
                $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=" . urlencode($v_link);
                $qr_img = "<div style='text-align: center; margin: 15px 0;'><img src='$qr_url' style='width: 80px; height: 80px;' alt='QR Verifikasi'><br><small style='color: #666; font-size: 8px;'>Scan Validasi</small></div>";
                
                // Final replacement of digital signature placeholders
                $final_content = str_replace('[QR_CODE]', $qr_img, $cert_content);
                $final_content = str_replace('[VERIF_LINK]', $v_link, $final_content);
                $final_content = str_replace('[FAKULTAS]', $thesisData['fac_name'], $final_content);

                // Add Logo [LOGO] with absolute URL
                $logo_img_html = "";
                if (!empty($settings['cert_header_img'])) {
                    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
                    $logo_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/" . $settings['cert_header_img'];
                    $logo_img_html = "<img src='$logo_url' style='width: 100%; height: auto; display: block;'>";
                }
                $final_content = str_replace('[LOGO]', $logo_img_html, $final_content);

                // Update the certificate_content in DB with final personalized content
                $final_db_content = $conn->real_escape_string($final_content);
                $conn->query("UPDATE theses SET certificate_content = '$final_db_content' WHERE id = '$thesis_id'");

                $subject = "Surat Keterangan Unggah Mandiri - $cert_number";
                // This combines the notification and certificate into one email
                $is_cert_sent = NotificationService::sendSelfUploadCertificate($thesisData['email'], $subject, $final_content, $thesisData['author_name']);
                
                // Update delivery status in DB
                $delivery_val = $is_cert_sent ? 'sent' : 'failed';
                $conn->query("UPDATE theses SET delivery_status = '$delivery_val' WHERE id = '$thesis_id'");

                // Update last number in settings
                $conn->query("UPDATE settings SET setting_value = '$cert_number' WHERE setting_key = 'cert_last_number'");
            }
            
            // Only send the generic "Approved" email if no certificate was sent to avoid double SMTP load
            if (!$is_cert_sent) {
                $is_notif_sent = NotificationService::notifyUserThesisResult($thesisData['email'], $thesisData['title'], 'approved');
                $delivery_val = $is_notif_sent ? 'sent' : 'failed';
                $conn->query("UPDATE theses SET delivery_status = '$delivery_val' WHERE id = '$thesis_id'");
            }
        } else {
            $is_rejected_notif_sent = NotificationService::notifyUserThesisResult($thesisData['email'], $thesisData['title'], 'rejected');
            $delivery_val = $is_rejected_notif_sent ? 'sent' : 'failed';
            $conn->query("UPDATE theses SET delivery_status = '$delivery_val' WHERE id = '$thesis_id'");
        }

        // If not using fastcgi_finish_request, redirect normally
        if (!function_exists('fastcgi_finish_request')) {
            header("Location: verification_queue.php");
            exit();
        }
    } else {
        $message = "<div class='alert alert-danger border-0 shadow-sm'>
            <i class='fas fa-exclamation-triangle me-2'></i> Error: " . $conn->error . "
        </div>";
    }
}

// Fetch Settings
$settings = [];
$res_settings = $conn->query("SELECT * FROM settings");
while($row_s = $res_settings->fetch_assoc()) {
    $settings[$row_s['setting_key']] = $row_s['setting_value'];
}

// Fetch Pending Theses
$sql = "SELECT t.*, u.name as author_name, u.nim, d.name as dept_name 
        FROM theses t 
        JOIN users u ON t.user_id = u.id 
        JOIN departments d ON u.department_id = d.id
        WHERE t.status = 'pending' 
        ORDER BY t.created_at ASC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Antrean Verifikasi - Admin DigiRepo</title>
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
        .table-card { border: none; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); background: white; overflow: hidden; border: 1px solid #f1f5f9; }
        .table thead th { background-color: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 15px 20px; font-size: 0.75rem; text-transform: uppercase; color: #64748b; letter-spacing: 0.025em; }
        .table tbody td { padding: 20px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
        .btn-action { border-radius: 10px; padding: 8px 16px; font-weight: 600; font-size: 0.85rem; }
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
            <a href="verification_queue.php" class="nav-link active"><i class="fas fa-clock"></i> Antrean Verifikasi</a>
            <a href="certificates_list.php" class="nav-link"><i class="fas fa-file-invoice"></i> Data Surat</a>
            <a href="theses.php" class="nav-link"><i class="fas fa-book"></i> Manajemen Skripsi</a>
            <a href="users.php" class="nav-link"><i class="fas fa-users"></i> Manajemen User</a>
            <a href="faculties.php" class="nav-link"><i class="fas fa-university"></i> Fakultas</a>
            <a href="departments.php" class="nav-link"><i class="fas fa-graduation-cap"></i> Program Studi</a>
            <a href="certificate_settings.php" class="nav-link"><i class="fas fa-certificate"></i> Pengaturan Surat</a>
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
            <h1 class="fw-bold mb-0">Antrean Verifikasi</h1>
            <div class="bg-white px-4 py-2 rounded-pill shadow-sm small fw-bold">
                <i class="fas fa-clock text-warning me-2"></i> <?php echo $result->num_rows; ?> Skripsi Menunggu
            </div>
        </div>

        <?php echo $message; ?>

        <div class="table-card mt-4">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Judul & Penulis</th>
                            <th>Info Prodi</th>
                            <th>Tanggal Unggah</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark mb-1"><?php echo $row['title']; ?></div>
                                        <div class="text-muted small">Oleh: <?php echo $row['author_name']; ?></div>
                                        <div class="text-secondary" style="font-size: 0.75rem;">Pembimbing: <?php echo $row['supervisor_name'] ?? '-'; ?></div>
                                        <?php if($row['file_path']): ?>
                                            <button type="button" class="btn btn-sm btn-link p-0 small text-primary text-decoration-none preview-btn" 
                                                    data-bs-toggle="modal" data-bs-target="#previewModal" 
                                                    data-file="../<?php echo $row['file_path']; ?>" 
                                                    data-title="<?php echo htmlspecialchars($row['title']); ?>">
                                                <i class="fas fa-eye me-1"></i> Pratinjau Cepat
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-primary border rounded-pill fw-bold" style="font-size: 0.7rem;">
                                            <?php echo $row['dept_name']; ?>
                                        </span>
                                        <div class="mt-1">
                                            <span class="badge bg-primary text-white border rounded-pill fw-bold" style="font-size: 0.65rem;">
                                                <?php echo $row['type']; ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="text-muted small">
                                        <?php echo date('d M Y, H:i', strtotime($row['created_at'])); ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2">
                                            <button type="button" class="btn btn-success btn-action approve-cert-btn" 
                                                    data-bs-toggle="modal" data-bs-target="#approveModal" 
                                                    data-id="<?php echo $row['id']; ?>" 
                                                    data-title="<?php echo htmlspecialchars($row['title']); ?>"
                                                    data-name="<?php echo htmlspecialchars($row['author_name']); ?>"
                                                    data-nim="<?php echo htmlspecialchars($row['nim'] ?? '-'); ?>"
                                                    data-supervisor="<?php echo htmlspecialchars($row['supervisor_name'] ?? ''); ?>"
                                                    data-year="<?php echo $row['year']; ?>"
                                                    data-dept="<?php echo htmlspecialchars($row['dept_name']); ?>"
                                                    data-fac-name="<?php echo htmlspecialchars($row['fac_name'] ?? ''); ?>">
                                                <i class="fas fa-certificate me-1"></i> Tinjau & Setujui
                                            </button>
                                            <button type="button" class="btn btn-outline-danger btn-action" 
                                                        data-bs-toggle="modal" data-bs-target="#rejectModal" 
                                                        data-id="<?php echo $row['id']; ?>" 
                                                        data-title="<?php echo htmlspecialchars($row['title']); ?>">
                                                <i class="fas fa-times me-1"></i> Tolak
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="fas fa-check-double fa-3x mb-3 opacity-25"></i>
                                    <p class="mb-0">Tidak ada skripsi di antrean verifikasi.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Preview Modal -->
    <div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
        <!-- ... existing preview modal content ... -->
    </div>

    <!-- Approve (Certificate) Modal -->
    <div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow">
                <form action="verification_queue.php" method="POST">
                    <div class="modal-header border-bottom-0 p-4">
                        <h5 class="modal-title fw-bold" id="approveModalLabel">Penerbitan Surat Keterangan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 pt-0">
                        <p class="text-muted small mb-4">Lengkapi data surat keterangan untuk mahasiswa ini sebelum menyetujui.</p>
                        
                        <input type="hidden" name="thesis_id" id="approve-thesis-id">
                        <input type="hidden" name="verification_hash" id="approve-v-hash">
                        <input type="hidden" name="action" value="approve">

                        <div class="row mb-3">
                            <div class="col-md-7">
                                <label class="form-label small fw-bold">No. Urut Surat</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 small">Perpus.B-</span>
                                    <input type="text" name="cert_sequence" id="approve-cert-seq" class="form-control" value="">
                                    <span class="input-group-text bg-light border-start-0 small" id="cert-suffix-preview">/Un.21/1/PP.009/...</span>
                                </div>
                                <input type="hidden" name="cert_number" id="approve-cert-number">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small fw-bold">Tanggal Surat</label>
                                <input type="date" name="cert_date" id="approve-cert-date" class="form-control rounded-3" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12 text-center">
                                <label class="form-label small fw-bold d-block">Preview QR Signature</label>
                                <div id="qr-preview-container" class="bg-light rounded-3 d-flex align-items-center justify-content-center" style="height: 100px; width: 100px; margin: 0 auto;">
                                    <i class="fas fa-qrcode fa-2x text-muted opacity-50"></i>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="cert_content" id="approve-cert-content">

                        <div class="mb-0 mt-3 border rounded-4 p-4 bg-light text-center" id="pdf-preview-area">
                            <div id="pdf-preview-idle">
                                <i class="fas fa-file-pdf fa-3x text-secondary opacity-25 mb-3"></i>
                                <p class="text-muted small">Klik tombol di bawah untuk melihat pratinjau surat dalam format PDF.</p>
                                <button type="button" id="btn-trigger-preview" class="btn btn-outline-primary btn-sm rounded-pill px-4 fw-bold">
                                    <i class="fas fa-eye me-2"></i> Tampilkan Pratinjau PDF
                                </button>
                            </div>

                            <div id="pdf-preview-loading" class="d-none">
                                <div class="spinner-border text-primary" role="status"></div>
                                <div class="mt-2 text-primary fw-bold small">Sedang Menyiapkan Pratinjau...</div>
                            </div>
                            
                            <iframe id="pdf-preview-iframe" src="about:blank" style="width: 100%; height: 500px; border: 1px solid #dee2e6; border-radius: 12px; display: none;"></iframe>
                            
                            <div id="pdf-preview-actions" class="d-none mt-2">
                                <button type="button" id="btn-refresh-preview" class="btn btn-link btn-sm text-decoration-none text-muted small">
                                    <i class="fas fa-sync-alt me-1"></i> Perbarui Pratinjau
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 p-4">
                        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" id="btn-submit-approve" class="btn btn-success rounded-pill px-4">
                            <i class="fas fa-paper-plane me-2"></i> Setujui & Kirim Surat
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow">
                <form action="verification_queue.php" method="POST">
                    <div class="modal-header border-bottom-0 p-4">
                        <h5 class="modal-title fw-bold" id="rejectModalLabel">Alasan Penolakan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 pt-0">
                        <p class="text-muted small mb-3">Tolak skripsi: <span id="reject-thesis-title" class="fw-bold"></span></p>
                        <input type="hidden" name="thesis_id" id="reject-thesis-id">
                        <input type="hidden" name="action" value="reject">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Berikan Alasan/Feedback ke Mahasiswa</label>
                            <textarea name="reason" class="form-control rounded-3" rows="4" placeholder="Contoh: Format PDF salah, abstrak kurang lengkap, dll." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 p-4">
                        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger rounded-pill px-4">Konfirmasi Tolak</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var previewModal = document.getElementById('previewModal');
            previewModal.addEventListener('show.bs.modal', function(event) {
                var button = event.relatedTarget;
                var fileUrl = button.getAttribute('data-file');
                var thesisTitle = button.getAttribute('data-title');

                var iframe = document.getElementById('pdf-viewer');
                var titleDisplay = document.getElementById('modal-thesis-title');
                var downloadBtn = document.getElementById('download-link');

                iframe.src = fileUrl;
                titleDisplay.textContent = thesisTitle;
                downloadBtn.href = fileUrl;
            });

            var rejectModal = document.getElementById('rejectModal');
            rejectModal.addEventListener('show.bs.modal', function(event) {
                var button = event.relatedTarget;
                var thesisId = button.getAttribute('data-id');
                var thesisTitle = button.getAttribute('data-title');

                document.getElementById('reject-thesis-id').value = thesisId;
                document.getElementById('reject-thesis-title').textContent = thesisTitle;
            });

            var approveModal = document.getElementById('approveModal');
            approveModal.addEventListener('show.bs.modal', function(event) {
                var button = event.relatedTarget;
                var thesisId = button.getAttribute('data-id');
                var thesisTitle = button.getAttribute('data-title');
                var authorName = button.getAttribute('data-name');
                var nim = button.getAttribute('data-nim');
                var supervisor = button.getAttribute('data-supervisor');
                var year = button.getAttribute('data-year');
                var dept = button.getAttribute('data-dept');
                var faculty = button.getAttribute('data-fac-name') || '-';

                document.getElementById('approve-thesis-id').value = thesisId;
                
                // Generate a random hash for verification
                var vHash = [...Array(32)].map(() => Math.floor(Math.random() * 16).toString(16)).join('');
                document.getElementById('approve-v-hash').value = vHash;

                // QR Preview Update
                var vLink = "http://" + window.location.host + "/verify.php?h=" + vHash;
                var qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=" + encodeURIComponent(vLink);
                document.getElementById('qr-preview-container').innerHTML = `<img src="${qrUrl}" alt="QR" style="width: 80px; height: 80px;">`;

                // Get current template and replace placeholders
                var template = <?php echo json_encode($settings['cert_template'] ?? ''); ?>;
                var lastNum = <?php echo json_encode($settings['cert_last_number'] ?? ''); ?>;
                
                // Roma months for number
                var romanMonths = ["I", "II", "III", "IV", "V", "VI", "VII", "VIII", "IX", "X", "XI", "XII"];
                var now = new Date();
                var monthRoman = romanMonths[now.getMonth()];
                var yearNow = now.getFullYear();

                // Simple auto-increment logic for the number
                var match = lastNum.match(/B-(\d+)/);
                var nextSeq = "001";
                if (match) {
                    var numPart = match[1];
                    nextSeq = (parseInt(numPart) + 1).toString().padStart(numPart.length, '0');
                }
                
                var suffix = "/Un.21/1/PP.009/" + monthRoman + "/" + yearNow;
                document.getElementById('cert-suffix-preview').textContent = suffix;
                document.getElementById('approve-cert-seq').value = nextSeq;

                var finalFormattedNum = "Perpus.B-" + nextSeq + suffix;

                var dateStr = now.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
                
                var content = template
                    .replace('[NAMA]', authorName)
                    .replace('[NIM]', nim)
                    .replace('[FAKULTAS]', faculty)
                    .replace('[PEMBIMBING]', supervisor)
                    .replace('[JUDUL]', thesisTitle)
                    .replace('[TAHUN]', year)
                    .replace('[PRODI]', dept)
                    .replace('[NOMOR_SURAT]', finalFormattedNum)
                    .replace('[TANGGAL]', dateStr);

                // Add Logo Image if exists
                var currentProtocol = window.location.protocol + "//";
                var logoImg = <?php 
                    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
                    echo json_encode(!empty($settings['cert_header_img']) ? '<img src="' . $protocol . '://' . $_SERVER['HTTP_HOST'] . '/' . $settings['cert_header_img'] . '" style="width: 100%; height: auto; display: block;">' : ''); 
                ?>;
                content = content.replace('[LOGO]', logoImg);
                content = content.replace('[QR_CODE]', `<img src="${qrUrl}" style="width: 80px; height: 80px;">`);
                content = content.replace('[VERIF_LINK]', vLink);

                document.getElementById('approve-cert-number').value = finalFormattedNum;
                document.getElementById('approve-cert-content').value = content;

                // Sync sequence changes to final number
                document.getElementById('approve-cert-seq').addEventListener('input', function() {
                    var seq = this.value;
                    var fullNum = "Perpus.B-" + seq + suffix;
                    document.getElementById('approve-cert-number').value = fullNum;
                    
                    // Live update content if possible
                    var currentContent = document.getElementById('approve-cert-content').value;
                    document.getElementById('approve-cert-content').value = currentContent.replace(/Perpus\.B\-[^\/]+/, "Perpus.B-" + seq);
                });

                // PDF PREVIEW LOGIC (Manual Trigger to Save Server Resources)
                function loadPdfPreview() {
                    const idle = document.getElementById('pdf-preview-idle');
                    const loading = document.getElementById('pdf-preview-loading');
                    const iframe = document.getElementById('pdf-preview-iframe');
                    const actions = document.getElementById('pdf-preview-actions');
                    const htmlContent = document.getElementById('approve-cert-content').value;

                    idle.classList.add('d-none');
                    loading.classList.remove('d-none');
                    iframe.style.display = 'none';
                    actions.classList.add('d-none');

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'preview_certificate.php';
                    form.target = 'pdf-preview-iframe';

                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'html';
                    input.value = htmlContent;
                    form.appendChild(input);

                    document.body.appendChild(form);
                    
                    iframe.onload = function() {
                        loading.classList.add('d-none');
                        iframe.style.display = 'block';
                        actions.classList.remove('d-none');
                        document.body.removeChild(form);
                    };

                    form.submit();
                }

                document.getElementById('btn-trigger-preview').addEventListener('click', loadPdfPreview);
                document.getElementById('btn-refresh-preview').addEventListener('click', loadPdfPreview);

                // Loading state for final submit
                document.querySelector('#approveModal form').addEventListener('submit', function() {
                    const btn = document.getElementById('btn-submit-approve');
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Memproses & Mengirim...';
                });
            });

            // Update content when date changes
            document.getElementById('approve-cert-date').addEventListener('change', function() {
                var newDate = new Date(this.value);
                var dateStr = newDate.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
                var content = document.getElementById('approve-cert-content').value;
                
                // This is a simple regex replace for the date line if it was standard, 
                // but since it's free-form, we might just let the user edit if they want specific dates.
                // However, let's try to update the [TANGGAL] placeholder again if they haven't manually edited too much.
            });

            // Clear iframe src when modal hides to stop any playback/loading
            previewModal.addEventListener('hide.bs.modal', function() {
                document.getElementById('pdf-viewer').src = '';
            });
        });
    </script>
</body>
</html>
