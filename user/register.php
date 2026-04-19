<?php 
include '../includes/db.php';

if (isset($_POST['register'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $nim = $conn->real_escape_string($_POST['nim']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $dept = (int)$_POST['department_id'];

    // Check if NIM or Email already exists
    $check_sql = "SELECT id, nim, email FROM users WHERE nim = '$nim' OR email = '$email'";
    $check_result = $conn->query($check_sql);

    if ($check_result->num_rows > 0) {
        $existing = $check_result->fetch_assoc();
        if ($existing['nim'] == $nim) {
            $error = "Pendaftaran Gagal: NIM $nim sudah terdaftar di sistem. Anda tidak dapat mengajukan akun lagi.";
        } else {
            $error = "Pendaftaran Gagal: Email $email sudah terdaftar di sistem. Anda tidak dapat mengajukan akun lagi.";
        }
    } else {
        $sql = "INSERT INTO users (name, nim, email, password, role, is_verified, department_id) 
                VALUES ('$name', '$nim', '$email', '$password', 'mahasiswa', 0, '$dept')";

        if ($conn->query($sql) === TRUE) {
            // Notify Admin
            require_once '../includes/notification_service.php';
            NotificationService::notifyAdminNewUser($name, $email);

            $success = "Pendaftaran berhasil! Tunggu verifikasi admin.";
        } else {
            $error = "Terjadi kesalahan: " . $conn->error;
        }
    }
}

// Fetch grouped depts
$faculties = $conn->query("SELECT * FROM faculties ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Mahasiswa - DigiRepo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .auth-card { border: none; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); padding: 40px; width: 100%; max-width: 500px; background: white; }
        .btn-primary { border-radius: 12px; padding: 12px; font-weight: 700; }
        .form-control, .form-select { border-radius: 10px; padding: 12px; border: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="text-center mb-4">
            <h1 class="fw-bold text-primary">Register.</h1>
            <p class="text-muted">Bergabung dengan Repo Perpustakaan</p>
        </div>

        <?php if(isset($success)): ?>
            <div class="alert alert-success border-0 rounded-3 mb-4 fw-bold small text-center"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if(isset($error)): ?>
            <div class="alert alert-danger border-0 rounded-3 mb-4 fw-bold small text-center"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <div class="mb-3">
                <label class="form-label fw-bold small">Nama Lengkap</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold small">NIM (Nomor Induk Mahasiswa)</label>
                <input type="text" name="nim" class="form-control" required placeholder="Contoh: 12345678">
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold small">Email Kampus</label>
                <input type="email" name="email" class="form-control" required placeholder="mahasiswa@univ.ac.id">
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold small">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="mb-4">
                <label class="form-label fw-bold small">Program Studi</label>
                <select name="department_id" class="form-select" required>
                    <option value="">Pilih Prodi...</option>
                    <?php while($f = $faculties->fetch_assoc()): ?>
                        <optgroup label="<?php echo $f['name']; ?> (<?php echo $f['level']; ?>)">
                            <?php 
                            $fac_id = $f['id'];
                            $depts_res = $conn->query("SELECT * FROM departments WHERE faculty_id = $fac_id ORDER BY name ASC");
                            while($d = $depts_res->fetch_assoc()):
                            ?>
                                <option value="<?php echo $d['id']; ?>"><?php echo $d['name']; ?> (<?php echo $d['level']; ?>) - [<?php echo $d['code']; ?>]</option>
                            <?php endwhile; ?>
                        </optgroup>
                    <?php endwhile; ?>
                </select>
            </div>
            <button type="submit" name="register" class="btn btn-primary w-100 mb-3">Daftar Akun</button>
            <div class="text-center">
                <p class="small text-muted mb-0">Sudah punya akun? <a href="login.php" class="text-primary fw-bold text-decoration-none">Login</a></p>
            </div>
        </form>
    </div>
</body>
</html>
