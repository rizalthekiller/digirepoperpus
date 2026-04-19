-- DIGIREPO DATABASE SCHEMA (ENHANCED)
-- Use this for XAMPP MySQL (phpMyAdmin)

-- Table: Fakultas
CREATE TABLE IF NOT EXISTS faculties (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  level ENUM('S1', 'S2', 'S3') DEFAULT 'S1',
  code VARCHAR(20) NULL
);

-- Table: Program Studi
CREATE TABLE IF NOT EXISTS departments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  faculty_id INT,
  name VARCHAR(255) NOT NULL,
  level ENUM('S1', 'S2', 'S3') DEFAULT 'S1',
  code VARCHAR(20) NULL,
  FOREIGN KEY (faculty_id) REFERENCES faculties(id) ON DELETE CASCADE
);

-- Table: Users
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin', 'mahasiswa', 'dosen') NOT NULL,
  is_verified TINYINT(1) DEFAULT 0,
  department_id INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (department_id) REFERENCES departments(id)
);

-- Table: Theses (Skripsi)
CREATE TABLE IF NOT EXISTS theses (
  id VARCHAR(36) PRIMARY KEY,
  user_id INT,
  title TEXT NOT NULL,
  year VARCHAR(4) NOT NULL,
  abstract TEXT NOT NULL,
  keywords TEXT,
  supervisor_name VARCHAR(255),
  type ENUM('Skripsi', 'Thesis', 'Disertasi') DEFAULT 'Skripsi',
  file_path VARCHAR(255),
  status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FULLTEXT (title, abstract, keywords),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- SEED DATA (Optional, for testing)
INSERT INTO faculties (id, name) VALUES (1, 'Teknik'), (2, 'Ekonomi');
INSERT INTO departments (id, faculty_id, name) VALUES (1, 1, 'Informatika'), (2, 1, 'Elektro'), (3, 2, 'Akuntansi');

-- Admin account (password: admin123)
INSERT INTO users (name, email, password, role, is_verified) 
VALUES ('Super Admin', 'admin@repo.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1);
