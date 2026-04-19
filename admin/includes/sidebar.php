<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar">
    <div class="sidebar-brand text-start">
        <h2 class="fw-bold mb-0 text-primary">DigiRepo.</h2>
        <small class="text-muted fw-semibold">ADMINISTRATOR</small>
    </div>
    <div class="sidebar-nav mt-3">
        <a href="dashboard.php" class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>"><i class="fas fa-th-large"></i> Overview</a>
        <a href="verification_queue.php" class="nav-link <?php echo $current_page == 'verification_queue.php' ? 'active' : ''; ?>"><i class="fas fa-clock"></i> Antrean Verifikasi</a>
        <a href="certificates_list.php" class="nav-link <?php echo $current_page == 'certificates_list.php' ? 'active' : ''; ?>"><i class="fas fa-file-invoice"></i> Data Surat</a>
        <a href="theses.php" class="nav-link <?php echo $current_page == 'theses.php' ? 'active' : ''; ?>"><i class="fas fa-book"></i> Manajemen Skripsi</a>
        <a href="users.php" class="nav-link <?php echo $current_page == 'users.php' ? 'active' : ''; ?>"><i class="fas fa-users"></i> Manajemen User</a>
        <a href="faculties.php" class="nav-link <?php echo $current_page == 'faculties.php' ? 'active' : ''; ?>"><i class="fas fa-university"></i> Fakultas</a>
        <a href="departments.php" class="nav-link <?php echo $current_page == 'departments.php' ? 'active' : ''; ?>"><i class="fas fa-graduation-cap"></i> Program Studi</a>
        <a href="certificate_settings.php" class="nav-link <?php echo $current_page == 'certificate_settings.php' ? 'active' : ''; ?>"><i class="fas fa-certificate"></i> Pengaturan Surat</a>
        <a href="site_settings.php" class="nav-link <?php echo $current_page == 'site_settings.php' ? 'active' : ''; ?>"><i class="fas fa-cogs"></i> Pengaturan Situs</a>
        <a href="reports.php" class="nav-link <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>"><i class="fas fa-file-alt"></i> Laporan</a>
        
        <div class="px-4 mt-5 pb-5">
            <a href="../logout.php" class="btn btn-outline-danger w-100 rounded-pill fw-bold small py-2">
                <i class="fas fa-sign-out-alt me-2"></i> Logout
            </a>
        </div>
    </div>
</div>

<style>
    .sidebar { 
        width: 280px; 
        height: 100vh; 
        position: fixed; 
        background: white; 
        border-right: 1px solid #e2e8f0; 
        padding: 30px 0; 
        z-index: 1000; 
        overflow-y: auto;
    }
    /* Custom Scrollbar for Sidebar */
    .sidebar::-webkit-scrollbar { width: 5px; }
    .sidebar::-webkit-scrollbar-track { background: #f1f5f9; }
    .sidebar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    .sidebar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

    @media print {
        .sidebar, .no-print { display: none !important; }
        .main-content { margin-left: 0 !important; padding: 0 !important; }
    }
</style>
