<?php 
include 'includes/db.php'; 

$by = isset($_GET['by']) ? $conn->real_escape_string($_GET['by']) : '';
$value = isset($_GET['value']) ? $conn->real_escape_string($_GET['value']) : '';

$page_title = "Browse Koleksi";
if ($by == 'year') $page_title = "Browse by Year";
if ($by == 'faculty') $page_title = "Browse by Faculty";
if ($by == 'type') $page_title = "Browse by Type";

if ($value) {
    if ($by == 'faculty') {
        $fac_name = $conn->query("SELECT name FROM faculties WHERE id = '$value'")->fetch_assoc()['name'];
        $page_title = "Items per Faculty: $fac_name";
    } else {
        $page_title = "Items for $by: $value";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - DigiRepo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .browse-container { background: white; border-radius: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); padding: 40px; margin-top: 30px; }
        .breadcrumb { background: transparent; padding: 0; margin-bottom: 30px; }
        .list-group-item { border: none; padding: 12px 20px; border-bottom: 1px solid #f1f5f9; transition: 0.2s; }
        .list-group-item:hover { background: #f8fafc; padding-left: 30px; }
        .item-row { border-bottom: 1px solid #f1f5f9; padding: 20px 0; }
        .item-row:last-child { border-bottom: none; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-5">
        <div class="browse-container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="browse.php" class="text-decoration-none">Browse</a></li>
                    <?php if ($by): ?>
                        <li class="breadcrumb-item <?php echo !$value ? 'active' : ''; ?>">
                            <?php if ($value): ?><a href="browse.php?by=<?php echo $by; ?>" class="text-decoration-none"><?php endif; ?>
                            <?php echo ucfirst($by); ?>
                            <?php if ($value): ?></a><?php endif; ?>
                        </li>
                    <?php endif; ?>
                    <?php if ($value): ?>
                        <li class="breadcrumb-item active"><?php echo $value; ?></li>
                    <?php endif; ?>
                </ol>
            </nav>

            <?php if (!$by): ?>
                <!-- Main Browse Menu -->
                <h2 class="fw-bold mb-4">Browse</h2>
                <p class="text-secondary mb-5">Please select a value to browse from the list below.</p>
                <div class="row g-4">
                    <div class="col-md-4">
                        <a href="browse.php?by=year" class="card p-4 text-center text-decoration-none shadow-sm border-0 rounded-4 hover-lift">
                            <i class="fas fa-calendar-alt fa-3x text-success mb-3"></i>
                            <h5 class="fw-bold text-dark mb-0">Year</h5>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="browse.php?by=faculty" class="card p-4 text-center text-decoration-none shadow-sm border-0 rounded-4 hover-lift">
                            <i class="fas fa-university fa-3x text-primary mb-3"></i>
                            <h5 class="fw-bold text-dark mb-0">Faculty</h5>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="browse.php?by=type" class="card p-4 text-center text-decoration-none shadow-sm border-0 rounded-4 hover-lift">
                            <i class="fas fa-tags fa-3x text-warning mb-3"></i>
                            <h5 class="fw-bold text-dark mb-0">Type</h5>
                        </a>
                    </div>
                </div>

            <?php elseif ($by && !$value): ?>
                <!-- Level 2: Values List -->
                <h2 class="fw-bold mb-4">Browse by <?php echo ucfirst($by); ?></h2>
                <p class="text-secondary mb-4">Please select a value to browse from the list below.</p>
                <div class="list-group rounded-4 overflow-hidden border">
                    <?php
                    if ($by == 'year') {
                        $res = $conn->query("SELECT year as val, COUNT(*) as count FROM theses WHERE status='approved' GROUP BY year ORDER BY year DESC");
                    } elseif ($by == 'faculty') {
                        $res = $conn->query("SELECT f.id as val, f.name as label, COUNT(t.id) as count 
                                           FROM faculties f 
                                           JOIN departments d ON d.faculty_id = f.id 
                                           JOIN users u ON u.department_id = d.id 
                                           JOIN theses t ON t.user_id = u.id 
                                           WHERE t.status='approved' 
                                           GROUP BY f.id ORDER BY f.name ASC");
                    } elseif ($by == 'type') {
                        $res = $conn->query("SELECT type as val, COUNT(*) as count FROM theses WHERE status='approved' GROUP BY type ORDER BY type ASC");
                    }

                    while ($row = $res->fetch_assoc()):
                        $display_val = isset($row['label']) ? $row['label'] : $row['val'];
                    ?>
                        <a href="browse.php?by=<?php echo $by; ?>&value=<?php echo urlencode($row['val']); ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <span class="fw-semibold"><?php echo $display_val; ?></span>
                            <span class="badge bg-primary rounded-pill"><?php echo $row['count']; ?></span>
                        </a>
                    <?php endwhile; ?>
                </div>

            <?php else: ?>
                <!-- Level 3: Items List -->
                <h2 class="fw-bold mb-4"><?php echo $page_title; ?></h2>
                <p class="text-secondary mb-5">Showing all items matches the criteria.</p>

                <div class="items-list">
                    <?php
                    $sql = "SELECT t.*, u.name as author, d.name as dept_name 
                            FROM theses t 
                            JOIN users u ON t.user_id = u.id 
                            JOIN departments d ON u.department_id = d.id 
                            WHERE t.status='approved'";
                    
                    if ($by == 'year') $sql .= " AND t.year = '$value'";
                    elseif ($by == 'faculty') $sql .= " AND d.faculty_id = '$value'";
                    elseif ($by == 'type') $sql .= " AND t.type = '$value'";

                    $sql .= " ORDER BY t.created_at DESC";
                    $res = $conn->query($sql);

                    if ($res->num_rows > 0):
                        while ($row = $res->fetch_assoc()):
                    ?>
                        <div class="item-row">
                            <h5 class="fw-bold mb-2">
                                <a href="skripsi/detail.php?id=<?php echo $row['id']; ?>" class="text-decoration-none text-primary"><?php echo $row['title']; ?></a>
                            </h5>
                            <div class="text-secondary small">
                                <span class="me-3"><i class="fas fa-user-edit me-1"></i> <?php echo $row['author']; ?></span>
                                <span class="me-3"><i class="fas fa-calendar-day me-1"></i> <?php echo $row['year']; ?></span>
                                <span><i class="fas fa-graduation-cap me-1"></i> <?php echo $row['dept_name']; ?></span>
                            </div>
                        </div>
                    <?php endwhile; else: ?>
                        <div class="text-center py-5">
                            <h4 class="text-muted">Tidak ada item ditemukan.</h4>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <style>
        .hover-lift { transition: transform 0.2s, box-shadow 0.2s; }
        .hover-lift:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important; }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
