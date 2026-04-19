<?php
session_start();
include '../includes/db.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../user/login.php");
    exit();
}

$message = "";

// Handle Actions
if (isset($_POST['action'])) {
    $target_uid = (int)$_POST['user_id'];
    $action = $_POST['action'];

    // Prevent self-deletion or self-deactivation
    if ($target_uid == $_SESSION['user_id']) {
        $message = "<div class='alert alert-warning border-0 shadow-sm'><i class='fas fa-exclamation-circle me-2'></i>Anda tidak bisa mengubah status akun Anda sendiri.</div>";
    } else {
        if ($action == 'toggle_status') {
            $new_status = (int)$_POST['current_status'] == 1 ? 0 : 1;
            $sql = "UPDATE users SET is_verified = $new_status WHERE id = $target_uid";
            if ($conn->query($sql)) {
                $status_txt = $new_status == 1 ? "diaktifkan" : "dinonaktifkan";
                $message = "<div class='alert alert-success border-0 shadow-sm'><i class='fas fa-check-circle me-2'></i>User berhasil $status_txt.</div>";
            }
        } elseif ($action == 'delete') {
            // Hapus file skripsi fisik sebelum menghapus data dari database
            $theses_res = $conn->query("SELECT file_path FROM theses WHERE user_id = $target_uid");
            while ($t_file = $theses_res->fetch_assoc()) {
                if ($t_file['file_path'] && file_exists("../" . $t_file['file_path'])) {
                    @unlink("../" . $t_file['file_path']);
                }
            }
            
            // Hapus data skripsi di database (mencegah error FK jika tidak ada cascade)
            $conn->query("DELETE FROM theses WHERE user_id = $target_uid");

            $sql = "DELETE FROM users WHERE id = $target_uid";
            if ($conn->query($sql)) {
                $message = "<div class='alert alert-success border-0 shadow-sm'><i class='fas fa-trash-alt me-2'></i>User dan semua data terkait berhasil dihapus.</div>";
            }
        }
    }
}

// Handle Update User
if (isset($_POST['update_user'])) {
    $uid = (int)$_POST['user_id'];
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $nim = $conn->real_escape_string($_POST['nim']);
    $dept_id = (int)$_POST['department_id'];
    $role = $conn->real_escape_string($_POST['role']);
    
    $update_sql = "UPDATE users SET name='$name', email='$email', nim='$nim', department_id=$dept_id, role='$role' WHERE id=$uid";
    if ($conn->query($update_sql)) {
        $message = "<div class='alert alert-success border-0 shadow-sm'><i class='fas fa-check-circle me-2'></i>Profil user berhasil diperbarui.</div>";
    }
}

// Search Filter
$search_q = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$where_clause = " WHERE 1=1";
if ($search_q) {
    $where_clause .= " AND (u.name LIKE '%$search_q%' OR u.email LIKE '%$search_q%' OR u.nim LIKE '%$search_q%')";
}

// Pagination logic
$limit = 10;
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$total_res = $conn->query("SELECT COUNT(*) as total FROM users u $where_clause");
$total_filtered = $total_res->fetch_assoc()['total'];
$total_pages = ceil($total_filtered / $limit);

// Fetch all users with department info
$sql = "SELECT u.*, d.name as dept_name FROM users u 
        LEFT JOIN departments d ON u.department_id = d.id 
        $where_clause
        ORDER BY u.role ASC, u.name ASC
        LIMIT $offset, $limit";
$result = $conn->query($sql);

