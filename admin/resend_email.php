<?php
session_start();
include '../includes/db.php';
require_once '../includes/notification_service.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../user/login.php");
    exit();
}

if (isset($_GET['id'])) {
    $thesis_id = $conn->real_escape_string($_GET['id']);
    
    // Fetch data
    $sql = "SELECT t.*, u.email, u.name as author_name 
            FROM theses t 
            JOIN users u ON t.user_id = u.id 
            WHERE t.id = '$thesis_id' AND t.certificate_number IS NOT NULL";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        
        $subject = "Surat Keterangan Unggah Mandiri - " . $data['certificate_number'];
        $is_sent = NotificationService::sendSelfUploadCertificate($data['email'], $subject, $data['certificate_content'], $data['author_name']);
        
        $status = $is_sent ? 'sent' : 'failed';
        $conn->query("UPDATE theses SET delivery_status = '$status' WHERE id = '$thesis_id'");
        
        if ($is_sent) {
            $_SESSION['queue_msg'] = "<div class='alert alert-success border-0 shadow-sm'>
                <i class='fas fa-check-circle me-2'></i> Email berhasil dikirim ulang ke " . $data['email'] . "
            </div>";
        } else {
            $_SESSION['queue_msg'] = "<div class='alert alert-danger border-0 shadow-sm'>
                <i class='fas fa-exclamation-triangle me-2'></i> Gagal mengirim ulang email. Silakan periksa konfigurasi SMTP.
            </div>";
        }
    }
}

header("Location: certificates_list.php");
exit();
