<?php
include 'includes/db.php';

echo "<h2>Migrasi Database DigiRepo</h2>";

// 1. Periksa dan Tambah kolom 'type' ke tabel 'theses'
$check_type = $conn->query("SHOW COLUMNS FROM theses LIKE 'type'");
if ($check_type->num_rows == 0) {
    $sql = "ALTER TABLE theses ADD COLUMN type ENUM('Skripsi', 'Thesis', 'Disertasi') NOT NULL AFTER title";
    if ($conn->query($sql)) {
        echo "<p style='color: green;'>[OK] Kolom 'type' berhasil ditambahkan ke tabel 'theses'.</p>";
    } else {
        echo "<p style='color: red;'>[ERROR] Gagal menambah kolom 'type': " . $conn->error . "</p>";
    }
}

// 2. Pastikan kolom 'id' pada 'theses' bisa menampung string (karena pakai uniqid)
$check_id_type = $conn->query("DESCRIBE theses id");
if ($check_id_type) {
    $row = $check_id_type->fetch_assoc();
    if (strpos($row['Type'], 'int') !== false) {
        $sql = "ALTER TABLE theses MODIFY COLUMN id VARCHAR(50) PRIMARY KEY";
        if ($conn->query($sql)) {
             echo "<p style='color: green;'>[OK] Kolom 'id' pada 'theses' berhasil diubah menjadi VARCHAR.</p>";
        }
    }
}

// 3. Tambah kolom 'rejection_reason' ke tabel 'theses'
$check_reason = $conn->query("SHOW COLUMNS FROM theses LIKE 'rejection_reason'");
if ($check_reason->num_rows == 0) {
    $sql = "ALTER TABLE theses ADD COLUMN rejection_reason TEXT NULL AFTER status";
    if ($conn->query($sql)) {
        echo "<p style='color: green;'>[OK] Kolom 'rejection_reason' berhasil ditambahkan ke tabel 'theses'.</p>";
    }
}

// 4. Tambah kolom 'certificate_number' ke tabel 'theses'
$check_cert_num = $conn->query("SHOW COLUMNS FROM theses LIKE 'certificate_number'");
if ($check_cert_num->num_rows == 0) {
    $sql = "ALTER TABLE theses ADD COLUMN certificate_number VARCHAR(50) NULL AFTER status";
    if ($conn->query($sql)) {
        echo "<p style='color: green;'>[OK] Kolom 'certificate_number' berhasil ditambahkan ke tabel 'theses'.</p>";
    }
}

// 4.5 Kolom 'was_rejected' untuk deteksi surat otomatis
$check_was_rejected = $conn->query("SHOW COLUMNS FROM theses LIKE 'was_rejected'");
if ($check_was_rejected->num_rows == 0) {
    $sql = "ALTER TABLE theses ADD COLUMN was_rejected TINYINT(1) DEFAULT 0 AFTER status";
    if ($conn->query($sql)) {
        echo "<p style='color: green;'>[OK] Kolom 'was_rejected' berhasil ditambahkan ke tabel 'theses'.</p>";
    }
}

// 4.7 Kolom 'nim' untuk tabel users
$check_nim = $conn->query("SHOW COLUMNS FROM users LIKE 'nim'");
if ($check_nim->num_rows == 0) {
    $sql = "ALTER TABLE users ADD COLUMN nim VARCHAR(30) NULL AFTER email";
    if ($conn->query($sql)) {
        echo "<p style='color: green;'>[OK] Kolom 'nim' berhasil ditambahkan ke tabel 'users'.</p>";
    }
}

// 4.9 Kolom 'verification_hash' untuk validasi Digital Signature
$check_hash = $conn->query("SHOW COLUMNS FROM theses LIKE 'verification_hash'");
if ($check_hash->num_rows == 0) {
    $sql = "ALTER TABLE theses ADD COLUMN verification_hash VARCHAR(64) NULL AFTER certificate_number";
    if ($conn->query($sql)) {
        echo "<p style='color: green;'>[OK] Kolom 'verification_hash' berhasil ditambahkan ke tabel 'theses'.</p>";
    }
}

// 4.95 Kolom 'certificate_content' untuk simpan history surat agar bisa didownload mhs
$check_cert_content = $conn->query("SHOW COLUMNS FROM theses LIKE 'certificate_content'");
if ($check_cert_content->num_rows == 0) {
    $sql = "ALTER TABLE theses ADD COLUMN certificate_content TEXT NULL AFTER verification_hash";
    if ($conn->query($sql)) {
        echo "<p style='color: green;'>[OK] Kolom 'certificate_content' berhasil ditambahkan ke tabel 'theses'.</p>";
    }
}

