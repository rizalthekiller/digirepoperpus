<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die("Akses ditolak.");
}

// 1. Data Fakultas
$faculties_data = [
    ['Teknik dan Sains', 'S1', 'FT-S1'],
    ['Ekonomi dan Bisnis', 'S1', 'FEB-S1'],
    ['Pascasarjana Teknik', 'S2', 'SPS-T2'],
    ['Pascasarjana Ekonomi', 'S2', 'SPS-E2'],
    ['Program Doktoral Sains', 'S3', 'DOC-S3']
];
foreach ($faculties_data as $f) {
    $conn->query("INSERT IGNORE INTO faculties (name, level, code) VALUES ('{$f[0]}', '{$f[1]}', '{$f[2]}')");
}

// 2. Data Prodi
$depts = [
    ['Teknik Informatika', 1, 'S1', 'IF-S1'],
    ['Sistem Informasi', 1, 'S1', 'SI-S1'],
    ['Magister Teknik Sipil', 3, 'S2', 'MT-S2'],
    ['Magister Manajemen', 4, 'S2', 'MM-S2'],
    ['Doktor Ilmu Komputer', 5, 'S3', 'DK-S3']
];
foreach ($depts as $d) {
    $conn->query("INSERT IGNORE INTO departments (name, faculty_id, level, code) VALUES ('{$d[0]}', {$d[1]}, '{$d[2]}', '{$d[3]}')");
}

// 3. Data User (Mahasiswa)
$password = password_hash('password123', PASSWORD_DEFAULT);
$users = [
    ['Budi Santoso', 'budi@student.ac.id', 'mahasiswa', 1, 1],
    ['Siti Aminah', 'siti@student.ac.id', 'mahasiswa', 2, 1],
    ['Andi Wijaya', 'andi@student.ac.id', 'mahasiswa', 3, 1],
    ['Rina Pratama', 'rina@student.ac.id', 'mahasiswa', 1, 0],
    ['Eko Saputra', 'eko@student.ac.id', 'mahasiswa', 4, 1]
];
foreach ($users as $u) {
    $conn->query("INSERT IGNORE INTO users (name, email, password, role, department_id, is_verified) 
                  VALUES ('{$u[0]}', '{$u[1]}', '$password', '{$u[2]}', {$u[3]}, {$u[4]})");
}

// 4. Data Skripsi
$theses = [
    [1, 'Implementasi Algoritma C4.5 untuk Prediksi Kelulusan Mahasiswa', 'Abstract skripsi tentang data mining...', 'Skripsi', 'Dr. Ir. H. Ahmad Fauzi, M.T.', 'uploads/skripsi_1.pdf', 'approved'],
    [2, 'Analisis Strategi Pemasaran Digital pada UMKM di Samarinda', 'Abstract tentang pemasaran...', 'Skripsi', 'Siti Rahmawati, S.E., M.Si.', 'uploads/skripsi_2.pdf', 'pending'],
    [3, 'Tinjauan Yuridis terhadap Implementasi UU ITE di Indonesia', 'Abstract tentang hukum...', 'Skripsi', 'Prof. Dr. H. Zainuddin, S.H., M.Hum.', 'uploads/skripsi_3.pdf', 'approved'],
    [5, 'Pengaruh Media Pembelajaran Visual terhadap Pemahaman Siswa', 'Abstract tentang pendidikan...', 'Skripsi', 'Dwi Lestari, S.Pd., M.Ed.', 'uploads/skripsi_4.pdf', 'rejected']
];
foreach ($theses as $t) {
    $conn->query("INSERT IGNORE INTO theses (user_id, title, abstract, type, supervisor_name, file_path, status, created_at) 
                  VALUES ({$t[0]}, '{$t[1]}', '{$t[2]}', '{$t[3]}', '{$t[4]}', '{$t[5]}', '{$t[6]}', NOW())");
}

header("Location: dashboard.php?seed=success");
exit();
?>
