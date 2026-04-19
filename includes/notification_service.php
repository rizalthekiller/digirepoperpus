<?php
/**
 * DigiRepo Notification Service (SMTP Enabled)
 * Menggunakan kredensial dari config.php
 */
require_once 'config.php';

class NotificationService {
    
    private static $adminEmail = "admin@repo.id"; 

    /**
     * Send email notification (Using SMTP)
     * Untuk hasil terbaik, unduh PHPMailer dan masukkan ke folder includes/vendor/
     */
    private static function send($to, $subject, $body, $attachment = null) {
        // Integrasi PHPMailer (Aktif)
        require_once 'PHPMailer/src/Exception.php';
        require_once 'PHPMailer/src/PHPMailer.php';
        require_once 'PHPMailer/src/SMTP.php';
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT;

            // Recipients
            $mail->setFrom(SMTP_FROM, SMTP_NAME);
            $mail->addAddress($to);

            // Attachment
            if ($attachment && isset($attachment['data']) && isset($attachment['filename'])) {
                $mail->addStringAttachment($attachment['data'], $attachment['filename']);
            }

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Email gagal dikirim via PHPMailer. Error: {$mail->ErrorInfo}");
            return false;
        }
    }

    /**
     * Notify Admin: New User Registration
     */
    public static function notifyAdminNewUser($userName, $userEmail) {
        $subject = "[DigiRepo] Verifikasi Akun Baru: $userName";
        $body = "
            <div style='font-family: Arial, sans-serif; padding: 20px;'>
                <h2>Permintaan Verifikasi Akun baru</h2>
                <p>Halo Admin,</p>
                <p>Pengguna baru telah mendaftar dan menunggu verifikasi Anda:</p>
                <ul>
                    <li><strong>Nama:</strong> $userName</li>
                    <li><strong>Email:</strong> $userEmail</li>
                </ul>
                <p><a href='http://localhost/admin/users.php' style='background: #1e3a8a; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Buka Manajemen User</a></p>
            </div>";
        self::send(self::$adminEmail, $subject, $body);
    }

    /**
     * Notify Admin: New Thesis Submission
     */
    public static function notifyAdminNewThesis($userName, $thesisTitle) {
        $subject = "[DigiRepo] Antrean Skripsi Baru: $thesisTitle";
        $body = "
            <div style='font-family: Arial, sans-serif; padding: 20px;'>
                <h2>Pengajuan Skripsi Baru</h2>
                <p>Halo Admin,</p>
                <p><strong>$userName</strong> baru saja mengunggah skripsi baru:</p>
                <p><em>\"$thesisTitle\"</em></p>
                <p><a href='http://localhost/admin/verification_queue.php' style='background: #1e3a8a; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Verifikasi Sekarang</a></p>
            </div>";
        self::send(self::$adminEmail, $subject, $body);
    }

    /**
     * Notify User: Thesis Result (Approval/Rejection)
     */
    public static function notifyUserThesisResult($userEmail, $thesisTitle, $status) {
        $statusLabel = $status == 'approved' ? "DISETUJUI" : "DITOLAK";
        $color = $status == 'approved' ? "#10b981" : "#ef4444";
        $message = $status == 'approved' 
            ? "Selamat! Skripsi Anda telah divalidasi dan kini tersedia di repositori publik."
            : "Mohon maaf, skripsi Anda belum memenuhi syarat verifikasi kami. Silakan hubungi prodi untuk detailnya.";

        $subject = "[DigiRepo] Status Pengajuan Skripsi: $statusLabel";
        $body = "
            <div style='font-family: Arial, sans-serif; padding: 20px;'>
                <h2 style='color: $color;'>Hasil Verifikasi Skripsi</h2>
                <p>Halo,</p>
                <p>Pengajuan skripsi Anda dengan judul:</p>
                <p><strong>\"$thesisTitle\"</strong></p>
                <p>Status: <strong style='color: $color;'>$statusLabel</strong></p>
                <p>$message</p>
                <hr>
                <p><small>Pesan ini dikirim secara otomatis oleh sistem DigiRepo.</small></p>
            </div>";
        self::send($userEmail, $subject, $body);
    }

    /**
     * Send Self-Upload Certificate to User
     */
    public static function sendSelfUploadCertificate($userEmail, $subject, $content, $authorName = "") {
        // Generate PDF using Dompdf
        $pdfOutput = null;
        try {
            require_once 'dompdf/autoload.inc.php';
            $options = new \Dompdf\Options();
            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('chroot', realpath(dirname(__DIR__))); // Allow local paths
            $dompdf = new \Dompdf\Dompdf($options);
            
            // Convert URLs to local paths for reliability in Dompdf
            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
            $baseUrl = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/";
            
            // Speed Optimization: Local paths help Dompdf process much faster than URLs
            $localContent = str_replace($baseUrl, dirname(__DIR__) . "/", $content);
            $localContent = str_replace('src="uploads/', 'src="' . dirname(__DIR__) . '/uploads/', $localContent);

            // Render HTML to PDF
            $html = "<style>
                @page { margin: 0; }
                body { font-family: Arial, Helvetica, sans-serif; padding: 0; margin: 0; }
                .content-area { padding: 40px; }
                img { max-width: 100%; height: auto; display: block; margin: 0 auto; }
            </style><div class='content-area'>" . $localContent . "</div>";
            
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $pdfOutput = $dompdf->output();
        } catch (Exception $e) {
            error_log("Dompdf Gagal: " . $e->getMessage());
        }

        $attachment = null;
        if ($pdfOutput) {
            $attachment = [
                'data' => $pdfOutput,
                'filename' => 'Surat_Keterangan_Unggah_Mandiri.pdf'
            ];
        }

        $body = "
            <div style='font-family: Arial, sans-serif; padding: 20px; color: #334155;'>
                <h2 style='color: #1e3a8a;'>Terima Kasih!</h2>
                <p>Halo <strong>" . htmlspecialchars($authorName) . "</strong>,</p>
                <p>Terima kasih telah berkontribusi dengan mengunggah karya ilmiah Anda ke dalam Repositori Digital Perpustakaan.</p>
                <p>Bersama email ini, kami lampirkan <strong>Surat Keterangan Unggah Mandiri</strong> dalam format PDF sebagai bukti validasi digital atas karya Anda.</p>
                
                <p>Harap simpan file lampiran ini dengan baik. Jika Anda memerlukan verifikasi manual, pihak terkait dapat memindai QR Code yang tertera pada surat tersebut.</p>
                <p>Hormat kami,<br><strong>Tim Repositori DigiRepo</strong></p>
                
                <hr style='border: 0; border-top: 1px solid #f1f5f9; margin: 20px 0;'>
                <p style='text-align: center; color: #94a3b8; font-size: 0.75rem;'>
                    Pesan ini diterbitkan secara otomatis oleh Sistem Repositori Digital. Mohon tidak membalas email ini.
                </p>
            </div>";
        return self::send($userEmail, $subject, $body, $attachment);
    }
}
?>
