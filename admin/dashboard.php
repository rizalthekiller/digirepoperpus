<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../user/login.php");
    exit();
}

// Stats
$userCount = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
$thesisCount = $conn->query("SELECT COUNT(*) as total FROM theses WHERE status='approved'")->fetch_assoc()['total'];
$unverified = $conn->query("SELECT COUNT(*) as total FROM users WHERE is_verified=0")->fetch_assoc()['total'];
$pendingTheses = $conn->query("SELECT COUNT(*) as total FROM theses WHERE status='pending'")->fetch_assoc()['total'];
// Maintenance: Cleanup Orphan Files
if (isset($_POST['cleanup_files'])) {
    $target_dir = "../uploads/theses/";
    $deleted_count = 0;
    
    // Get all valid file paths from DB
    $valid_files = [];
    $res = $conn->query("SELECT file_path FROM theses");
    while($row = $res->fetch_assoc()) {
        if (!empty($row['file_path'])) {
            $valid_files[] = basename($row['file_path']);
        }
    }
    
    // Scan directory
    if (is_dir($target_dir)) {
        $files = scandir($target_dir);
        foreach ($files as $file) {
            if ($file != "." && $file != ".." && $file != ".gitkeep") {
                if (!in_array($file, $valid_files)) {
                    if (@unlink($target_dir . $file)) {
                        $deleted_count++;
                    }
                }
            }
        }
    }
    header("Location: dashboard.php?cleanup=" . $deleted_count);
    exit();
}

$cleanup_msg = "";
if (isset($_GET['cleanup'])) {
    $count = (int)$_GET['cleanup'];
    $cleanup_msg = "<div class='alert alert-info border-0 shadow-sm rounded-4 mb-4'><i class='fas fa-broom me-2'></i> Pembersihan Selesai: <b>$count</b> file sampah berhasil dihapus dari server.</div>";
}

// Stats by Type
$statsByType = $conn->query("SELECT type, COUNT(*) as count FROM theses GROUP BY type");
$typeLabels = [];
$typeData = [];
while($row = $statsByType->fetch_assoc()) {
    $typeLabels[] = $row['type'];
    $typeData[] = $row['count'];
}