// Fetch grouped departments for edit modal
$faculties_list = [];
$fac_res = $conn->query("SELECT * FROM faculties ORDER BY name ASC");
while($f = $fac_res->fetch_assoc()) {
    $fid = $f['id'];
    $f['depts'] = [];
    $d_res = $conn->query("SELECT id, name, level FROM departments WHERE faculty_id = $fid ORDER BY name ASC");
    while($d = $d_res->fetch_assoc()) {
        $f['depts'][] = $d;
    }
    $faculties_list[] = $f;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User - Admin DigiRepo</title>
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
        .role-badge { font-size: 0.7rem; font-weight: 800; padding: 5px 12px; border-radius: 50px; }
        .btn-action { width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; transition: 0.2s; }
        .btn-action:hover { transform: scale(1.1); }
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
            <a href="users.php" class="nav-link active"><i class="fas fa-users"></i> Manajemen User</a>
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
            <h1 class="fw-bold mb-0">Manajemen User</h1>
            <div class="bg-white px-4 py-2 rounded-pill shadow-sm small fw-bold text-primary">
                Total <?php echo $total_filtered; ?> Pengguna
            </div>
        </div>

        <!-- Filters -->
        <div class="card border-0 rounded-4 shadow-sm p-4 mb-4">
            <form action="users.php" method="GET" class="row g-3">
                <div class="col-md-10">
                    <input type="text" name="search" class="form-control" style="border-radius: 12px; border: 1px solid #e2e8f0; padding: 10px 20px;" placeholder="Cari nama, email, atau NIM..." value="<?php echo htmlspecialchars($search_q); ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100 rounded-3 py-2 fw-bold">Cari</button>
                </div>
            </form>
        </div>

        <?php echo $message; ?>

        <div class="table-card mt-4">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">#</th>
                            <th>User</th>
                            <th>Role</th>
                            <th>Status Akun</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = $offset + 1; while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-4 text-muted small"><?php echo $no++; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3 small fw-bold" style="width: 35px; height: 35px;">
                                            <?php echo substr($row['name'], 0, 1); ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark mb-0 small"><?php echo $row['name']; ?></div>
                                            <div class="text-muted" style="font-size: 0.75rem;"><?php echo $row['email']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                        $badge_class = 'bg-info text-white';
                                        if($row['role'] == 'admin') $badge_class = 'bg-dark text-white';
                                        if($row['role'] == 'mahasiswa') $badge_class = 'bg-primary text-white';
                                    ?>
                                    <span class="badge role-badge <?php echo $badge_class; ?>">
                                        <?php echo strtoupper($row['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($row['is_verified']): ?>
                                        <span class="text-success small fw-bold"><i class="fas fa-check-circle me-1"></i> Aktif</span>
                                    <?php else: ?>
                                        <span class="text-warning small fw-bold"><i class="fas fa-clock me-1"></i> Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <!-- Edit Profile -->
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-action edit-user-btn" 
                                                data-bs-toggle="modal" data-bs-target="#editUserModal" 
                                                data-id="<?php echo $row['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($row['name']); ?>"
                                                data-email="<?php echo htmlspecialchars($row['email']); ?>"
                                                data-nim="<?php echo htmlspecialchars($row['nim'] ?? ''); ?>"
                                                data-role="<?php echo $row['role']; ?>"
                                                data-dept="<?php echo $row['department_id']; ?>"
                                                title="Edit Profil">
                                            <i class="fas fa-edit"></i>
                                        </button>

                                        <!-- Toggle Status -->
                                        <form action="users.php" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin mengubah status akun ini?')">
                                            <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                            <input type="hidden" name="current_status" value="<?php echo $row['is_verified']; ?>">
                                            <button type="submit" name="action" value="toggle_status" class="btn btn-sm <?php echo $row['is_verified'] ? 'btn-outline-warning' : 'btn-outline-success'; ?> btn-action" title="<?php echo $row['is_verified'] ? 'Nonaktifkan' : 'Aktifkan'; ?>">
                                                <i class="fas <?php echo $row['is_verified'] ? 'fa-user-slash' : 'fa-user-check'; ?>"></i>
                                            </button>
                                        </form>

                                        <!-- Delete -->
                                        <form action="users.php" method="POST" onsubmit="return confirm('PERINGATAN: Menghapus user akan menghapus semua skripsi yang pernah diunggah olehnya. Lanjutkan?')">
                                            <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" name="action" value="delete" class="btn btn-sm btn-outline-danger btn-action" title="Hapus Permanen">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <?php if($total_pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php 
                        $filt = $search_q ? "&search=".urlencode($search_q) : "";
                    ?>
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link rounded-pill px-3 me-2" href="?p=<?php echo $page - 1; ?><?php echo $filt; ?>">Prev</a>
                    </li>
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                            <a class="page-link rounded-circle mx-1 d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;" href="?p=<?php echo $i; ?><?php echo $filt; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link rounded-pill px-3 ms-2" href="?p=<?php echo $page + 1; ?><?php echo $filt; ?>">Next</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </main>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow">
                <form action="users.php" method="POST">
                    <div class="modal-header border-bottom-0 p-4">
                        <h5 class="modal-title fw-bold">Edit Profil User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 pt-0">
                        <input type="hidden" name="user_id" id="edit-user-id">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Nama Lengkap</label>
                            <input type="text" name="name" id="edit-name" class="form-control rounded-3" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Email</label>
                            <input type="email" name="email" id="edit-email" class="form-control rounded-3" required>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-secondary">NIM</label>
                                <input type="text" name="nim" id="edit-nim" class="form-control rounded-3">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-secondary">Role</label>
                                <select name="role" id="edit-role" class="form-select rounded-3">
                                    <option value="mahasiswa">Mahasiswa</option>
                                    <option value="admin">Admin</option>
                                    <option value="dosen">Dosen</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Program Studi (Grup per Fakultas)</label>
                            <select name="department_id" id="edit-dept" class="form-select rounded-3">
                                <option value="">--- Pilih Prodi ---</option>
                                <?php foreach($faculties_list as $f): ?>
                                    <optgroup label="<?php echo $f['name']; ?>">
                                        <?php foreach($f['depts'] as $d): ?>
                                            <option value="<?php echo $d['id']; ?>"><?php echo $d['name']; ?> (<?php echo $d['level']; ?>)</option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 p-4">
                        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="update_user" class="btn btn-primary rounded-pill px-4">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editBtn = document.querySelectorAll('.edit-user-btn');
            editBtn.forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');
                    const email = this.getAttribute('data-email');
                    const nim = this.getAttribute('data-nim');
                    const role = this.getAttribute('data-role');
                    const dept = this.getAttribute('data-dept');

                    document.getElementById('edit-user-id').value = id;
                    document.getElementById('edit-name').value = name;
                    document.getElementById('edit-email').value = email;
                    document.getElementById('edit-nim').value = nim;
                    document.getElementById('edit-role').value = role;
                    document.getElementById('edit-dept').value = dept;
                });
            });
        });
    </script>
</body>
</html>
