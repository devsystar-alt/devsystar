<?php
// إعدادات قاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_NAME', 'military_devices');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// إعدادات عامة
define('SITE_NAME', 'نظام إدارة الأجهزة العسكرية');
define('SESSION_TIMEOUT', 3600); // ساعة واحدة بالثواني

// إعداد التوقيت للمنطقة العربية
date_default_timezone_set('Asia/Riyadh');

// إعدادات الأمان
define('PASSWORD_SALT', 'military_system_2024');

// بدء الجلسة إذا لم تكن مبدوءة بالفعل
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// فحص انتهاء الجلسة
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
    session_unset();
    session_destroy();
    header('Location: login.php?expired=1');
    exit();
}

// تحديث وقت آخر نشاط
$_SESSION['last_activity'] = time();
?>