// Stats by Dept (Top 5)
$statsByDept = $conn->query("SELECT d.name, COUNT(t.id) as count 
                             FROM departments d 
                             LEFT JOIN users u ON u.department_id = d.id 
                             LEFT JOIN theses t ON t.user_id = u.id 
                             GROUP BY d.id 
                             ORDER BY count DESC LIMIT 5");
$deptLabels = [];
$deptData = [];
while($row = $statsByDept->fetch_assoc()) {
    $deptLabels[] = $row['name'];
    $deptData[] = $row['count'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - DigiRepo</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #1e293b; }
        .sidebar { width: 280px; height: 100vh; position: fixed; background: white; border-right: 1px solid #e2e8f0; padding: 30px 0; z-index: 1000; }
        .sidebar-brand { padding: 0 30px 40px 30px; border-bottom: 1px solid #f1f5f9; }
        .sidebar-nav .nav-link { color: #64748b; padding: 12px 30px; font-weight: 600; transition: 0.3s; text-decoration: none; display: flex; align-items: center; gap: 12px; }
        .sidebar-nav .nav-link i { width: 20px; text-align: center; font-size: 1.1rem; }
        .sidebar-nav .nav-link:hover { color: #2563eb; background: #f1f5f9; }
        .sidebar-nav .nav-link.active { color: #2563eb; background: #eff6ff; border-right: 4px solid #2563eb; }
        .main-content { margin-left: 280px; padding: 40px 60px; }
        .stat-card { border: none; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); transition: 0.3s; background: white; border: 1px solid #f1f5f9; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .icon-box { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; }
        .chart-container { background: white; border-radius: 24px; padding: 35px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); margin-top: 30px; border: 1px solid #f1f5f9; }
        .badge-admin { background: #eff6ff; color: #1e40af; border: 1px solid #dbeafe; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-brand text-start">
            <h2 class="fw-bold mb-0 text-primary">DigiRepo.</h2>
            <small class="text-muted fw-semibold">ADMINISTRATOR</small>
        </div>
        <div class="sidebar-nav mt-5">
            <a href="dashboard.php" class="nav-link active"><i class="fas fa-th-large"></i> Overview</a>
            <a href="verification_queue.php" class="nav-link"><i class="fas fa-clock"></i> Antrean Verifikasi</a>
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
            <div>
                <h1 class="fw-bold mb-1">Overview Sistem</h1>
                <p class="text-muted mb-0 small"><i class="fas fa-user-circle me-1"></i> Administrator: <b><?php echo htmlspecialchars($_SESSION['name']); ?></b></p>
            </div>
            <div class="d-flex gap-2">
                <div class="badge-admin px-4 py-2 rounded-pill shadow-sm small fw-bold">
                    <i class="fas fa-user-shield me-2"></i> Admin Panel v2.0
                </div>
            </div>
        </div>

        <?php echo $cleanup_msg; ?>

        <div class="row g-4">
            <div class="col-md-3">
                <div class="card stat-card p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="icon-box bg-primary bg-opacity-10 text-primary">
                            <i class="fas fa-users"></i>
                        </div>
                        <span class="badge bg-success bg-opacity-10 text-success small">+12%</span>
                    </div>
                    <h2 class="fw-bold mb-0"><?php echo $userCount; ?></h2>
                    <p class="text-muted small fw-bold text-uppercase mb-0 mt-2">Total Pengguna</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="icon-box bg-success bg-opacity-10 text-success">
                            <i class="fas fa-book"></i>
                        </div>
                        <span class="badge bg-success bg-opacity-10 text-success small">Aktif</span>
                    </div>
                    <h2 class="fw-bold mb-0"><?php echo $thesisCount; ?></h2>
                    <p class="text-muted small fw-bold text-uppercase mb-0 mt-2">Koleksi Skripsi</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="icon-box bg-warning bg-opacity-10 text-warning">
                            <i class="fas fa-user-clock"></i>
                        </div>
                        <span class="badge bg-warning bg-opacity-10 text-warning small">Verifikasi</span>
                    </div>
                    <h2 class="fw-bold mb-0"><?php echo $unverified; ?></h2>
                    <p class="text-muted small fw-bold text-uppercase mb-0 mt-2">User Pending</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="icon-box bg-danger bg-opacity-10 text-danger">
                            <i class="fas fa-file-signature"></i>
                        </div>
                        <span class="badge bg-danger bg-opacity-10 text-danger small">Antrean</span>
                    </div>
                    <h2 class="fw-bold mb-0"><?php echo $pendingTheses; ?></h2>
                    <p class="text-muted small fw-bold text-uppercase mb-0 mt-2">Verifikasi Skripsi</p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-7">
                <div class="chart-container">
                    <h5 class="fw-bold mb-4">Top 5 Program Studi (Total Koleksi)</h5>
                    <canvas id="deptChart" height="250"></canvas>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="chart-container">
                    <h5 class="fw-bold mb-4">Distribusi Tipe Dokumen</h5>
                    <canvas id="typeChart" height="250"></canvas>
                </div>
                
                <!-- Maintenance Tool -->
                <div class="card stat-card mt-4 p-4 bg-light border-0">
                    <div class="d-flex align-items-center mb-3">
                        <div class="icon-box bg-dark bg-opacity-10 text-dark me-3">
                            <i class="fas fa-tools"></i>
                        </div>
                        <h6 class="fw-bold mb-0">System Maintenance</h6>
                    </div>
                    <p class="small text-muted mb-3">Hapus file PDF di server yang sudah tidak terhubung dengan database untuk menghemat ruang penyimpanan.</p>
                    <form action="dashboard.php" method="POST" onsubmit="return confirm('Sistem akan memindai folder uploads dan menghapus file yang tidak memiliki catatan di database. Lanjutkan?')">
                        <button type="submit" name="cleanup_files" class="btn btn-dark w-100 rounded-pill fw-bold small">
                            <i class="fas fa-broom me-2"></i> Jalankan Pembersihan Disk
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Dept Chart
        const deptCtx = document.getElementById('deptChart').getContext('2d');
        new Chart(deptCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($deptLabels); ?>,
                datasets: [{
                    label: 'Jumlah Dokumen',
                    data: <?php echo json_encode($deptData); ?>,
                    backgroundColor: '#1e3a8a',
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, grid: { display: false } } }
            }
        });

        // Type Chart
        const typeCtx = document.getElementById('typeChart').getContext('2d');
        new Chart(typeCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($typeLabels); ?>,
                datasets: [{
                    data: <?php echo json_encode($typeData); ?>,
                    backgroundColor: ['#1e3a8a', '#10b981', '#f59e0b'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } },
                cutout: '70%'
            }
        });
    </script>
</body>
</html>
