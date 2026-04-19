<?php
session_start();
include '../includes/db.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../user/login.php");
    exit();
}

$message = "";

// Add Department
if (isset($_POST['add_dept'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $fac_id = (int)$_POST['faculty_id'];
    $level = $conn->real_escape_string($_POST['level']);
    $code = $conn->real_escape_string($_POST['code']);
    if ($conn->query("INSERT INTO departments (name, faculty_id, level, code) VALUES ('$name', $fac_id, '$level', '$code')")) {
        $message = "<div class='alert alert-success border-0 shadow-sm'>Program Studi berhasil ditambahkan!</div>";
    }
}

// Delete Department
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($conn->query("DELETE FROM departments WHERE id = $id")) {
        $message = "<div class='alert alert-success border-0 shadow-sm'>Program Studi berhasil dihapus!</div>";
    }
}

// Pagination logic
$limit = 20;
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$total_res = $conn->query("SELECT COUNT(*) as total FROM departments");
$total_rows = $total_res->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

$faculties = $conn->query("SELECT * FROM faculties ORDER BY name ASC");
$depts = $conn->query("SELECT d.id, d.name as dept_name, d.level as dept_level, d.code as dept_code, f.name as faculty_name 
                      FROM departments d 
                      JOIN faculties f ON d.faculty_id = f.id 
                      ORDER BY f.name ASC, d.name ASC
                      LIMIT $offset, $limit");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Prodi - DigiRepo</title>
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
        .table thead th { background-color: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 15px 20px; font-size: 0.75rem; text-transform: uppercase; color: #64748b; letter-spacing: 0.025em; }
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
            <a href="departments.php" class="nav-link active"><i class="fas fa-graduation-cap"></i> Program Studi</a>
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
        <h1 class="fw-bold mb-5">Manajemen Program Studi (Prodi)</h1>
        
        <?php echo $message; ?>

        <div class="row g-4">
            <div class="col-md-5">
                <div class="card p-4">
                    <h5 class="fw-bold mb-4">Tambah Prodi Baru</h5>
                    <form action="departments.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Fakultas</label>
                            <select name="faculty_id" class="form-select rounded-3 py-2" required>
                                <option value="">--- Pilih Fakultas ---</option>
                                <?php while($f = $faculties->fetch_assoc()): ?>
                                    <option value="<?php echo $f['id']; ?>"><?php echo $f['name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Nama Program Studi</label>
                            <input type="text" name="name" class="form-control rounded-3 py-2" placeholder="Contoh: Informatika" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Jenjang</label>
                            <select name="level" class="form-select rounded-3 py-2" required>
                                <option value="S1">S1 (Sarjana)</option>
                                <option value="S2">S2 (Magister)</option>
                                <option value="S3">S3 (Doktor)</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-secondary">Kode Prodi</label>
                            <input type="text" name="code" class="form-control rounded-3 py-2" placeholder="Contoh: IF-S1" required>
                        </div>
                        <button type="submit" name="add_dept" class="btn btn-primary w-100 rounded-3 fw-bold py-3">Simpan Prodi</button>
                    </form>
                </div>
            </div>
            <div class="col-md-7">
                <div class="card p-0 overflow-hidden">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4 py-3">#</th>
                                <th class="py-3">Kode</th>
                                <th class="py-3">Program Studi</th>
                                <th class="py-3">Fakultas & Jenjang</th>
                                <th class="text-end pe-4 py-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($depts->num_rows > 0): $no = $offset + 1; while($row = $depts->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4 text-muted small"><?php echo $no++; ?></td>
                                    <td class="fw-bold text-primary small"><?php echo $row['dept_code'] ?? '-'; ?></td>
                                    <td class="fw-bold"><?php echo $row['dept_name']; ?></td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="badge bg-light text-primary border rounded-pill px-3 py-1 text-start" style="width: fit-content; font-size: 0.7rem;"><?php echo $row['faculty_name']; ?></span>
                                            <span class="badge bg-dark text-white rounded-pill px-3 py-1 text-start" style="width: fit-content; font-size: 0.65rem;"><?php echo $row['dept_level']; ?></span>
                                        </div>
                                    </td>
                                    <td class="text-end pe-4">
                                        <a href="departments.php?delete=<?php echo $row['id']; ?>" class="text-danger" onclick="return confirm('Hapus prodi ini?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr>
                                    <td colspan="3" class="text-center py-5 text-muted">Belum ada data prodi.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
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
