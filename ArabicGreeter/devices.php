<?php
require_once 'auth.php';

requireLogin();
requirePermission('عرض_الأجهزة');

$db = new Database();

// معالجة الفلاتر
$device_type = $_GET['device_type'] ?? '';
$status = $_GET['status'] ?? '';
$unit = $_GET['unit'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// بناء الاستعلام
$query = "SELECT * FROM الأجهزة WHERE 1=1";
$params = [];

if ($device_type) {
    $query .= " AND نوع_الجهاز LIKE ?";
    $params[] = "%$device_type%";
}

if ($status) {
    $query .= " AND حالة_الجهاز = ?";
    $params[] = $status;
}

if ($unit) {
    $query .= " AND الجهة_المستلمة LIKE ?";
    $params[] = "%$unit%";
}

if ($date_from) {
    $query .= " AND DATE(تاريخ_الإدخال) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $query .= " AND DATE(تاريخ_الإدخال) <= ?";
    $params[] = $date_to;
}

$query .= " ORDER BY تاريخ_الإدخال DESC";

$stmt = $db->getPDO()->prepare($query);
$stmt->execute($params);
$devices = $stmt->fetchAll();

// الحصول على قوائم للفلاتر
$device_types = $db->getPDO()->query("SELECT DISTINCT نوع_الجهاز FROM الأجهزة ORDER BY نوع_الجهاز")->fetchAll();
$units = $db->getPDO()->query("SELECT DISTINCT الجهة_المستلمة FROM الأجهزة ORDER BY الجهة_المستلمة")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الأجهزة - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap RTL CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- شريط التنقل -->
    <?php include 'navbar.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>
                        <i class="fas fa-desktop"></i>
                        إدارة الأجهزة العسكرية
                    </h2>
                    <div>
                        <?php if (hasPermission('إدخال_الأجهزة')): ?>
                        <a href="add_device.php" class="btn btn-success">
                            <i class="fas fa-plus"></i>
                            إضافة جهاز جديد
                        </a>
                        <?php endif; ?>
                        
                        <?php if (hasPermission('تصدير_البيانات')): ?>
                        <a href="export_devices.php?<?php echo http_build_query($_GET); ?>" class="btn btn-primary">
                            <i class="fas fa-download"></i>
                            تصدير البيانات
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- فلاتر البحث -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-filter"></i> فلاتر البحث</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">نوع الجهاز</label>
                                <select name="device_type" class="form-select">
                                    <option value="">الكل</option>
                                    <?php foreach ($device_types as $type): ?>
                                    <option value="<?php echo htmlspecialchars($type['نوع_الجهاز']); ?>" 
                                            <?php echo ($device_type == $type['نوع_الجهاز']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($type['نوع_الجهاز']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">الحالة</label>
                                <select name="status" class="form-select">
                                    <option value="">الكل</option>
                                    <option value="جيد" <?php echo ($status == 'جيد') ? 'selected' : ''; ?>>جيد</option>
                                    <option value="معطل" <?php echo ($status == 'معطل') ? 'selected' : ''; ?>>معطل</option>
                                    <option value="صيانة" <?php echo ($status == 'صيانة') ? 'selected' : ''; ?>>صيانة</option>
                                    <option value="مفقود" <?php echo ($status == 'مفقود') ? 'selected' : ''; ?>>مفقود</option>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">الجهة المستلمة</label>
                                <select name="unit" class="form-select">
                                    <option value="">الكل</option>
                                    <?php foreach ($units as $unit_item): ?>
                                    <option value="<?php echo htmlspecialchars($unit_item['الجهة_المستلمة']); ?>" 
                                            <?php echo ($unit == $unit_item['الجهة_المستلمة']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($unit_item['الجهة_المستلمة']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">من تاريخ</label>
                                <input type="date" name="date_from" class="form-control" 
                                       value="<?php echo htmlspecialchars($date_from); ?>">
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">إلى تاريخ</label>
                                <input type="date" name="date_to" class="form-control" 
                                       value="<?php echo htmlspecialchars($date_to); ?>">
                            </div>
                            
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-search"></i>
                                    بحث
                                </button>
                                <a href="devices.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i>
                                    إلغاء
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- جدول الأجهزة -->
                <div class="card">
                    <div class="card-header">
                        <h5>
                            <i class="fas fa-list"></i>
                            قائمة الأجهزة (<?php echo count($devices); ?> جهاز)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($devices)): ?>
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle fa-2x mb-2"></i>
                                <h5>لا توجد أجهزة</h5>
                                <p>لم يتم العثور على أجهزة تطابق معايير البحث</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>رقم الجهاز</th>
                                            <th>نوع الجهاز</th>
                                            <th>اسم الجهاز</th>
                                            <th>الحالة</th>
                                            <th>الجهة المستلمة</th>
                                            <th>اسم المستلم</th>
                                            <th>تاريخ الاستلام</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($devices as $device): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($device['رقم_الجهاز']); ?></strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($device['نوع_الجهاز']); ?></td>
                                            <td><?php echo htmlspecialchars($device['اسم_الجهاز'] ?? '-'); ?></td>
                                            <td>
                                                <?php
                                                $status_class = '';
                                                switch ($device['حالة_الجهاز']) {
                                                    case 'جيد': $status_class = 'success'; break;
                                                    case 'معطل': $status_class = 'danger'; break;
                                                    case 'صيانة': $status_class = 'warning'; break;
                                                    case 'مفقود': $status_class = 'dark'; break;
                                                }
                                                ?>
                                                <span class="badge bg-<?php echo $status_class; ?>">
                                                    <?php echo htmlspecialchars($device['حالة_الجهاز']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($device['الجهة_المستلمة']); ?></td>
                                            <td><?php echo htmlspecialchars($device['اسم_المستلم']); ?></td>
                                            <td><?php echo date('Y-m-d', strtotime($device['تاريخ_الاستلام'])); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="view_device.php?id=<?php echo $device['معرف_فريد']; ?>" 
                                                       class="btn btn-outline-primary" title="عرض التفاصيل">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    
                                                    <?php if (hasPermission('تعديل_الأجهزة')): ?>
                                                    <a href="edit_device.php?id=<?php echo $device['معرف_فريد']; ?>" 
                                                       class="btn btn-outline-warning" title="تعديل">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (hasPermission('صيانة_الأجهزة')): ?>
                                                    <a href="maintenance.php?id=<?php echo $device['معرف_فريد']; ?>" 
                                                       class="btn btn-outline-info" title="صيانة">
                                                        <i class="fas fa-tools"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (hasPermission('حذف_الأجهزة')): ?>
                                                    <a href="delete_device.php?id=<?php echo $device['معرف_فريد']; ?>" 
                                                       class="btn btn-outline-danger" title="حذف"
                                                       onclick="return confirm('هل أنت متأكد من حذف هذا الجهاز؟')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>