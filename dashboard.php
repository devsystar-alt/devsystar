<?php
require_once 'auth.php';

requireLogin();

$auth = new Auth();
$db = new Database();

$user_permissions = $auth->getUserPermissions();

// إحصائيات سريعة
$stats = [];

// عدد الأجهزة
if (hasPermission('عرض_الأجهزة')) {
    $stmt = $db->getPDO()->query("SELECT COUNT(*) FROM الأجهزة");
    $stats['total_devices'] = $stmt->fetchColumn();
    
    // إحصائيات حالة الأجهزة
    $stmt = $db->getPDO()->query("
        SELECT حالة_الجهاز, COUNT(*) as count 
        FROM الأجهزة 
        GROUP BY حالة_الجهاز
    ");
    $device_status = $stmt->fetchAll();
    $stats['device_status'] = $device_status;
}

// عدد المستخدمين (للمدير فقط)
if (hasPermission('إدارة_المستخدمين')) {
    $stmt = $db->getPDO()->query("SELECT COUNT(*) FROM المستخدمين WHERE مفعل = 1");
    $stats['total_users'] = $stmt->fetchColumn();
}

// آخر الأنشطة (للمدير فقط)
if (hasPermission('عرض_سجل_الأنشطة')) {
    $stmt = $db->getPDO()->query("
        SELECT sa.نوع_النشاط, sa.التفاصيل, sa.التاريخ, u.الاسم_الكامل
        FROM سجل_الأنشطة sa
        JOIN المستخدمين u ON sa.معرف_المستخدم = u.معرف_المستخدم
        ORDER BY sa.التاريخ DESC
        LIMIT 5
    ");
    $stats['recent_activities'] = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap RTL CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap" rel="stylesheet">
    
    <style>
        .navbar-brand {
            font-weight: bold;
            font-size: 1.2rem;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
        }
        
        .recent-activity {
            background: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        
        .sidebar {
            background: #2c3e50;
            min-height: calc(100vh - 76px);
        }
        
        .sidebar .nav-link {
            color: #ecf0f1;
            border-radius: 5px;
            margin: 2px 0;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: #3498db;
            color: white;
        }
        
        .main-content {
            background: #f8f9fa;
            min-height: calc(100vh - 76px);
            padding: 20px;
        }
    </style>
</head>
<body>
    <!-- شريط التنقل -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-shield-alt"></i>
                <?php echo SITE_NAME; ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-home"></i> الرئيسية
                        </a>
                    </li>
                    
                    <?php if (hasPermission('عرض_الأجهزة')): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-desktop"></i> الأجهزة
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="devices.php">عرض الأجهزة</a></li>
                            <?php if (hasPermission('إدخال_الأجهزة')): ?>
                            <li><a class="dropdown-item" href="add_device.php">إضافة جهاز</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (hasPermission('إدارة_المستخدمين')): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-users"></i> المستخدمين
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="users.php">إدارة المستخدمين</a></li>
                            <li><a class="dropdown-item" href="permissions.php">إدارة الصلاحيات</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (hasPermission('عرض_سجل_الأنشطة')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="activity_log.php">
                            <i class="fas fa-history"></i> سجل الأنشطة
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i>
                            <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php">الملف الشخصي</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">تسجيل الخروج</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="main-content">
                    <!-- ترحيب -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h2 class="mb-3">
                                <i class="fas fa-tachometer-alt"></i>
                                لوحة التحكم
                            </h2>
                            <p class="text-muted">
                                أهلاً وسهلاً <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?> - 
                                <?php echo date('Y-m-d H:i'); ?>
                            </p>
                        </div>
                    </div>

                    <!-- بطاقات الإحصائيات -->
                    <div class="row mb-4">
                        <?php if (isset($stats['total_devices'])): ?>
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-desktop fa-2x mb-3"></i>
                                    <div class="stat-number"><?php echo $stats['total_devices']; ?></div>
                                    <div>إجمالي الأجهزة</div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (isset($stats['total_users'])): ?>
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-users fa-2x mb-3"></i>
                                    <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                                    <div>المستخدمين النشطين</div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (isset($stats['device_status'])): ?>
                        <?php foreach ($stats['device_status'] as $status): ?>
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-circle fa-2x mb-3"></i>
                                    <div class="stat-number"><?php echo $status['count']; ?></div>
                                    <div><?php echo htmlspecialchars($status['حالة_الجهاز']); ?></div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="row">
                        <!-- الروابط السريعة -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-bolt"></i> الروابط السريعة</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (hasPermission('عرض_الأجهزة')): ?>
                                    <a href="devices.php" class="btn btn-outline-primary mb-2 me-2">
                                        <i class="fas fa-desktop"></i> عرض الأجهزة
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php if (hasPermission('إدخال_الأجهزة')): ?>
                                    <a href="add_device.php" class="btn btn-outline-success mb-2 me-2">
                                        <i class="fas fa-plus"></i> إضافة جهاز
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php if (hasPermission('تصدير_البيانات')): ?>
                                    <a href="export.php" class="btn btn-outline-info mb-2 me-2">
                                        <i class="fas fa-download"></i> تصدير البيانات
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php if (hasPermission('إدارة_المستخدمين')): ?>
                                    <a href="users.php" class="btn btn-outline-warning mb-2 me-2">
                                        <i class="fas fa-users"></i> إدارة المستخدمين
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- آخر الأنشطة -->
                        <?php if (isset($stats['recent_activities'])): ?>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-history"></i> آخر الأنشطة</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($stats['recent_activities'])): ?>
                                        <p class="text-muted">لا توجد أنشطة حديثة</p>
                                    <?php else: ?>
                                        <?php foreach ($stats['recent_activities'] as $activity): ?>
                                        <div class="recent-activity">
                                            <strong><?php echo htmlspecialchars($activity['نوع_النشاط']); ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($activity['الاسم_الكامل']); ?> - 
                                                <?php echo date('Y-m-d H:i', strtotime($activity['التاريخ'])); ?>
                                            </small>
                                            <?php if ($activity['التفاصيل']): ?>
                                            <br>
                                            <small><?php echo htmlspecialchars($activity['التفاصيل']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <?php endforeach; ?>
                                        <a href="activity_log.php" class="btn btn-sm btn-outline-primary mt-2">
                                            عرض كامل السجل
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
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