// 4.97 Kolom 'supervisor_name' untuk nama pembimbing
$check_supervisor = $conn->query("SHOW COLUMNS FROM theses LIKE 'supervisor_name'");
if ($check_supervisor->num_rows == 0) {
    $sql = "ALTER TABLE theses ADD COLUMN supervisor_name VARCHAR(255) NULL AFTER keywords";
    if ($conn->query($sql)) {
        echo "<p style='color: green;'>[OK] Kolom 'supervisor_name' berhasil ditambahkan ke tabel 'theses'.</p>";
    }
}

// 4.98 Tambah kolom 'level' dan 'code' ke tabel 'faculties'
$check_fac_level = $conn->query("SHOW COLUMNS FROM faculties LIKE 'level'");
if ($check_fac_level->num_rows == 0) {
    $conn->query("ALTER TABLE faculties ADD COLUMN level ENUM('S1', 'S2', 'S3') DEFAULT 'S1' AFTER name");
    $conn->query("ALTER TABLE faculties ADD COLUMN code VARCHAR(20) NULL AFTER level");
    echo "<p style='color: green;'>[OK] Kolom 'level' dan 'code' berhasil ditambahkan ke tabel 'faculties'.</p>";
}

// 4.99 Tambah kolom 'level' dan 'code' ke tabel 'departments'
$check_dept_level = $conn->query("SHOW COLUMNS FROM departments LIKE 'level'");
if ($check_dept_level->num_rows == 0) {
    $conn->query("ALTER TABLE departments ADD COLUMN level ENUM('S1', 'S2', 'S3') DEFAULT 'S1' AFTER name");
    $conn->query("ALTER TABLE departments ADD COLUMN code VARCHAR(20) NULL AFTER level");
    echo "<p style='color: green;'>[OK] Kolom 'level' dan 'code' berhasil ditambahkan ke tabel 'departments'.</p>";
}

// 4.995 Kolom 'updated_at' untuk tabel theses jika belum ada
$check_updated = $conn->query("SHOW COLUMNS FROM theses LIKE 'updated_at'");
if ($check_updated->num_rows == 0) {
    if($conn->query("ALTER TABLE theses ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP")) {
        echo "<p style='color: green;'>[OK] Kolom 'updated_at' berhasil ditambahkan ke tabel 'theses'.</p>";
    }
}

// 4.996 Kolom 'delivery_status' untuk melacak status pengiriman email sertifikat
$check_delivery = $conn->query("SHOW COLUMNS FROM theses LIKE 'delivery_status'");
if ($check_delivery->num_rows == 0) {
    $sql = "ALTER TABLE theses ADD COLUMN delivery_status ENUM('not_sent', 'sent', 'failed') DEFAULT 'not_sent' AFTER certificate_content";
    if ($conn->query($sql)) {
        echo "<p style='color: green;'>[OK] Kolom 'delivery_status' berhasil ditambahkan ke tabel 'theses'.</p>";
    }
}

