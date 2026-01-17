<?php
/**
 * ملف التثبيت - تشغيله مرة واحدة فقط لإنشاء قاعدة البيانات والجداول
 */

// إعدادات قاعدة البيانات - يرجى تعديلها حسب إعدادات الخادم
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'military_devices');

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // الاتصال بخادم MySQL
        $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // إنشاء قاعدة البيانات
        $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE " . DB_NAME);
        
        // تضمين ملف قاعدة البيانات لإنشاء الجداول
        require_once 'database.php';
        
        $db = new Database();
        
        $message = 'تم تثبيت النظام بنجاح! يمكنك الآن تسجيل الدخول باستخدام:
        <br><strong>اسم المستخدم:</strong> admin
        <br><strong>كلمة المرور:</strong> admin123';
        
    } catch (Exception $e) {
        $error = 'خطأ في التثبيت: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تثبيت نظام إدارة الأجهزة العسكرية</title>
    
    <!-- Bootstrap RTL CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .install-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card install-container">
                    <div class="card-header text-center bg-primary text-white">
                        <h3>
                            <i class="fas fa-cogs"></i>
                            تثبيت نظام إدارة الأجهزة العسكرية
                        </h3>
                    </div>
                    
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i>
                                <?php echo $message; ?>
                            </div>
                            <div class="text-center">
                                <a href="login.php" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt"></i>
                                    الانتقال إلى صفحة تسجيل الدخول
                                </a>
                            </div>
                        <?php elseif ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i>
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>تعليمات التثبيت:</strong>
                                <ul class="mt-2 mb-0">
                                    <li>تأكد من إنشاء قاعدة بيانات MySQL</li>
                                    <li>تأكد من صحة إعدادات قاعدة البيانات في الملف</li>
                                    <li>اضغط على زر التثبيت لبدء العملية</li>
                                </ul>
                            </div>
                            
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6><i class="fas fa-database"></i> إعدادات قاعدة البيانات الحالية</h6>
                                </div>
                                <div class="card-body">
                                    <p><strong>الخادم:</strong> <?php echo DB_HOST; ?></p>
                                    <p><strong>اسم المستخدم:</strong> <?php echo DB_USER; ?></p>
                                    <p><strong>اسم قاعدة البيانات:</strong> <?php echo DB_NAME; ?></p>
                                    <small class="text-muted">
                                        يمكنك تعديل هذه الإعدادات في ملف install.php
                                    </small>
                                </div>
                            </div>
                            
                            <form method="POST">
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="fas fa-play"></i>
                                        بدء التثبيت
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-footer text-center text-muted">
                        <small>
                            <i class="fas fa-shield-alt"></i>
                            نظام إدارة الأجهزة العسكرية - الإصدار 1.0
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>