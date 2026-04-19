<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

// Handle Update Profile
if (isset($_POST['update'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $dept_id = (int)$_POST['department_id'];

    $sql = "UPDATE users SET name='$name', email='$email', department_id='$dept_id' WHERE id='$user_id'";
    if ($conn->query($sql) === TRUE) {
        $_SESSION['name'] = $name; // Update session name
        $message = "<div class='alert alert-success border-0 shadow-sm'><i class='fas fa-check-circle me-1'></i> Profil berhasil diperbarui!</div>";
    } else {
        $message = "<div class='alert alert-danger border-0 shadow-sm'>Gagal memperbarui profil: " . $conn->error . "</div>";
    }
}

// Handle Change Password
if (isset($_POST['change_password'])) {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    // Fetch user for password verification
    $user_data = $conn->query("SELECT password FROM users WHERE id='$user_id'")->fetch_assoc();

    if (password_verify($current_pass, $user_data['password'])) {
        if ($new_pass === $confirm_pass) {
            $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET password='$hashed_pass' WHERE id='$user_id'";
            if ($conn->query($sql) === TRUE) {
                $message = "<div class='alert alert-success border-0 shadow-sm'><i class='fas fa-lock me-1'></i> Password berhasil diubah!</div>";
            } else {
                $message = "<div class='alert alert-danger border-0 shadow-sm'>Terjadi kesalahan database.</div>";
            }
        } else {
            $message = "<div class='alert alert-danger border-0 shadow-sm'>Konfirmasi password baru tidak cocok.</div>";
        }
    } else {
        $message = "<div class='alert alert-danger border-0 shadow-sm'>Password saat ini salah.</div>";
    }
}

// Fetch current user data
$user = $conn->query("SELECT * FROM users WHERE id='$user_id'")->fetch_assoc();
$faculties = $conn->query("SELECT * FROM faculties ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - DigiRepo</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; }
        .profile-card { border: none; border-radius: 24px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); background: white; padding: 40px; }
        .btn-primary { background-color: #1e3a8a; border: none; border-radius: 12px; padding: 12px 24px; font-weight: 700; }
        .form-control, .form-select { border-radius: 12px; padding: 12px; border: 1px solid #e2e8f0; background-color: #f8fafc; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm py-3" style="background-color: #0f172a;">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../index.php">DigiRepo.</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link text-white small fw-bold" href="../index.php"><i class="fas fa-home me-1"></i> Beranda</a>
                <?php if($_SESSION['role'] == 'admin'): ?>
                    <a class="nav-link text-white small fw-bold ms-3" href="../admin/dashboard.php"><i class="fas fa-shield-alt me-1"></i> Dashboard Admin</a>
                <?php else: ?>
                    <a class="nav-link text-white small fw-bold ms-3" href="dashboard.php"><i class="fas fa-th-large me-1"></i> Dashboard Saya</a>
                <?php endif; ?>
                <a class="nav-link text-white small fw-bold ms-3" href="../logout.php"><i class="fas fa-sign-out-alt me-1"></i> Keluar</a>
            </div>
        </div>
    </nav>

    <div class="container py-5 text-center">
        <div class="row justify-content-center">
            <div class="col-lg-6 text-start">
                <div class="profile-card">
                    <h2 class="fw-bold mb-4 text-center">Profil Pengguna</h2>
                    
                    <?php echo $message; ?>

                    <form action="profile.php" method="POST">
                        <div class="mb-3 text-center">
                            <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; font-size: 2rem; font-weight: 800;">
                                <?php echo substr($user['name'], 0, 1); ?>
                            </div>
                            <p class="text-muted small">ID Pengguna: #<?php echo $user['id']; ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Nama Lengkap</label>
                            <input type="text" name="name" class="form-control" value="<?php echo $user['name']; ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Email</label>
                            <input type="email" name="email" class="form-control" value="<?php echo $user['email']; ?>" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small text-secondary">Fakultas & Program Studi</label>
                            <select name="department_id" class="form-select" required>
                                <option value="">Pilih Prodi...</option>
                                <?php while($f = $faculties->fetch_assoc()): ?>
                                    <optgroup label="<?php echo $f['name']; ?>">
                                        <?php 
                                        $fac_id = $f['id'];
                                        $depts_res = $conn->query("SELECT * FROM departments WHERE faculty_id = $fac_id ORDER BY name ASC");
                                        while($d = $depts_res->fetch_assoc()):
                                        ?>
                                            <option value="<?php echo $d['id']; ?>" <?php echo ($user['department_id'] == $d['id']) ? 'selected' : ''; ?>>
                                                <?php echo $d['name']; ?> (<?php echo $d['level']; ?>)
                                            </option>
                                        <?php endwhile; ?>
                                    </optgroup>
                                <?php endwhile; ?>
                            </select>
                            <div class="form-text small opacity-75">Fakultas akan terdeteksi otomatis berdasarkan Prodi yang Anda pilih.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Role</label>
                            <input type="text" class="form-control bg-light" value="<?php echo ucfirst($user['role']); ?>" readonly>
                        </div>

                        <button type="submit" name="update" class="btn btn-primary w-100 mt-2">
                            <i class="fas fa-save me-2"></i> Perbarui Profil
                        </button>
                    </form>

                    <hr class="my-5 opacity-10">

                    <h4 class="fw-bold mb-4"><i class="fas fa-key me-2 text-warning"></i>Ganti Password</h4>
                    <form action="profile.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Password Saat Ini</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Password Baru</label>
                            <input type="password" name="new_password" class="form-control" required minlength="6">
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-secondary">Konfirmasi Password Baru</label>
                            <input type="password" name="confirm_password" class="form-control" required minlength="6">
                        </div>
                        <button type="submit" name="change_password" class="btn btn-outline-dark w-100 fw-bold rounded-3 py-3">
                            Ubah Password Akun
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
