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
    <style>
        .navbar-nav { display: flex; align-items: center; }
        .navbar-nav.gap-2 { gap: 0.5rem; }
        .navbar-nav .nav-item.dropdown { align-self: center; }
        .navbar-nav .dropdown-toggle { display: flex; align-items: center; white-space: nowrap; padding-top: 0.5rem; padding-bottom: 0.5rem; transition: all 0.3s ease; }
        .navbar-nav .dropdown-toggle:hover { color: #1d4ed8 !important; transform: scale(1.05); }
        .navbar-nav .btn { display: inline-flex; align-items: center; white-space: nowrap; height: auto; }
        
        /* Dropdown Menu Styling */
        .dropdown-menu-custom { 
            border-radius: 16px !important; 
            border: none !important; 
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12) !important;
            padding: 8px !important;
            min-width: 260px;
            animation: slideDown 0.25s ease-out;
        }
        @keyframes slideDown { 
            from { opacity: 0; transform: translateY(-10px); } 
            to { opacity: 1; transform: translateY(0); } 
        }
        @keyframes fadeIn { 
            from { opacity: 0; transform: translateY(-5px); } 
            to { opacity: 1; transform: translateY(0); } 
        }
        
        /* Dropdown Items */
        .dropdown-menu-custom .dropdown-item {
            border-radius: 12px;
            padding: 10px 14px;
            margin-bottom: 4px;
            color: #475569;
            font-weight: 500;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
            display: flex;
            align-items: center;
        }
        .dropdown-menu-custom .dropdown-item:hover {
            background-color: #f1f5f9;
            color: #2563eb;
            border-left-color: #2563eb;
            transform: translateX(4px);
        }
        .dropdown-menu-custom .dropdown-item i {
            margin-right: 10px;
            width: 18px;
            text-align: center;
        }
        
        /* Special Item Styles */
        .dropdown-menu-custom .dropdown-item.admin-link {
            color: #2563eb;
            font-weight: 600;
            background-color: #eff6ff;
        }
        .dropdown-menu-custom .dropdown-item.admin-link:hover {
            background-color: #dbeafe;
        }
        .dropdown-menu-custom .dropdown-item.logout-link {
            color: #dc2626;
        }
        .dropdown-menu-custom .dropdown-item.logout-link:hover {
            background-color: #fee2e2;
            color: #991b1b;
        }
        
        /* Divider */
        .dropdown-menu-custom .dropdown-divider {
            margin: 6px 0 !important;
            border-top: 1px solid #e2e8f0 !important;
        }
        
        @media (max-width: 991px) {
            .navbar-nav.gap-2 { gap: 0.25rem; flex-direction: column; align-items: flex-start; }
            .navbar-nav .dropdown-menu { position: static; float: none; margin-left: 1rem; }
            .navbar-nav .btn { width: 100%; justify-content: flex-start; margin-top: 0.5rem; }
        }
    </style>
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
            <div class="navbar-nav ms-auto align-items-center gap-2">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle fw-bold text-primary px-3 d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" style="min-height: 45px;">
                            <i class="fas fa-user-circle me-2"></i> <span><?php echo htmlspecialchars(explode(' ', $_SESSION['name'])[0]); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-custom">
                            <?php 
                                $dashboard_prefix = '';
                                if (strpos($_SERVER['PHP_SELF'], '/user/') !== false) {
                                    $dashboard_prefix = '';
                                } elseif (strpos($_SERVER['PHP_SELF'], '/admin/') !== false || strpos($_SERVER['PHP_SELF'], '/skripsi/') !== false) {
                                    $dashboard_prefix = '../user/';
                                } else {
                                    $dashboard_prefix = 'user/';
                                }
                            ?>
                            <?php if($_SESSION['role'] == 'admin'): ?>
                                <li><a class="dropdown-item admin-link" href="<?php echo (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? '' : 'admin/'; ?>dashboard.php"><i class="fas fa-shield-alt"></i>Dashboard Admin</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item" href="<?php echo $dashboard_prefix; ?>dashboard.php"><i class="fas fa-th-large"></i>Dashboard Saya</a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="<?php echo $dashboard_prefix; ?>profile.php"><i class="fas fa-user-cog"></i>Pengaturan Profil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item logout-link" href="<?php echo (strpos($_SERVER['PHP_SELF'], '/admin/') !== false || strpos($_SERVER['PHP_SELF'], '/skripsi/') !== false || strpos($_SERVER['PHP_SELF'], '/user/') !== false) ? '../' : ''; ?>logout.php"><i class="fas fa-sign-out-alt"></i>Keluar</a></li>
                        </ul>
                    </div>
                    <?php if($_SESSION['is_verified'] == 1 || $_SESSION['role'] == 'admin'): ?>
                        <?php 
                        // Check if mahasiswa already has thesis
                        $show_upload_btn = false;
                        $btn_text = 'Unggah Skripsi';
                        $btn_icon = 'fa-cloud-upload-alt';
                        $btn_url = 'skripsi/tambah.php';
                        $btn_color = 'btn-primary';
                        $is_disabled = false;
                        
                        if ($_SESSION['role'] == 'mahasiswa') {
                            $existing = $conn->query("SELECT id, status FROM theses WHERE user_id = " . $_SESSION['user_id'] . " ORDER BY created_at DESC LIMIT 1");
                            if ($existing->num_rows > 0) {
                                $thesis = $existing->fetch_assoc();
                                if ($thesis['status'] == 'rejected') {
                                    $btn_text = 'Ajukan Revisi';
                                    $btn_icon = 'fa-edit';
                                    $btn_url = 'skripsi/edit.php?id=' . $thesis['id'];
                                    $show_upload_btn = true;
                                } elseif ($thesis['status'] == 'pending') {
                                    $btn_text = 'Edit Pengajuan';
                                    $btn_icon = 'fa-edit';
                                    $btn_url = 'skripsi/edit.php?id=' . $thesis['id'];
                                    $show_upload_btn = true;
                                } else {
                                    // approved or other status
                                    $btn_text = 'Sudah Diajukan';
                                    $btn_color = 'btn-secondary';
                                    $is_disabled = true;
                                    $show_upload_btn = true;
                                }
                            } else {
                                $show_upload_btn = true;
                            }
                        } else {
                            // For admin/dosen
                            $show_upload_btn = true;
                        }
                        
                        if ($show_upload_btn):
                        ?>
                            <?php
                            // Determine correct URL prefix based on current location
                            $url_prefix = '';
                            if (strpos($_SERVER['PHP_SELF'], '/skripsi/') !== false) {
                                $url_prefix = '';
                            } elseif (strpos($_SERVER['PHP_SELF'], '/user/') !== false) {
                                $url_prefix = '../skripsi/';
                            } else {
                                $url_prefix = 'skripsi/';
                            }
                            $full_url = $url_prefix . $btn_url;
                            ?>
                            <a class="btn <?php echo $btn_color; ?> text-white px-4 rounded-pill fw-bold <?php echo $is_disabled ? 'disabled' : ''; ?>" 
                               href="<?php echo $full_url; ?>" 
                               style="white-space: nowrap;" 
                               <?php echo $is_disabled ? 'role="button" aria-disabled="true"' : ''; ?>>
                                <i class="fas <?php echo $btn_icon; ?> me-2"></i><?php echo $btn_text; ?>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php else: ?>
                    <style>
                        .btn-masuk { border-radius: 30px; font-weight: 700; padding: 10px 30px; border: 2px solid #2563eb; color: #2563eb; transition: 0.3s; text-decoration: none; }
                        .btn-masuk:hover { background: #2563eb; color: white !important; }
                        .btn-daftar { border-radius: 30px; font-weight: 700; padding: 10px 30px; background: #2563eb; color: white !important; border: 2px solid #2563eb; transition: 0.3s; box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2); text-decoration: none; }
                        .btn-daftar:hover { background: #1d4ed8; border-color: #1d4ed8; color: white !important; transform: translateY(-1px); box-shadow: 0 6px 15px rgba(37, 99, 235, 0.3); }
                    </style>
                    <?php 
                        $auth_prefix = '';
                        if (strpos($_SERVER['PHP_SELF'], '/user/') !== false) {
                            $auth_prefix = '';
                        } elseif (strpos($_SERVER['PHP_SELF'], '/admin/') !== false || strpos($_SERVER['PHP_SELF'], '/skripsi/') !== false) {
                            $auth_prefix = '../user/';
                        } else {
                            $auth_prefix = 'user/';
                        }
                    ?>
                    <a class="btn-masuk me-2" href="<?php echo $auth_prefix; ?>login.php">Masuk</a>
                    <a class="btn-daftar" href="<?php echo $auth_prefix; ?>register.php">Daftar</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
