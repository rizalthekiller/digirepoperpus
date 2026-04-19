<?php
include '../includes/db.php';
session_start();

$id = isset($_GET['id']) ? $conn->real_escape_string($_GET['id']) : '';

if (!$id) {
    header("Location: ../index.php");
    exit();
}

$sql = "SELECT t.*, u.name as author, d.name as dept_name, f.name as faculty_name 
        FROM theses t 
        JOIN users u ON t.user_id = u.id 
        JOIN departments d ON u.department_id = d.id 
        JOIN faculties f ON d.faculty_id = f.id
        WHERE t.id = '$id' AND t.status = 'approved'";

$result = $conn->query($sql);

if ($result->num_rows == 0) {
    die("Skripsi tidak ditemukan atau belum disetujui.");
}

$row = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $row['title']; ?> - DigiRepo</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .detail-card { border: none; border-radius: 24px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); background: white; padding: 40px; }
        .badge-keyword { background-color: #e0e7ff; color: #4338ca; font-weight: 600; }
        .btn-download { background-color: #1e3a8a; border: none; border-radius: 12px; padding: 15px 30px; font-weight: 700; color: white; display: inline-flex; align-items: center; text-decoration: none; }
        .btn-download:hover { background-color: #1e40af; color: white; transform: translateY(-2px); transition: 0.2s; }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="detail-card">
                    <div class="mb-4">
                        <span class="badge bg-light text-primary border rounded-pill px-3 py-2 mb-3">
                            <i class="fas fa-university me-1"></i> <?php echo $row['faculty_name']; ?> • <?php echo $row['dept_name']; ?>
                        </span>
                        <span class="badge bg-primary text-white border rounded-pill px-3 py-2 mb-3 ms-2">
                            <i class="fas fa-graduation-cap me-1"></i> <?php echo $row['type']; ?>
                        </span>
                        <h1 class="fw-bold mb-3"><?php echo $row['title']; ?></h1>
                        <div class="d-flex flex-wrap align-items-center text-muted border-bottom pb-4 mb-4 gap-4">
                            <div><i class="fas fa-user me-1"></i> <b>Penulis:</b> <?php echo $row['author']; ?></div>
                            <div><i class="fas fa-user-tie me-1"></i> <b>Pembimbing:</b> <?php echo $row['supervisor_name'] ?? '-'; ?></div>
                            <div><i class="fas fa-calendar me-1"></i> <b>Tahun:</b> <?php echo $row['year']; ?></div>
                            <div><i class="fas fa-check-circle text-success me-1"></i> Terverifikasi Perpustakaan</div>
                        </div>
                    </div>

                    <div class="row g-5">
                        <div class="col-lg-8">
                            <h5 class="fw-bold mb-3">Abstrak</h5>
                            <p class="text-secondary lh-lg" style="text-align: justify; white-space: pre-line;">
                                <?php echo $row['abstract']; ?>
                            </p>

                            <div class="mt-5">
                                <h6 class="fw-bold mb-3">Kata Kunci:</h6>
                                <div>
                                    <?php 
                                    $keywords = explode(',', $row['keywords']);
                                    foreach($keywords as $kw) {
                                        if(trim($kw)) echo '<span class="badge badge-keyword me-2 px-3 py-2">#' . trim($kw) . '</span>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <!-- Actions Section -->
                            <div class="card bg-white border rounded-4 p-4 shadow-sm mb-4">
                                <h6 class="fw-bold mb-3 text-dark"><i class="fas fa-bolt me-2 text-warning"></i>Actions</h6>
                                <div class="list-group list-group-flush small">
                                    <?php if(isset($_SESSION['user_id'])): ?>
                                        <div class="list-group-item d-flex align-items-center py-3">
                                            <i class="fas fa-check-circle text-success me-3"></i>
                                            <span>Anda masuk sebagai <strong><?php echo $_SESSION['role']; ?></strong></span>
                                        </div>
                                    <?php else: ?>
                                        <div class="list-group-item d-flex align-items-center py-3">
                                            <i class="fas fa-lock text-muted me-3"></i>
                                            <span>Silakan <a href="../user/login.php" class="fw-bold text-primary">Login</a> untuk akses penuh</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="card bg-light border-0 rounded-4 p-4 sticky-top" style="top: 100px;">
                                <h6 class="fw-bold mb-3 text-dark">Akses Dokumen</h6>
                                <p class="small text-muted mb-4">
                                    <?php if(isset($_SESSION['user_id'])): ?>
                                        File ini tersedia dalam format PDF dan dapat diunduh untuk kebutuhan referensi akademik.
                                    <?php else: ?>
                                        <span class="text-danger fw-bold">Restricted:</span> Akses file PDF hanya tersedia untuk pengguna terdaftar (Registered Users Only).
                                    <?php endif; ?>
                                </p>
                                
                                <?php if($row['file_path']): ?>
                                    <?php if(isset($_SESSION['user_id'])): ?>
                                        <a href="../<?php echo $row['file_path']; ?>" class="btn-download w-100 justify-content-center mb-3" download>
                                            <i class="fas fa-file-pdf me-2" style="font-size: 1.2rem;"></i> Unduh Skripsi (PDF)
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-secondary w-100 rounded-3 mb-3 py-3 fw-bold opacity-75" disabled>
                                            <i class="fas fa-lock me-2"></i> Login required to view PDF
                                        </button>
                                        <a href="../user/login.php" class="btn btn-primary w-100 rounded-pill mb-3 fw-bold">Login Sekarang</a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="alert alert-warning small border-0 py-2">
                                        <i class="fas fa-exclamation-triangle me-1"></i> Dokumen digital tidak tersedia.
                                    </div>
                                <?php endif; ?>

                                <!-- Permanent URL -->
                                <div class="mt-4 pt-4 border-top">
                                    <h6 class="fw-bold small text-muted mb-2">URI Repository:</h6>
                                    <div class="bg-white border p-2 rounded small text-break font-monospace">
                                        <?php 
                                        $site_url = $settings['site_url'] ?? ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST']);
                                        $uri = rtrim($site_url, '/') . "/id/eprint/" . $row['id'];
                                        echo $uri;
                                        ?>
                                    </div>
                                </div>
                                
                                <button onclick="window.print()" class="btn btn-outline-secondary w-100 rounded-3 small mt-3">
                                    <i class="fas fa-print me-1"></i> Cetak Metadata
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
