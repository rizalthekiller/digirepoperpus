<?php 
include 'includes/db.php'; 
session_start();

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
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $site_name; ?> - Perpustakaan Skripsi</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .navbar { box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .card { border: none; border-radius: 15px; transition: transform 0.2s; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .card:hover { transform: translateY(-5px); box-shadow: 0 10px 15px rgba(0,0,0,0.1); }
        .badge-keyword { background-color: #e0e7ff; color: #4338ca; font-weight: 600; }
        .btn-masuk { border-radius: 30px; font-weight: 700; padding: 10px 30px; border: 2px solid #2563eb; color: #2563eb; transition: 0.3s; }
        .btn-masuk:hover { background: #2563eb; color: white; }
        .btn-daftar { border-radius: 30px; font-weight: 700; padding: 10px 30px; background: #2563eb; color: white; border: 2px solid #2563eb; transition: 0.3s; box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2); }
        .btn-daftar:hover { background: #1d4ed8; border-color: #1d4ed8; color: white; transform: translateY(-1px); box-shadow: 0 6px 15px rgba(37, 99, 235, 0.3); }
        .navbar-brand img { height: 45px; margin-right: 12px; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <?php if(isset($_SESSION['user_id']) && $_SESSION['is_verified'] == 0 && $_SESSION['role'] != 'admin'): ?>
        <div class="bg-warning text-dark py-2 text-center small fw-bold shadow-sm">
            <i class="fas fa-clock me-2"></i> Akun Anda sedang menunggu verifikasi admin. Anda belum dapat mengunggah skripsi.
        </div>
    <?php endif; ?>

    <!-- Hero Section -->
    <section class="bg-primary text-white py-5 mb-5 shadow-sm">
        <div class="container py-4">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <h1 class="display-4 fw-800 mb-3">Selamat Datang di <?php echo $site_name; ?></h1>
                    <p class="lead mb-4 opacity-75"><?php echo $site_tagline; ?></p>
                    <div class="d-flex gap-3">
                        <a href="#prosedur" class="btn btn-light rounded-pill px-4 fw-bold">Prosedur Unggah</a>
                        <a href="#koleksi" class="btn btn-outline-light rounded-pill px-4 fw-bold">Jelajahi Koleksi</a>
                    </div>
                </div>
                <div class="col-lg-5 d-none d-lg-block text-end">
                    <i class="fas fa-book-reader fa-10x opacity-25"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Information Section -->
    <section class="container mb-5" id="prosedur">
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="card p-4 h-100 border-0 shadow-sm" style="background: #eff6ff;">
                    <div class="icon-box bg-primary text-white mb-3 d-inline-flex align-items-center justify-content-center rounded-3" style="width: 50px; height: 50px;">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h5 class="fw-bold">1. Pendaftaran Akun</h5>
                    <p class="text-secondary small">Daftarkan akun menggunakan email kampus Anda. Tunggu verifikasi dari administrator perpustakaan.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-4 h-100 border-0 shadow-sm" style="background: #f0fdf4;">
                    <div class="icon-box bg-success text-white mb-3 d-inline-flex align-items-center justify-content-center rounded-3" style="width: 50px; height: 50px;">
                        <i class="fas fa-file-upload"></i>
                    </div>
                    <h5 class="fw-bold">2. Unggah Dokumen</h5>
                    <p class="text-secondary small">Lengkapi data skripsi, thesis, atau disertasi Anda beserta file PDF utuh yang telah disidangkan.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-4 h-100 border-0 shadow-sm" style="background: #fefce8;">
                    <div class="icon-box bg-warning text-dark mb-3 d-inline-flex align-items-center justify-content-center rounded-3" style="width: 50px; height: 50px;">
                        <i class="fas fa-certificate"></i>
                    </div>
                    <h5 class="fw-bold">3. Ambil Sertifikat</h5>
                    <p class="text-secondary small">Setelah diverifikasi, Anda akan menerima email notifikasi dan dapat mengunduh Surat Keterangan Penyerahan Karya.</p>
                </div>
            </div>
        </div>

        <div class="row g-5 align-items-center mb-5">
            <div class="col-lg-6">
                <div class="p-4 bg-white rounded-4 shadow-sm">
                    <h3 class="fw-bold mb-4">Informasi Perpustakaan UINSI</h3>
                    <ul class="list-unstyled">
                        <li class="mb-3 d-flex align-items-start">
                            <i class="fas fa-map-marker-alt text-danger me-3 mt-1"></i>
                            <div>
                                <h6 class="fw-bold mb-0">Lokasi</h6>
                                <p class="small text-muted mb-0">Kampus II UINSI Samarinda, Jl. H.A.M. Rifaddin, Harapan Baru, Loa Janan Ilir, Samarinda.</p>
                            </div>
                        </li>
                        <li class="mb-3 d-flex align-items-start">
                            <i class="fas fa-clock text-primary me-3 mt-1"></i>
                            <div>
                                <h6 class="fw-bold mb-0">Jam Operasional</h6>
                                <p class="small text-muted mb-0">Senin - Kamis: 08:30 - 16:00 WITA<br>Jumat: 08:30 - 11:30 & 14:00 - 16:30 WITA</p>
                            </div>
                        </li>
                        <li class="d-flex align-items-start">
                            <i class="fas fa-phone-alt text-success me-3 mt-1"></i>
                            <div>
                                <h6 class="fw-bold mb-0">Kontak</h6>
                                <p class="small text-muted mb-0">Email: perpustakaan@uinsi.ac.id<br>Website: lib.uinsi.ac.id</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="p-4 bg-white rounded-4 shadow-sm border-start border-primary border-5">
                    <h4 class="fw-bold mb-3">Tentang DigiRepo</h4>
                    <p class="text-secondary small">DigiRepo (Digital Repository) adalah platform resmi Perpustakaan UIN Sultan Aji Muhammad Idris Samarinda untuk menyimpan dan mempublikasikan karya akhir mahasiswa secara terpusat. Sistem ini bertujuan untuk memudahkan akses referensi akademik dan memastikan keabsahan dokumen melalui verifikasi digital berkelanjutan.</p>
                </div>
            </div>
        </div>
    </section>

    <main class="container py-5" id="koleksi">
        <h2 class="fw-bold mb-4"><i class="fas fa-search me-2 text-primary"></i>Jelajahi Koleksi</h2>
        <div class="row">
            <!-- Sidebar Filters -->
            <div class="col-lg-3 mb-4">
                <div class="card p-4 shadow-sm border-0 rounded-4 sticky-top" style="top: 100px;">
                    <h5 class="fw-bold mb-4">Filter Pencarian</h5>
                    <form action="index.php" method="GET">
                        <input type="hidden" name="q" value="<?php echo isset($_GET['q']) ? $_GET['q'] : ''; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Tahun</label>
                            <select name="year" class="form-select form-select-sm rounded-3">
                                <option value="">Semua Tahun</option>
                                <?php
                                $years = $conn->query("SELECT DISTINCT year FROM theses WHERE status='approved' ORDER BY year DESC");
                                while($y = $years->fetch_assoc()) {
                                    $selected = (isset($_GET['year']) && $_GET['year'] == $y['year']) ? 'selected' : '';
                                    echo "<option value='".$y['year']."' $selected>".$y['year']."</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Fakultas</label>
                            <select name="faculty" class="form-select form-select-sm rounded-3">
                                <option value="">Semua Fakultas</option>
                                <?php
                                $faculties = $conn->query("SELECT * FROM faculties");
                                while($f = $faculties->fetch_assoc()) {
                                    $selected = (isset($_GET['faculty']) && $_GET['faculty'] == $f['id']) ? 'selected' : '';
                                    echo "<option value='".$f['id']."' $selected>".$f['name']."</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-secondary">Program Studi</label>
                            <select name="dept" class="form-select form-select-sm rounded-3">
                                <option value="">Semua Prodi</option>
                                <?php
                                $faculties_query = $conn->query("SELECT * FROM faculties ORDER BY name ASC");
                                while($f = $faculties_query->fetch_assoc()):
                                ?>
                                    <optgroup label="<?php echo $f['name']; ?>">
                                        <?php 
                                        $f_id = $f['id'];
                                        $depts = $conn->query("SELECT * FROM departments WHERE faculty_id = $f_id ORDER BY name ASC");
                                        while($d = $depts->fetch_assoc()):
                                            $selected = (isset($_GET['dept']) && $_GET['dept'] == $d['id']) ? 'selected' : '';
                                        ?>
                                            <option value="<?php echo $d['id']; ?>" <?php echo $selected; ?>><?php echo $d['name']; ?></option>
                                        <?php endwhile; ?>
                                    </optgroup>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-secondary">Tipe Dokumen</label>
                            <select name="type" class="form-select form-select-sm rounded-3">
                                <option value="">Semua Tipe</option>
                                <option value="Skripsi" <?php echo (isset($_GET['type']) && $_GET['type'] == 'Skripsi') ? 'selected' : ''; ?>>Skripsi (S1)</option>
                                <option value="Thesis" <?php echo (isset($_GET['type']) && $_GET['type'] == 'Thesis') ? 'selected' : ''; ?>>Thesis (S2)</option>
                                <option value="Disertasi" <?php echo (isset($_GET['type']) && $_GET['type'] == 'Disertasi') ? 'selected' : ''; ?>>Disertasi (S3)</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm w-100 rounded-3 fw-bold">Terapkan Filter</button>
                        <a href="index.php" class="btn btn-link btn-sm w-100 mt-2 text-decoration-none text-muted">Reset</a>
                    </form>
                </div>
            </div>

            <!-- Main Content (Results) -->
            <div class="col-lg-9">
                <div class="row g-4">
                    <?php
                    $q = isset($_GET['q']) ? $conn->real_escape_string($_GET['q']) : '';
                    $year = isset($_GET['year']) ? $conn->real_escape_string($_GET['year']) : '';
                    $faculty = isset($_GET['faculty']) ? $conn->real_escape_string($_GET['faculty']) : '';
                    $dept = isset($_GET['dept']) ? $conn->real_escape_string($_GET['dept']) : '';
                    $type = isset($_GET['type']) ? $conn->real_escape_string($_GET['type']) : '';

                    $sql = "SELECT t.*, u.name as author, d.name as dept_name";
                    
                    if ($q) {
                        $sql .= ", MATCH(t.title, t.abstract, t.keywords) AGAINST('$q') as relevance";
                    }

                    $sql .= " FROM theses t 
                             JOIN users u ON t.user_id = u.id 
                             JOIN departments d ON u.department_id = d.id 
                             WHERE t.status='approved'";

                    if ($q) $sql .= " AND MATCH(t.title, t.abstract, t.keywords) AGAINST('$q' IN NATURAL LANGUAGE MODE)";
                    if ($year) $sql .= " AND t.year = '$year'";
                    if ($dept) $sql .= " AND u.department_id = '$dept'";
                    if ($faculty) $sql .= " AND d.faculty_id = '$faculty'";
                    if ($type) $sql .= " AND t.type = '$type'";

                    if ($q) {
                        $sql .= " ORDER BY relevance DESC, t.created_at DESC";
                    } else {
                        $sql .= " ORDER BY t.created_at DESC";
                    }

                    $result = $conn->query($sql);

                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                    ?>
                        <div class="col-md-6">
                            <div class="card h-100 p-3">
                                <div class="card-body d-flex flex-column">
                                    <div class="mb-2 d-flex gap-2">
                                        <span class="badge bg-light text-primary border rounded-pill small"><?php echo $row['dept_name']; ?></span>
                                        <span class="badge bg-primary text-white border rounded-pill small"><?php echo $row['type']; ?></span>
                                    </div>
                                    <h5 class="card-title fw-bold mb-2">
                                        <a href="skripsi/detail.php?id=<?php echo $row['id']; ?>" class="text-decoration-none text-dark hover-primary"><?php echo $row['title']; ?></a>
                                    </h5>
                                    <p class="text-muted small mb-3">Oleh: <span class="fw-bold"><?php echo $row['author']; ?></span> • <?php echo $row['year']; ?></p>
                                    <p class="card-text text-secondary small mb-4"><?php echo substr($row['abstract'], 0, 150); ?>...</p>
                                    
                                    <div class="mb-4">
                                        <?php if($row['file_path']): ?>
                                            <?php if(isset($_SESSION['user_id'])): ?>
                                                <a href="<?php echo $row['file_path']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold" download>
                                                    <i class="fas fa-download me-1"></i> Unduh PDF
                                                </a>
                                            <?php else: ?>
                                                <a href="user/login.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-bold">
                                                    <i class="fas fa-lock me-1"></i> Login to Download
                                                </a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <a href="skripsi/detail.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-link text-decoration-none small text-muted">Lihat Detail</a>
                                    </div>

                                    <div class="mt-auto">
                                        <?php 
                                        $keywords = explode(',', $row['keywords']);
                                        foreach($keywords as $kw) {
                                            if(trim($kw)) echo '<span class="badge badge-keyword me-1 mb-1">#' . trim($kw) . '</span>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php
                        }
                    } else {
                        echo '<div class="col-12 text-center py-5">
                                <h4 class="text-muted">Skripsi tidak ditemukan</h4>
                                <p class="text-secondary">Coba sesuaikan filter atau kata kunci Anda.</p>
                              </div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </main>

    <footer class="bg-white border-top py-5 mt-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <h5 class="fw-bold text-primary mb-3"><?php echo $site_name; ?>.</h5>
                    <p class="text-secondary small"><?php echo $settings['footer_about'] ?? ''; ?></p>
                    <div class="d-flex gap-3 mt-4">
                        <a href="#" class="text-primary fs-5"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-primary fs-5"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-primary fs-5"><i class="fab fa-youtube"></i></a>
                        <a href="#" class="text-primary fs-5"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 ms-auto">
                    <h6 class="fw-bold mb-3">Tautan Cepat</h6>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="index.php" class="text-decoration-none text-secondary">Beranda</a></li>
                        <li class="mb-2"><a href="latest_collection.php" class="text-decoration-none text-secondary">Koleksi Terbaru</a></li>
                        <li class="mb-2"><a href="browse.php" class="text-decoration-none text-secondary">Browse</a></li>
                        <li class="mb-2"><a href="faq.php" class="text-decoration-none text-secondary">F.A.Q</a></li>
                    </ul>
                </div>
                <div class="col-lg-2">
                    <h6 class="fw-bold mb-3">Layanan</h6>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="user/login.php" class="text-decoration-none text-secondary">Login Mahasiswa</a></li>
                        <li class="mb-2"><a href="user/register.php" class="text-decoration-none text-secondary">Pendaftaran</a></li>
                        <li class="mb-2"><a href="skripsi/tambah.php" class="text-decoration-none text-secondary">Unggah Mandiri</a></li>
                    </ul>
                </div>
                <div class="col-lg-3">
                    <h6 class="fw-bold mb-3">Kontak Kami</h6>
                    <ul class="list-unstyled small">
                        <li class="mb-2 text-secondary"><i class="fas fa-envelope me-2"></i> perpustakaan@uinsi.ac.id</li>
                        <li class="mb-2 text-secondary"><i class="fas fa-globe me-2"></i> lib.uinsi.ac.id</li>
                        <li class="mb-2 text-secondary"><i class="fas fa-map-marker-alt me-2"></i> Kampus II UINSI Samarinda</li>
                    </ul>
                </div>
            </div>
            <hr class="my-4 opacity-10">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <p class="text-muted small mb-0">&copy; 2026 DigiRepo UINSI Samarinda. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <p class="text-muted small mb-0">Developed with <i class="fas fa-heart text-danger"></i> for Academic Excellence</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://kit.fontawesome.com/your-code.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
