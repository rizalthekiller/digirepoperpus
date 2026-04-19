<?php include 'includes/db.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Koleksi Terbaru - DigiRepo UINSI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .card { border: none; border-radius: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); transition: 0.3s; }
        .card:hover { transform: translateY(-5px); }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container py-5">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Koleksi Terbaru</h2>
            <p class="text-secondary">Menampilkan 20 karya ilmiah terbaru yang telah disetujui.</p>
        </div>

        <div class="row g-4">
            <?php
            $sql = "SELECT t.*, u.name as author, d.name as dept_name 
                    FROM theses t 
                    JOIN users u ON t.user_id = u.id 
                    JOIN departments d ON u.department_id = d.id 
                    WHERE t.status='approved' 
                    ORDER BY t.created_at DESC 
                    LIMIT 20";
            $result = $conn->query($sql);
            if ($result->num_rows > 0):
                while($row = $result->fetch_assoc()):
            ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 p-3">
                        <div class="card-body d-flex flex-column">
                            <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1 mb-3 align-self-start small fw-bold"><?php echo $row['dept_name']; ?></span>
                            <h5 class="card-title fw-bold mb-3">
                                <a href="skripsi/detail.php?id=<?php echo $row['id']; ?>" class="text-decoration-none text-dark"><?php echo $row['title']; ?></a>
                            </h5>
                            <p class="text-secondary small mb-4"><?php echo substr($row['abstract'], 0, 100); ?>...</p>
                            <div class="mt-auto d-flex justify-content-between align-items-center">
                                <div class="small">
                                    <div class="fw-bold"><?php echo $row['author']; ?></div>
                                    <div class="text-muted"><?php echo $row['year']; ?></div>
                                </div>
                                <a href="skripsi/detail.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary rounded-pill px-3">Detail</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; else: ?>
                <div class="col-12 text-center py-5">
                    <p class="text-muted">Belum ada koleksi.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