// 5. Tabel Settings untuk Template Surat
$create_settings = "CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT
) ENGINE=InnoDB;";
if ($conn->query($create_settings)) {
    // Insert default template if not exists
    $check_tpl = $conn->query("SELECT * FROM settings WHERE setting_key = 'cert_template'");
    if ($check_tpl->num_rows == 0) {
        $default_tpl = "<div style=\"font-family: 'Times New Roman', Times, serif; color: #000; padding: 10px; line-height: 1.5;\">
    <table style=\"width: 100%; border-bottom: 3px solid #000; padding-bottom: 15px; margin-bottom: 20px; border-collapse: collapse;\">
        <tr>
            <td style=\"width: 100px; text-align: center; vertical-align: middle;\">
                [LOGO]
            </td>
            <td style=\"text-align: center; vertical-align: middle; padding-right: 100px;\">
                <h3 style=\"margin: 0; text-transform: uppercase; font-size: 16px;\">KEMENTERIAN AGAMA REPUBLIK INDONESIA</h3>
                <h2 style=\"margin: 2px 0; text-transform: uppercase; font-size: 18px;\">UNIVERSITAS ISLAM NEGERI</h2>
                <h2 style=\"margin: 0; text-transform: uppercase; font-size: 17px;\">SULTAN AJI MUHAMMAD IDRIS SAMARINDA</h2>
                <h3 style=\"margin: 2px 0; text-transform: uppercase; font-size: 16px;\">UPT. PERPUSTAKAAN</h3>
                <p style=\"margin: 2px 0; font-size: 11px;\">Jalan H.A.M. Rifaddin Samarinda 75131 Samarinda</p>
                <p style=\"margin: 2px 0; font-size: 11px;\">Telp. (0541) 7270222 Faksimile (0541) 4114393</p>
                <p style=\"margin: 0; font-size: 11px;\">Website : http://lib.uinsi.ac.id Gmail : perpustakaan@uinsi.ac.id</p>
            </td>
        </tr>
    </table>

    <div style=\"text-align: center; margin-bottom: 25px;\">
        <h3 style=\"margin: 0; text-decoration: underline; text-transform: uppercase; font-size: 18px;\">SURAT KETERANGAN</h3>
        <p style=\"margin: 5px 0; font-weight: bold;\">Nomor : [NOMOR_SURAT]</p>
    </div>

    <div style=\"text-align: justify; margin-bottom: 20px;\">
        <p>Kepala Perpustakaan Universitas Islam Negeri Sultan Aji Muhammad Idris (UINSI) Samarinda menerangkan bahwa :</p>
        
        <table style=\"width: 100%; margin: 20px 0; border: none; border-collapse: collapse;\">
            <tr>
                <td style=\"width: 150px; padding: 5px 0; vertical-align: top;\">NAMA</td>
                <td style=\"width: 20px; vertical-align: top;\">:</td>
                <td style=\"font-weight: bold; text-transform: uppercase; vertical-align: top;\">[NAMA]</td>
            </tr>
            <tr>
                <td style=\"padding: 5px 0; vertical-align: top;\">N.I.M.</td>
                <td style=\"vertical-align: top;\">:</td>
                <td style=\"vertical-align: top;\">[NIM]</td>
            </tr>
            <tr>
                <td style=\"padding: 5px 0; vertical-align: top;\">FAKULTAS</td>
                <td style=\"vertical-align: top;\">:</td>
                <td style=\"vertical-align: top;\">[FAKULTAS]</td>
            </tr>
            <tr>
                <td style=\"padding: 5px 0; vertical-align: top;\">PRODI</td>
                <td style=\"vertical-align: top;\">:</td>
                <td style=\"vertical-align: top;\">[PRODI]</td>
            </tr>
        </table>

        <p>Yang bersangkutan telah selesai melakukan unggah Skripsi/Disertasi pada Repositori Perpustakaan Universitas Islam Negeri Sultan Aji Muhammad Idris (UINSI) Samarinda.</p>
        <p>Demikian Surat Keterangan ini diberikan, agar dapat dipergunakan sebagaimana mestinya.</p>
    </div>

    <div style=\"margin-top: 40px;\">
        <table style=\"width: 100%; border: none;\">
            <tr>
                <td style=\"width: 60%;\"></td>
                <td style=\"width: 40%; text-align: left; vertical-align: top;\">
                    <p style=\"margin-bottom: 0;\">Samarinda, [TANGGAL]</p>
                    <p style=\"margin-top: 0;\">Mengetahui,<br>Kepala Perpustakaan,</p>
                    
                    <div style=\"margin: 10px 0; text-align: left;\">
                        [QR_CODE]
                    </div>
                    
                    <p style=\"font-weight: bold; text-decoration: underline; margin-bottom: 0;\">SITI SHOLIHAH, S.Ag., S.S., M.Hum</p>
                    <p style=\"margin: 0;\">NIP. 197412152009012005</p>
                </td>
            </tr>
        </table>
    </div>

    <div style=\"margin-top: 50px; border-top: 1px solid #eee; padding-top: 10px; text-align: center; font-size: 10px; color: #999;\">
        ID Verifikasi Digital: [VERIF_LINK]
    </div>
</div>";
        $escaped_tpl = $conn->real_escape_string($default_tpl);
        $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('cert_template', '$escaped_tpl')");
        $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('cert_last_number', '001')");
    }
    echo "<p style='color: green;'>[OK] Tabel 'settings' siap dengan template default.</p>";
}

// 2. Pastikan tabel 'faculties' ada
$create_faculties = "CREATE TABLE IF NOT EXISTS faculties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
) ENGINE=InnoDB;";
if ($conn->query($create_faculties)) {
    echo "<p style='color: green;'>[OK] Tabel 'faculties' siap.</p>";
}

// 3. Pastikan tabel 'departments' ada dan memiliki 'faculty_id'
$create_departments = "CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    faculty_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    FOREIGN KEY (faculty_id) REFERENCES faculties(id) ON DELETE CASCADE
) ENGINE=InnoDB;";
if ($conn->query($create_departments)) {
    echo "<p style='color: green;'>[OK] Tabel 'departments' siap.</p>";
}

// 4. Update tabel 'users' untuk memiliki 'department_id' jika belum ada
$check_dept_id = $conn->query("SHOW COLUMNS FROM users LIKE 'department_id'");
if ($check_dept_id->num_rows == 0) {
    $sql = "ALTER TABLE users ADD COLUMN department_id INT NULL AFTER is_verified";
    if ($conn->query($sql)) {
        echo "<p style='color: green;'>[OK] Kolom 'department_id' berhasil ditambahkan ke tabel 'users'.</p>";
    }
}

echo "<br><a href='index.php'>Kembali ke Beranda</a>";
?>
