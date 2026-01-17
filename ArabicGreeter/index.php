<?php
session_start();

// إعادة التوجيه إلى صفحة تسجيل الدخول إذا لم يكن المستخدم مسجلاً
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// إعادة التوجيه إلى لوحة التحكم
header('Location: dashboard.php');
exit();
?>