<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Fetch All Settings
$settings = [];
$res_settings = $conn->query("SELECT * FROM settings");
if ($res_settings) {
    while($row_s = $res_settings->fetch_assoc()) {
        $settings[$row_s['setting_key']] = $row_s['setting_value'];
    }
}
$site_name = $settings['site_name'] ?? 'DigiRepo';
$site_tagline = $settings['site_tagline'] ?? 'Sistem Repositori Digital';
$site_logo = $settings['site_logo'] ?? '';
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top py-3">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary d-flex align-items-center" href="<?php echo htmlspecialchars($settings['home_url'] ?? 'index.php'); ?>">
            <?php if($site_logo): ?>
                <img src="<?php echo (strpos($_SERVER['PHP_SELF'], '/admin/') !== false || strpos($_SERVER['PHP_SELF'], '/skripsi/') !== false || strpos($_SERVER['PHP_SELF'], '/user/') !== false) ? '../' : ''; ?><?php echo $site_logo; ?>" alt="Logo" style="height: 45px; margin-right: 12px;">
            <?php endif; ?>
            <span class="fs-3"><?php echo $site_name; ?>.</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">
                <li class="nav-item">
                    <a class="nav-link fw-semibold" href="<?php echo (strpos($_SERVER['PHP_SELF'], '/admin/') !== false || strpos($_SERVER['PHP_SELF'], '/skripsi/') !== false || strpos($_SERVER['PHP_SELF'], '/user/') !== false) ? '../' : ''; ?>index.php">Beranda</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-semibold" href="<?php echo (strpos($_SERVER['PHP_SELF'], '/admin/') !== false || strpos($_SERVER['PHP_SELF'], '/skripsi/') !== false || strpos($_SERVER['PHP_SELF'], '/user/') !== false) ? '../' : ''; ?>latest_collection.php">Koleksi Terbaru</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-semibold" href="<?php echo (strpos($_SERVER['PHP_SELF'], '/admin/') !== false || strpos($_SERVER['PHP_SELF'], '/skripsi/') !== false || strpos($_SERVER['PHP_SELF'], '/user/') !== false) ? '../' : ''; ?>browse.php">Browse</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-semibold" href="<?php echo (strpos($_SERVER['PHP_SELF'], '/admin/') !== false || strpos($_SERVER['PHP_SELF'], '/skripsi/') !== false || strpos($_SERVER['PHP_SELF'], '/user/') !== false) ? '../' : ''; ?>faq.php">F.A.Q</a>
                </li>
            </ul>
            <div class="navbar-nav ms-auto align-items-center">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle fw-bold text-primary px-3" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i> Halo, <?php echo htmlspecialchars(explode(' ', $_SESSION['name'])[0]); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg rounded-4 mt-2 p-2" style="min-width: 220px;">
                            <?php if($_SESSION['role'] == 'admin'): ?>
                                <li><a class="dropdown-item fw-bold text-primary rounded-3 py-2" href="<?php echo (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? '' : 'admin/'; ?>dashboard.php"><i class="fas fa-shield-alt me-2"></i>Dashboard Admin</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item rounded-3 py-2" href="<?php echo (strpos($_SERVER['PHP_SELF'], '/user/') !== false) ? '' : 'user/'; ?>dashboard.php"><i class="fas fa-th-large me-2"></i>Dashboard Saya</a></li>
                            <?php endif; ?>
                            
                            <li><a class="dropdown-item rounded-3 py-2" href="<?php echo (strpos($_SERVER['PHP_SELF'], '/user/') !== false) ? '' : 'user/'; ?>profile.php"><i class="fas fa-user-cog me-2"></i>Pengaturan Profil</a></li>
                            <li><hr class="dropdown-divider opacity-50"></li>
                            <li><a class="dropdown-item text-danger rounded-3 py-2" href="<?php echo (strpos($_SERVER['PHP_SELF'], '/admin/') !== false || strpos($_SERVER['PHP_SELF'], '/skripsi/') !== false || strpos($_SERVER['PHP_SELF'], '/user/') !== false) ? '../' : ''; ?>logout.php"><i class="fas fa-sign-out-alt me-2"></i>Keluar</a></li>
                        </ul>
                    </div>
                    <?php if($_SESSION['is_verified'] == 1 || $_SESSION['role'] == 'admin'): ?>
                        <a class="nav-link btn btn-primary text-white px-4 rounded-pill fw-bold ms-lg-3" href="<?php echo (strpos($_SERVER['PHP_SELF'], '/skripsi/') !== false) ? '' : 'skripsi/'; ?>tambah.php">Unggah Skripsi</a>
                    <?php endif; ?>
                <?php else: ?>
                    <style>
                        .btn-masuk { border-radius: 30px; font-weight: 700; padding: 10px 30px; border: 2px solid #2563eb; color: #2563eb; transition: 0.3s; text-decoration: none; }
                        .btn-masuk:hover { background: #2563eb; color: white !important; }
                        .btn-daftar { border-radius: 30px; font-weight: 700; padding: 10px 30px; background: #2563eb; color: white !important; border: 2px solid #2563eb; transition: 0.3s; box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2); text-decoration: none; }
                        .btn-daftar:hover { background: #1d4ed8; border-color: #1d4ed8; color: white !important; transform: translateY(-1px); box-shadow: 0 6px 15px rgba(37, 99, 235, 0.3); }
                    </style>
                    <a class="btn-masuk me-2" href="<?php echo (strpos($_SERVER['PHP_SELF'], '/user/') !== false) ? '' : 'user/'; ?>login.php">Masuk</a>
                    <a class="btn-daftar" href="<?php echo (strpos($_SERVER['PHP_SELF'], '/user/') !== false) ? '' : 'user/'; ?>register.php">Daftar</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
