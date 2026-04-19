<?php include 'includes/db.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>F.A.Q - DigiRepo UINSI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }</style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container py-5">
        <h2 class="fw-bold mb-5 text-center">Frequently Asked Questions (F.A.Q)</h2>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="accordion shadow-sm" id="faqAccordion">
                    <div class="accordion-item border-0 mb-3 rounded-4 overflow-hidden">
                        <h2 class="accordion-header">
                            <button class="accordion-button fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                Bagaimana cara mendaftar akun di DigiRepo?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-secondary">
                                Mahasiswa dapat mendaftar melalui menu "Daftar" dengan mengisi NIM, Nama, dan Email institusi. Setelah mendaftar, akun akan diverifikasi oleh admin perpustakaan sebelum dapat digunakan untuk unggah mandiri.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item border-0 mb-3 rounded-4 overflow-hidden">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                Berapa lama proses verifikasi skripsi?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-secondary">
                                Proses verifikasi biasanya memakan waktu 1-3 hari kerja. Petugas perpustakaan akan memeriksa kelengkapan data dan file PDF yang diunggah.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item border-0 mb-3 rounded-4 overflow-hidden">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                Bagaimana jika skripsi saya ditolak?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-secondary">
                                Jika ditolak, Anda akan menerima email informasi berisi alasan penolakan. Anda dapat melakukan perbaikan data atau file melalui Dashboard Mahasiswa pada menu "Edit" di skripsi terkait.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="text-center mt-5">
            <a href="index.php" class="btn btn-primary px-4 rounded-pill fw-bold">Kembali ke Beranda</a>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
