<?php
session_start();
include '../includes/db.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../user/login.php");
    exit();
}

// Summary Queries
$totalTheses = $conn->query("SELECT COUNT(*) as count FROM theses")->fetch_assoc()['count'];
$approvedCount = $conn->query("SELECT COUNT(*) as count FROM theses WHERE status='approved'")->fetch_assoc()['count'];
$pendingCount = $conn->query("SELECT COUNT(*) as count FROM theses WHERE status='pending'")->fetch_assoc()['count'];
$rejectedCount = $conn->query("SELECT COUNT(*) as count FROM theses WHERE status='rejected'")->fetch_assoc()['count'];

$deptStats = $conn->query("SELECT d.name as dept, COUNT(t.id) as count 
                           FROM departments d 
                           LEFT JOIN users u ON u.department_id = d.id 
                           LEFT JOIN theses t ON t.user_id = u.id 
                           GROUP BY d.id");

$typeStats = $conn->query("SELECT type, COUNT(*) as count FROM theses GROUP BY type");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Sistem - DigiRepo</title>
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
        .report-section { background: white; border-radius: 24px; padding: 40px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); margin-bottom: 30px; border: 1px solid #f1f5f9; }
        @media print {
            .sidebar, .btn-print, .no-print { display: none !important; }
            .main-content { margin-left: 0; padding: 0; }
            .report-section { box-shadow: none; border: 1px solid #eee; }
        }
    </style>
</head>
<body>
    <div class="sidebar no-print">
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
            <a href="site_settings.php" class="nav-link"><i class="fas fa-cogs"></i> Pengaturan Situs</a>
            <a href="reports.php" class="nav-link active"><i class="fas fa-file-alt"></i> Laporan</a>
            <div class="px-4 mt-5">
                <a href="../logout.php" class="btn btn-outline-danger w-100 rounded-pill fw-bold small py-2">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </a>
            </div>
        </div>
    </div>

    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-5 no-print">
            <h1 class="fw-bold mb-0">Laporan Statistik</h1>
            <button onclick="window.print()" class="btn btn-primary rounded-pill px-4 py-2 fw-bold btn-print">
                <i class="fas fa-print me-2"></i> Cetak Laporan
            </button>
        </div>

        <div class="report-section">
            <div class="text-center mb-5">
                <h3 class="fw-bold mb-0">DIGIREPO REPOSITORY REPORT</h3>
                <p class="text-muted">Periode s/d <?php echo date('d F Y'); ?></p>
            </div>

            <div class="row mb-5">
                <div class="col-md-3 text-center border-end">
                    <h1 class="fw-bold text-primary mb-0"><?php echo $totalTheses; ?></h1>
                    <small class="text-secondary fw-bold">TOTAL DOKUMEN</small>
                </div>
                <div class="col-md-3 text-center border-end">
                    <h1 class="fw-bold text-success mb-0"><?php echo $approvedCount; ?></h1>
                    <small class="text-secondary fw-bold">APPROVED</small>
                </div>
                <div class="col-md-3 text-center border-end">
                    <h1 class="fw-bold text-warning mb-0"><?php echo $pendingCount; ?></h1>
                    <small class="text-secondary fw-bold">PENDING</small>
                </div>
                <div class="col-md-3 text-center">
                    <h1 class="fw-bold text-danger mb-0"><?php echo $rejectedCount; ?></h1>
                    <small class="text-secondary fw-bold">REJECTED</small>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <h5 class="fw-bold mb-3">Distribusi Program Studi</h5>
                    <table class="table table-sm table-bordered">
                        <thead class="bg-light">
                            <tr>
                                <th>Nama Program Studi</th>
                                <th class="text-center">Jumlah Dokumen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $deptStats->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['dept']; ?></td>
                                    <td class="text-center"><?php echo $row['count']; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <div class="col-md-6">
                    <h5 class="fw-bold mb-3">Distribusi Jenjang / Tipe</h5>
                    <table class="table table-sm table-bordered">
                        <thead class="bg-light">
                            <tr>
                                <th>Tipe Dokumen</th>
                                <th class="text-center">Jumlah Dokumen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $typeStats->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['type']; ?></td>
                                    <td class="text-center"><?php echo $row['count']; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="mt-5 pt-5 text-end small text-muted">
                <p>Dicetak otomatis oleh Sistem DigiRepo pada: <?php echo date('d/m/Y H:i'); ?></p>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
