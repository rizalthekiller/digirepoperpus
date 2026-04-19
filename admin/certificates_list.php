<?php
session_start();
include '../includes/db.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../user/login.php");
    exit();
}

// Pagination logic
$limit = 20;
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Fetch Approved Theses with Certificates
$total_res = $conn->query("SELECT COUNT(*) as total FROM theses WHERE certificate_number IS NOT NULL AND certificate_number != ''");
$total_rows = $total_res->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

$sql = "SELECT t.*, u.name as author_name, u.nim, d.name as dept_name 
        FROM theses t 
        JOIN users u ON t.user_id = u.id 
        JOIN departments d ON u.department_id = d.id
        WHERE t.certificate_number IS NOT NULL AND t.certificate_number != ''
        ORDER BY t.created_at DESC 
        LIMIT $offset, $limit";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Surat Keterangan - Admin DigiRepo</title>
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
            <a href="certificates_list.php" class="nav-link active"><i class="fas fa-file-invoice"></i> Data Surat</a>
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
            <h1 class="fw-bold mb-0">Riwayat Surat Keterangan</h1>
            <div class="bg-white px-4 py-2 rounded-pill shadow-sm small fw-bold">
                <i class="fas fa-file-pdf text-danger me-2"></i> <?php echo $total_rows; ?> Surat Diterbitkan
            </div>
        </div>

        <?php 
        if (isset($_SESSION['queue_msg'])) {
            echo $_SESSION['queue_msg'];
            unset($_SESSION['queue_msg']);
        }
        ?>

        <div class="table-card mt-4">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">#</th>
                            <th>Nomor Surat</th>
                            <th>Mahasiswa</th>
                            <th>Judul Skripsi</th>
                            <th>Pengiriman</th>
                            <th>Tanggal Terbit</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($result->num_rows > 0): ?>
                            <?php $no = $offset + 1; while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4 text-muted small"><?php echo $no++; ?></td>
                                    <td>
                                        <div class="fw-bold text-primary"><?php echo $row['certificate_number']; ?></div>
                                        <div class="text-muted" style="font-size: 0.7rem;">HASH: <?php echo substr($row['verification_hash'], 0, 12); ?>...</div>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?php echo $row['author_name']; ?></div>
                                        <div class="text-muted small"><?php echo $row['nim']; ?></div>
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 250px;"><?php echo $row['title']; ?></div>
                                        <div class="text-secondary small"><?php echo $row['dept_name']; ?></div>
                                    </td>
                                    <td>
                                        <?php if($row['delivery_status'] == 'sent'): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill fw-bold" style="font-size: 0.65rem;">
                                                <i class="fas fa-check me-1"></i> Terkirim
                                            </span>
                                        <?php elseif($row['delivery_status'] == 'failed'): ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill fw-bold" style="font-size: 0.65rem;">
                                                <i class="fas fa-exclamation-circle me-1"></i> Gagal
                                            </span>
                                            <a href="resend_email.php?id=<?php echo $row['id']; ?>" class="d-block mt-1 small text-decoration-none text-danger font-bold" style="font-size: 0.6rem;">
                                                <i class="fas fa-sync-alt me-1"></i> Kirim Ulang
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill fw-bold" style="font-size: 0.65rem;">
                                                <i class="fas fa-clock me-1"></i> Menunggu
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted small">
                                        <?php echo date('d M Y, H:i', strtotime($row['created_at'])); ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="../verify.php?h=<?php echo $row['verification_hash']; ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                            <i class="fas fa-external-link-alt me-1"></i> Lihat Surat
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">Belum ada surat yang diterbitkan.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <?php if($total_pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link rounded-pill px-3 me-2" href="?p=<?php echo $page - 1; ?>">Prev</a>
                    </li>
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                            <a class="page-link rounded-circle mx-1 d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;" href="?p=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link rounded-pill px-3 ms-2" href="?p=<?php echo $page + 1; ?>">Next</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </main>
</body>
</html>
