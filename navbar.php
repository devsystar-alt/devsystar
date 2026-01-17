<?php
if (!isset($_SESSION['user_id'])) {
    return;
}

$auth = new Auth();
$user_permissions = $auth->getUserPermissions();
?>

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
                    <a class="nav-link" href="dashboard.php">
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
                
                <?php if (hasPermission('تصدير_البيانات')): ?>
                <li class="nav-item">
                    <a class="nav-link" href="export.php">
                        <i class="fas fa-download"></i> تصدير البيانات
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