<?php 
include '../includes/db.php';
session_start();

// Brute Force Protection (Session-based)
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = time();
}

// Check lockout (5 attempts, 1 minute wait)
if ($_SESSION['login_attempts'] >= 5 && (time() - $_SESSION['last_attempt_time']) < 60) {
    $error = "Terlalu banyak percobaan login. Silakan tunggu 1 menit.";
} elseif (isset($_POST['login'])) {
    $email = $_POST['email']; 
    $password = $_POST['password'];

    // SQL Injection Protection using Prepared Statements
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // Reset attempts on success
            unset($_SESSION['login_attempts']);
            unset($_SESSION['last_attempt_time']);

            // Security: Session Fixation Protection
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['is_verified'] = $user['is_verified'];

            if ($user['role'] == 'admin') {
                header("Location: ../admin/dashboard.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();
        } else {
            $_SESSION['login_attempts']++;
            $_SESSION['last_attempt_time'] = time();
            $error = "Password salah (Sisa percobaan: " . (5 - $_SESSION['login_attempts']) . ")";
        }
        $stmt->close();
    } else {
        $_SESSION['login_attempts']++;
        $_SESSION['last_attempt_time'] = time();
        $error = "Email tidak terdaftar.";
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - DigiRepo</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%); 
            height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
        }
        .auth-card { 
            border: none; 
            border-radius: 24px; 
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); 
            padding: 48px; 
            width: 100%; 
            max-width: 420px; 
            background: white; 
        }
        .btn-dark-blue { 
            background-color: #1e3a8a; 
            color: white; 
            border: none;
            border-radius: 12px; 
            padding: 14px; 
            font-weight: 700; 
            transition: all 0.3s ease;
        }
        .btn-dark-blue:hover { 
            background-color: #1e40af; 
            color: white;
            box-shadow: 0 4px 6px -1px rgba(30, 58, 138, 0.3);
            transform: translateY(-1px);
        }
        .form-control { 
            border-radius: 12px; 
            padding: 12px 12px 12px 45px; 
            border: 1px solid #e2e8f0; 
            background-color: #f8fafc;
        }
        .form-control:focus {
            box-shadow: none;
            border-color: #1e3a8a;
            background-color: white;
        }
        .input-group-text {
            background-color: transparent;
            border: none;
            position: absolute;
            z-index: 10;
            padding: 12px 15px;
            color: #94a3b8;
        }
        .input-group { position: relative; }
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="text-center mb-5">
            <h1 class="fw-bold" style="color: #1e3a8a; letter-spacing: -1px;">DigiRepo.</h1>
            <p class="text-muted small">Selamat datang kembali! Silakan masuk.</p>
        </div>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-danger py-2 small fw-bold text-center mb-4 border-0 rounded-3 shadow-sm">
                <i class="fas fa-exclamation-circle me-1"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="mb-3">
                <label class="form-label fw-bold small text-secondary px-1">Email Kampus</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                    <input type="email" name="email" class="form-control" required placeholder="nama@univ.ac.id">
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label fw-bold small text-secondary px-1">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" name="password" class="form-control" required placeholder="••••••••">
                </div>
            </div>
            <button type="submit" name="login" class="btn btn-dark-blue w-100 mb-4 shadow-sm">Login</button>
            <div class="text-center">
                <p class="small text-muted mb-0">Belum punya akun? <a href="register.php" class="fw-bold text-decoration-none" style="color: #1e3a8a;">Daftar Sekarang</a></p>
            </div>
        </form>
    </div>
</body>
</html>
