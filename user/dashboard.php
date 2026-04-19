<?php
session_start();
include '../includes/db.php';

// Check login and role
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'mahasiswa' && $_SESSION['role'] != 'dosen')) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$is_verified = $_SESSION['is_verified'];

// Fetch Stats
$total_papers = $conn->query("SELECT COUNT(*) as count FROM theses WHERE user_id = $user_id")->fetch_assoc()['count'];
$approved_papers = $conn->query("SELECT COUNT(*) as count FROM theses WHERE user_id = $user_id AND status = 'approved'")->fetch_assoc()['count'];
$pending_papers = $conn->query("SELECT COUNT(*) as count FROM theses WHERE user_id = $user_id AND status = 'pending'")->fetch_assoc()['count'];

// Fetch User's Papers
$sql = "SELECT * FROM theses WHERE user_id = $user_id ORDER BY created_at DESC";
$papers = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Saya - DigiRepo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .sidebar { width: 280px; height: 100vh; position: fixed; background: white; border-right: 1px solid #e2e8f0; padding: 30px 0; }
        .sidebar-brand { padding: 0 30px 40px 30px; }
        .sidebar-nav .nav-link { color: #64748b; padding: 12px 30px; font-weight: 600; transition: 0.3s; text-decoration: none; display: block; border-left: 4px solid transparent; }
        .sidebar-nav .nav-link:hover, .sidebar-nav .nav-link.active { color: #1e3a8a; background: #f1f5f9; border-left-color: #1e3a8a; }
        .main-content { margin-left: 280px; padding: 40px; }
        .stat-card { border: none; border-radius: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); transition: 0.3s; }
        .stat-card:hover { transform: translateY(-3px); }
        .table-card { border: none; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.03); background: white; overflow: hidden; }
        .status-badge { font-size: 0.75rem; font-weight: 700; padding: 6px 12px; border-radius: 50px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-brand">
            <h2 class="fw-bold text-primary mb-0">DigiRepo.</h2>
            <small class="text-muted">Portal Mahasiswa</small>
        </div>
        <div class="sidebar-nav mt-4">
            <a href="dashboard.php" class="nav-link active"><i class="fas fa-th-large me-2"></i> Dashboard</a>
            <a href="../index.php" class="nav-link"><i class="fas fa-search me-2"></i> Cari Skripsi</a>
            <a href="profile.php" class="nav-link"><i class="fas fa-user-circle me-2"></i> Profil Ku</a>
            <a href="../logout.php" class="nav-link mt-5 text-danger"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
        </div>
    </div>

    <main class="main-content">
        <?php if($is_verified == 0): ?>
            <div class="alert alert-warning border-0 shadow-sm rounded-4 p-4 mb-5 d-flex align-items-center">
                <i class="fas fa-info-circle fa-2x me-3"></i>
                <div>
                    <h6 class="fw-bold mb-1">Akun Menunggu Verifikasi</h6>
                    <p class="mb-0 small">Akun Anda sedang ditinjau oleh tim perpustakaan. Anda belum dapat mengunggah karya ilmiah baru sampai akun diverifikasi.</p>
                </div>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h1 class="fw-extrabold mb-1">Halo, <?php echo explode(' ', $_SESSION['name'])[0]; ?>! 👋</h1>
                <p class="text-muted mb-0">Kelola dan pantau status publikasi karya ilmiah Anda.</p>
            </div>
            <?php 
            if($is_verified == 1): 
                // Check if user already has a thesis (any status except approved)
                $existing_thesis = $conn->query("SELECT id, status FROM theses WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 1");
                
                if ($existing_thesis->num_rows > 0) {
                    $thesis = $existing_thesis->fetch_assoc();
                    if ($thesis['status'] == 'rejected') {
                        // Show revise button for rejected thesis
                        echo '<a href="../skripsi/edit.php?id=' . $thesis['id'] . '" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">';
                        echo '<i class="fas fa-edit me-2"></i> Ajukan Revisi';
                        echo '</a>';
                    } elseif ($thesis['status'] == 'pending') {
                        // Show edit button for pending thesis
                        echo '<a href="../skripsi/edit.php?id=' . $thesis['id'] . '" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">';
                        echo '<i class="fas fa-edit me-2"></i> Edit Pengajuan';
                        echo '</a>';
                    } else {
                        // Disabled for other statuses
                        echo '<button class="btn btn-secondary rounded-pill px-4 py-2 fw-bold shadow-sm" disabled title="Pengajuan Anda sudah disetujui">';
                        echo '<i class="fas fa-lock me-2"></i> Terbatas';
                        echo '</button>';
                    }
                } else {
                    // No existing thesis, show upload button
                    echo '<a href="../skripsi/tambah.php" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">';
                    echo '<i class="fas fa-plus me-2"></i> Unggah Karya Baru';
                    echo '</a>';
                }
            ?>
            <?php endif; ?>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="card stat-card p-4 border-0">
                    <h6 class="text-secondary small fw-bold text-uppercase mb-3">Total Karya</h6>
                    <h2 class="fw-extrabold mb-0"><?php echo $total_papers; ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card p-4 border-0">
                    <h6 class="text-secondary small fw-bold text-uppercase mb-3">Disetujui</h6>
                    <h2 class="fw-extrabold mb-0 text-success"><?php echo $approved_papers; ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card p-4 border-0">
                    <h6 class="text-secondary small fw-bold text-uppercase mb-3">Menunggu</h6>
                    <h2 class="fw-extrabold mb-0 text-warning"><?php echo $pending_papers; ?></h2>
                </div>
            </div>
        </div>

        <h4 class="fw-bold mb-4">Riwayat Pengajuan</h4>
        <div class="table-card">
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4 py-3 text-secondary small fw-bold text-uppercase">Judul Karya</th>
                            <th class="py-3 text-secondary small fw-bold text-uppercase">Tipe</th>
                            <th class="py-3 text-secondary small fw-bold text-uppercase">Status</th>
                            <th class="py-3 text-secondary small fw-bold text-uppercase">Tanggal Submit</th>
                            <th class="pe-4 py-3 text-end text-secondary small fw-bold text-uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($papers->num_rows > 0): ?>
                            <?php while($row = $papers->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4 py-3">
                                        <div class="fw-bold text-dark"><?php echo $row['title']; ?></div>
                                        <div class="text-muted small"><?php echo $row['year']; ?></div>
                                        <?php if($row['status'] == 'rejected' && !empty($row['rejection_reason'])): ?>
                                            <div class="alert alert-danger border-0 py-1 px-2 mt-2 mb-0 small rounded-3">
                                                <i class="fas fa-exclamation-circle me-1"></i> 
                                                <strong>Alasan Tolak:</strong> <?php echo htmlspecialchars($row['rejection_reason']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-primary border rounded-pill small"><?php echo $row['type']; ?></span>
                                    </td>
                                    <td>
                                        <?php 
                                            $status_class = 'bg-warning text-dark';
                                            $status_text = 'Pending';
                                            if($row['status'] == 'approved') { $status_class = 'bg-success text-white'; $status_text = 'Approved'; }
                                            if($row['status'] == 'rejected') { $status_class = 'bg-danger text-white'; $status_text = 'Rejected'; }
                                        ?>
                                        <span class="badge status-badge <?php echo $status_class; ?>">
                                            <?php echo $status_text; ?>
                                        </span>
                                    </td>
                                    <td class="text-muted small"><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                                    <td class="pe-4 text-end">
                                        <div class="btn-group">
                                            <a href="../skripsi/detail.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3 me-2">Detail</a>
                                            <?php if($row['status'] == 'pending'): ?>
                                                <a href="../skripsi/edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">Edit</a>
                                            <?php elseif($row['status'] == 'rejected'): ?>
                                                <a href="../skripsi/edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary rounded-pill px-3">Revisi</a>
                                            <?php elseif($row['status'] == 'approved' && !empty($row['certificate_number'])): ?>
                                                <a href="../skripsi/certificate.php?id=<?php echo $row['id']; ?>" target="_blank" class="btn btn-sm btn-success rounded-pill px-3">
                                                    <i class="fas fa-certificate me-1"></i> Sertifikat
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <img src="https://picsum.photos/seed/empty/100/100" class="opacity-25 mb-3" style="filter: grayscale(1);">
                                    <p class="text-muted">Belum ada karya ilmiah yang diunggah.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
