<?php
session_start();
include '../includes/db.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../user/login.php");
    exit();
}

$message = "";

// Handle Re-queue Action (Reset to Pending)
if (isset($_POST['requeue_id'])) {
    $req_id = $conn->real_escape_string($_POST['requeue_id']);
    
    // Reset status to pending and clear certificate data
    $sql = "UPDATE theses SET 
            status = 'pending', 
            certificate_number = NULL, 
            verification_hash = NULL, 
            certificate_content = NULL 
            WHERE id = '$req_id'";
            
    if ($conn->query($sql)) {
        $message = "<div class='alert alert-info border-0 shadow-sm'><i class='fas fa-undo me-1'></i> Skripsi telah dikembalikan ke antrean verifikasi.</div>";
    }
}

// Handle Delete Action
if (isset($_POST['delete_id'])) {
    $del_id = $conn->real_escape_string($_POST['delete_id']);
    
    // Get file path to delete physical file
    $file_info = $conn->query("SELECT file_path FROM theses WHERE id = '$del_id'")->fetch_assoc();
    
    $sql = "DELETE FROM theses WHERE id = '$del_id'";
    if ($conn->query($sql)) {
        if ($file_info && $file_info['file_path'] && file_exists("../" . $file_info['file_path'])) {
            @unlink("../" . $file_info['file_path']);
        }
        $message = "<div class='alert alert-success border-0 shadow-sm'><i class='fas fa-check-circle me-1'></i> Data skripsi berhasil dihapus secara permanen.</div>";
    }
}

// Filters
$status_filter = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';
$type_filter = isset($_GET['type']) ? $conn->real_escape_string($_GET['type']) : '';
$search_q = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Build Query
$limit = 10;
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$where_clause = " WHERE 1=1";
if ($status_filter) $where_clause .= " AND t.status = '$status_filter'";
if ($type_filter) $where_clause .= " AND t.type = '$type_filter'";
if ($search_q) $where_clause .= " AND (t.title LIKE '%$search_q%' OR u.name LIKE '%$search_q%')";

// Total count for pagination
$total_res = $conn->query("SELECT COUNT(*) as total FROM theses t JOIN users u ON t.user_id = u.id $where_clause");
$total_filtered = $total_res->fetch_assoc()['total'];
$total_pages = ceil($total_filtered / $limit);

$sql = "SELECT t.*, u.name as author_name, d.name as dept_name 
        FROM theses t 
        JOIN users u ON t.user_id = u.id 
        JOIN departments d ON u.department_id = d.id 
        $where_clause
        ORDER BY t.created_at DESC
        LIMIT $offset, $limit";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Skripsi - Admin DigiRepo</title>
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
        .table tbody td { padding: 18px 20px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
        .status-badge { font-size: 0.7rem; font-weight: 800; padding: 5px 12px; border-radius: 50px; }
        .search-input { border-radius: 12px; border: 1px solid #e2e8f0; padding: 10px 20px; font-size: 0.9rem; transition: 0.2s; }
        .search-input:focus { border-color: #2563eb; box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1); }
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
            <a href="theses.php" class="nav-link active"><i class="fas fa-book"></i> Manajemen Skripsi</a>
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
            <h1 class="fw-bold mb-0">Manajemen Skripsi</h1>
            <div class="bg-white px-4 py-2 rounded-pill shadow-sm small fw-bold text-primary">
                Total <?php echo $total_filtered; ?> Dokumen Sesuai Filter
            </div>
        </div>

        <?php echo $message; ?>

        <!-- Filters -->
        <div class="card border-0 rounded-4 shadow-sm p-4 mb-4">
            <form action="theses.php" method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control search-input" placeholder="Cari judul atau penulis..." value="<?php echo htmlspecialchars($search_q); ?>">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select search-input">
                        <option value="">Semua Status</option>
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="type" class="form-select search-input">
                        <option value="">Semua Tipe</option>
                        <option value="Skripsi" <?php echo $type_filter == 'Skripsi' ? 'selected' : ''; ?>>Skripsi (S1)</option>
                        <option value="Thesis" <?php echo $type_filter == 'Thesis' ? 'selected' : ''; ?>>Thesis (S2)</option>
                        <option value="Disertasi" <?php echo $type_filter == 'Disertasi' ? 'selected' : ''; ?>>Disertasi (S3)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100 rounded-3 py-2 fw-bold">Filter</button>
                </div>
            </form>
        </div>

        <div class="table-card mt-4">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">#</th>
                            <th>Judul & Penulis</th>
                            <th>Tipe & Prodi</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($result->num_rows > 0): ?>
                            <?php $no = $offset + 1; while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4 text-muted small"><?php echo $no++; ?></td>
                                    <td style="max-width: 300px;">
                                        <div class="fw-bold text-dark small mb-1"><?php echo $row['title']; ?></div>
                                        <div class="text-muted d-flex flex-column" style="font-size: 0.75rem;">
                                            <span><i class="fas fa-user-edit me-1"></i> Penulis: <?php echo $row['author_name']; ?></span>
                                            <span><i class="fas fa-user-tie me-1"></i> Pembimbing: <?php echo $row['supervisor_name'] ?? '-'; ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary text-white rounded-pill mb-1" style="font-size: 0.65rem;"><?php echo $row['type']; ?></span><br>
                                        <span class="text-muted small" style="font-size: 0.7rem;"><?php echo $row['dept_name']; ?></span>
                                    </td>
                                    <td>
                                        <?php 
                                            $s_class = 'bg-warning text-dark';
                                            if($row['status'] == 'approved') $s_class = 'bg-success text-white';
                                            if($row['status'] == 'rejected') $s_class = 'bg-danger text-white';
                                        ?>
                                        <span class="badge status-badge <?php echo $s_class; ?>">
                                            <?php echo strtoupper($row['status']); ?>
                                        </span>
                                    </td>
                                    <td class="text-muted small">
                                        <?php echo date('d/m/Y', strtotime($row['created_at'])); ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2">
                                            <a href="../skripsi/detail.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary" title="Lihat Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if($row['status'] != 'pending'): ?>
                                                <form action="theses.php" method="POST" onsubmit="return confirm('Kembalikan skripsi ini ke antrean verifikasi?')">
                                                    <input type="hidden" name="requeue_id" value="<?php echo $row['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-warning" title="Kembalikan ke Antrean">
                                                        <i class="fas fa-undo"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <form action="theses.php" method="POST" onsubmit="return confirm('Hapus skripsi ini secara permanen?')">
                                                <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">Data tidak ditemukan.</td>
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
                    <?php 
                        $filter_params = "";
                        if($status_filter) $filter_params .= "&status=$status_filter";
                        if($type_filter) $filter_params .= "&type=$type_filter";
                        if($search_q) $filter_params .= "&search=" . urlencode($search_q);
                    ?>
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link rounded-pill px-3 me-2" href="?p=<?php echo $page - 1; ?><?php echo $filter_params; ?>">Prev</a>
                    </li>
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                            <a class="page-link rounded-circle mx-1 d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;" href="?p=<?php echo $i; ?><?php echo $filter_params; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link rounded-pill px-3 ms-2" href="?p=<?php echo $page + 1; ?><?php echo $filter_params; ?>">Next</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
