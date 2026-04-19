<?php
include 'includes/db.php';

$hash = isset($_GET['h']) ? $conn->real_escape_string($_GET['h']) : '';
$thesis = null;

if ($hash) {
    $sql = "SELECT t.*, u.name as author_name, u.nim, d.name as dept_name 
            FROM theses t 
            JOIN users u ON t.user_id = u.id 
            JOIN departments d ON u.department_id = d.id
            WHERE t.verification_hash = '$hash' AND t.status = 'approved'";
    $res = $conn->query($sql);
    if ($res->num_rows > 0) {
        $thesis = $res->fetch_assoc();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Digital Signature - DigiRepo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .verify-card { border: none; border-radius: 24px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); padding: 40px; width: 100%; max-width: 600px; background: white; text-align: center; }
        .status-header { border-radius: 16px; padding: 24px; margin-bottom: 30px; }
        .status-valid { background-color: #ecfdf5; color: #065f46; border: 1px solid #10b981; }
        .status-invalid { background-color: #fef2f2; color: #991b1b; border: 1px solid #ef4444; }
    </style>
</head>
<body>
    <div class="verify-card">
        <div class="mb-4">
            <h1 class="fw-bold text-primary">DigiRepo.</h1>
            <p class="text-muted">Verification Center</p>
        </div>

        <?php if ($thesis): ?>
            <div class="status-header status-valid">
                <i class="fas fa-check-circle fa-3x mb-3"></i>
                <h4 class="fw-bold mb-0">Surat Keterangan ASLI</h4>
                <p class="mb-0 small opacity-75">Sertifikat Digital Valid & Terverifikasi di Sistem</p>
            </div>

            <div class="text-start">
                <div class="mb-3">
                    <label class="small text-secondary fw-bold">Nomor Surat</label>
                    <div class="fw-bold text-dark"><?php echo $thesis['certificate_number']; ?></div>
                </div>
                <div class="mb-3">
                    <label class="small text-secondary fw-bold">Nama Mahasiswa</label>
                    <div class="fw-bold text-dark"><?php echo $thesis['author_name']; ?></div>
                </div>
                <div class="mb-3">
                    <label class="small text-secondary fw-bold">NIM</label>
                    <div class="fw-bold text-dark"><?php echo $thesis['nim']; ?></div>
                </div>
                <div class="mb-3">
                    <label class="small text-secondary fw-bold">Judul Karya Ilmiah</label>
                    <div class="fw-bold text-dark"><?php echo $thesis['title']; ?></div>
                </div>
                <div class="mb-3">
                    <label class="small text-secondary fw-bold">Program Studi / Tahun</label>
                    <div class="fw-bold text-dark"><?php echo $thesis['dept_name']; ?> / <?php echo $thesis['year']; ?></div>
                </div>
                <div class="mb-0">
                    <label class="small text-secondary fw-bold">Tanggal Verifikasi Sistem</label>
                    <div class="fw-bold text-dark text-muted">
                        <?php 
                            $v_date = !empty($thesis['updated_at']) ? $thesis['updated_at'] : $thesis['created_at'];
                            echo date('d F Y, H:i', strtotime($v_date)); 
                        ?> WIB
                    </div>
                </div>
            </div>
            
            <hr class="my-4 opacity-5">
            <p class="small text-muted mb-0">Dokumen ini telah ditandatangani secara digital oleh Perpustakaan melalui sistem repositori institusi.</p>

        <?php else: ?>
            <div class="status-header status-invalid">
                <i class="fas fa-times-circle fa-3x mb-3"></i>
                <h4 class="fw-bold mb-0">DATA TIDAK DITEMUKAN</h4>
                <p class="mb-0 small opacity-75">Sertifikat Digital Tidak Valid atau Tidak Terdaftar</p>
            </div>
            <p class="text-muted">Kode verifikasi yang Anda lampirkan tidak sesuai dengan catatan repositori kami. Mohon periksa kembali link atau QR Code Anda.</p>
            <a href="index.php" class="btn btn-secondary rounded-pill px-4 mt-3">Kembali ke Beranda</a>
        <?php endif; ?>
    </div>
</body>
</html>
