<?php
// ============================================
// ملف تصدير الأجهزة - نسخة كاملة ومكتملة
// يدعم تصدير CSV و Excel مع فلترة متقدمة
// ============================================

// بداية الجلسة إذا لم تكن بدأت
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// رفع مستوى الإبلاغ عن الأخطاء للتصحيح
error_reporting(E_ALL);
ini_set('display_errors', 1);

// تضمين ملفات الاعتماد
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/Database.php';

// التحقق من المصادقة والصلاحيات
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    die('<div style="text-align: center; padding: 50px; font-family: Arial;">
        <h2 style="color: red;">خطأ: الوصول مرفوع</h2>
        <p>يجب تسجيل الدخول أولاً للوصول إلى هذه الصفحة.</p>
        <a href="login.php" style="color: blue; text-decoration: none;">العودة إلى صفحة تسجيل الدخول</a>
    </div>');
}

// التحقق من صلاحية التصدير
function hasPermission($permission_name) {
    global $db;
    
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    $user_id = $_SESSION['user_id'];
    
    try {
        $stmt = $db->getPDO()->prepare("
            SELECT COUNT(*) 
            FROM تخصيص_الصلاحيات tp
            JOIN الصلاحيات p ON tp.معرف_الصلاحية = p.معرف_الصلاحية
            WHERE tp.معرف_المستخدم = ? 
            AND p.اسم_الصلاحية = ?
            AND p.مفعل = 1
        ");
        
        $stmt->execute([$user_id, $permission_name]);
        $count = $stmt->fetchColumn();
        
        return $count > 0;
    } catch (PDOException $e) {
        error_log("خطأ في التحقق من الصلاحية: " . $e->getMessage());
        return false;
    }
}

// إنشاء كائن قاعدة البيانات
$db = new Database();

// التحقق من الصلاحية
if (!hasPermission('تصدير_البيانات')) {
    die('<div style="text-align: center; padding: 50px; font-family: Arial; direction: rtl;">
        <h2 style="color: red;"><i class="fas fa-ban"></i> خطأ في الصلاحيات</h2>
        <p style="font-size: 18px; margin: 20px 0;">
            عذراً، ليس لديك صلاحية لتصدير البيانات.
        </p>
        <p>يجب أن تمتلك صلاحية "تصدير_البيانات" للوصول إلى هذه الصفحة.</p>
        <a href="devices.php" style="color: white; background: #007bff; padding: 10px 20px; 
           text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 20px;">
           <i class="fas fa-arrow-right"></i> العودة إلى صفحة الأجهزة
        </a>
    </div>');
}

// تحديد نوع التصدير (csv أو excel)
$export_type = $_GET['type'] ?? 'csv';

// معالجة معايير الفلترة
$filters = [
    'device_type' => $_GET['device_type'] ?? '',
    'status' => $_GET['status'] ?? '',
    'unit' => $_GET['unit'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
    'device_number' => $_GET['device_number'] ?? '',
    'receiver_name' => $_GET['receiver_name'] ?? ''
];

// بناء استعلام SQL مع الفلاتر
function buildQuery($filters) {
    $query = "SELECT 
        معرف_فريد,
        رقم_الجهاز,
        نوع_الجهاز,
        حالة_الجهاز,
        الشهر,
        F,
        S,
        K,
        CASE هوائي WHEN 1 THEN 'نعم' ELSE 'لا' END as هوائي,
        CASE شاحن WHEN 1 THEN 'نعم' ELSE 'لا' END as شاحن,
        CASE قاعدة_شحن WHEN 1 THEN 'نعم' ELSE 'لا' END as قاعدة_شحن,
        CASE بطارية WHEN 1 THEN 'نعم' ELSE 'لا' END as بطارية,
        CASE كبل WHEN 1 THEN 'نعم' ELSE 'لا' END as كبل,
        CASE سماعة WHEN 1 THEN 'نعم' ELSE 'لا' END as سماعة,
        اسم_الجهاز,
        اسم_المستلم,
        تاريخ_الاستلام,
        الجهة_المستلمة,
        ملاحظات,
        الاحتياجات,
        أخرى,
        تاريخ_الصيانة,
        (SELECT الاسم_الكامل FROM المستخدمين WHERE معرف_المستخدم = مستخدم_الإدخال) as مدخل_البيانات,
        تاريخ_الإدخال
    FROM الأجهزة 
    WHERE 1=1";
    
    $params = [];
    
    // فلترة حسب نوع الجهاز
    if (!empty($filters['device_type'])) {
        $query .= " AND نوع_الجهاز LIKE ?";
        $params[] = '%' . $filters['device_type'] . '%';
    }
    
    // فلترة حسب حالة الجهاز
    if (!empty($filters['status'])) {
        $query .= " AND حالة_الجهاز = ?";
        $params[] = $filters['status'];
    }
    
    // فلترة حسب الجهة المستلمة
    if (!empty($filters['unit'])) {
        $query .= " AND الجهة_المستلمة LIKE ?";
        $params[] = '%' . $filters['unit'] . '%';
    }
    
    // فلترة حسب رقم الجهاز
    if (!empty($filters['device_number'])) {
        $query .= " AND رقم_الجهاز LIKE ?";
        $params[] = '%' . $filters['device_number'] . '%';
    }
    
    // فلترة حسب اسم المستلم
    if (!empty($filters['receiver_name'])) {
        $query .= " AND اسم_المستلم LIKE ?";
        $params[] = '%' . $filters['receiver_name'] . '%';
    }
    
    // فلترة حسب التاريخ من
    if (!empty($filters['date_from'])) {
        $query .= " AND DATE(تاريخ_الإدخال) >= ?";
        $params[] = $filters['date_from'];
    }
    
    // فلترة حسب التاريخ إلى
    if (!empty($filters['date_to'])) {
        $query .= " AND DATE(تاريخ_الإدخال) <= ?";
        $params[] = $filters['date_to'];
    }
    
    // ترتيب النتائج
    $query .= " ORDER BY تاريخ_الإدخال DESC, معرف_فريد DESC";
    
    return ['query' => $query, 'params' => $params];
}

// الحصول على البيانات
$queryData = buildQuery($filters);
$query = $queryData['query'];
$params = $queryData['params'];

try {
    $stmt = $db->getPDO()->prepare($query);
    $stmt->execute($params);
    $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('<div style="text-align: center; padding: 50px; font-family: Arial; direction: rtl;">
        <h2 style="color: red;"><i class="fas fa-database"></i> خطأ في قاعدة البيانات</h2>
        <p>' . htmlspecialchars($e->getMessage()) . '</p>
        <a href="devices.php" style="color: white; background: #dc3545; padding: 10px 20px; 
           text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 20px;">
           العودة إلى صفحة الأجهزة
        </a>
    </div>');
}

// التحقق من وجود بيانات للتصدير
if (empty($devices)) {
    // حفظ رسالة في الجلسة وإعادة التوجيه
    $_SESSION['export_message'] = [
        'type' => 'warning',
        'title' => 'لا توجد بيانات',
        'message' => 'لم يتم العثور على بيانات تطابق معايير البحث للتصدير.'
    ];
    header('Location: devices.php');
    exit;
}

// ============================================
// دالة تصدير إلى CSV
// ============================================
function exportToCSV($devices) {
    // إعداد رؤوس HTTP
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="الأجهزة_' . date('Y-m-d_H-i') . '.csv"');
    header('Cache-Control: max-age=0');
    header('Pragma: public');
    
    // فتح تدفق الإخراج
    $output = fopen('php://output', 'w');
    
    // إضافة BOM لضبط الترميز في Excel
    fwrite($output, "\xEF\xBB\xBF");
    
    // رؤوس الأعمدة
    $headers = [
        'معرف فريد',
        'رقم الجهاز',
        'نوع الجهاز',
        'حالة الجهاز',
        'الشهر',
        'F',
        'S',
        'K',
        'هوائي',
        'شاحن',
        'قاعدة شحن',
        'بطارية',
        'كبل',
        'سماعة',
        'اسم الجهاز',
        'اسم المستلم',
        'تاريخ الاستلام',
        'الجهة المستلمة',
        'ملاحظات',
        'الاحتياجات',
        'أخرى',
        'تاريخ الصيانة',
        'مدخل البيانات',
        'تاريخ الإدخال'
    ];
    
    // كتابة الرؤوس
    fputcsv($output, $headers, ',', '"', '\\');
    
    // كتابة البيانات
    foreach ($devices as $device) {
        $row = [
            $device['معرف_فريد'],
            $device['رقم_الجهاز'],
            $device['نوع_الجهاز'],
            $device['حالة_الجهاز'],
            $device['الشهر'] ?? '',
            $device['F'] ?? '',
            $device['S'] ?? '',
            $device['K'] ?? '',
            $device['هوائي'],
            $device['شاحن'],
            $device['قاعدة_شحن'],
            $device['بطارية'],
            $device['كبل'],
            $device['سماعة'],
            $device['اسم_الجهاز'] ?? '',
            $device['اسم_المستلم'],
            $device['تاريخ_الاستلام'],
            $device['الجهة_المستلمة'],
            $device['ملاحظات'] ?? '',
            $device['الاحتياجات'] ?? '',
            $device['أخرى'] ?? '',
            $device['تاريخ_الصيانة'] ?? '',
            $device['مدخل_البيانات'] ?? '',
            $device['تاريخ_الإدخال']
        ];
        fputcsv($output, $row, ',', '"', '\\');
    }
    
    fclose($output);
    exit;
}

// ============================================
// دالة تصدير إلى Excel (بدون مكتبات خارجية)
// ============================================
function exportToExcel($devices) {
    // إعداد رؤوس HTTP
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="الأجهزة_' . date('Y-m-d_H-i') . '.xls"');
    header('Cache-Control: max-age=0');
    header('Pragma: public');
    
    // بداية ملف HTML/Excel
    echo '<!DOCTYPE html>
    <html dir="rtl">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <title>تقرير الأجهزة</title>
        <style>
            body { font-family: "Arial", sans-serif; direction: rtl; }
            table { border-collapse: collapse; width: 100%; font-size: 12px; }
            th { background-color: #4CAF50; color: white; padding: 8px; text-align: center; border: 1px solid #ddd; }
            td { padding: 6px; border: 1px solid #ddd; }
            tr:nth-child(even) { background-color: #f2f2f2; }
            .header-info { margin-bottom: 20px; padding: 10px; background: #f8f9fa; border: 1px solid #dee2e6; }
            .summary { margin-top: 20px; padding: 10px; background: #e9ecef; border: 1px solid #ced4da; }
        </style>
    </head>
    <body>';
    
    // معلومات التقرير
    echo '<div class="header-info">
        <h2 style="text-align: center; color: #2c3e50;">تقرير الأجهزة العسكرية</h2>
        <p style="text-align: center;"><strong>تاريخ التصدير:</strong> ' . date('Y-m-d H:i:s') . '</p>
        <p style="text-align: center;"><strong>عدد السجلات:</strong> ' . count($devices) . '</p>
        <p style="text-align: center;"><strong>المستخدم:</strong> ' . htmlspecialchars($_SESSION['user_fullname'] ?? 'غير معروف') . '</p>
    </div>';
    
    // جدول البيانات
    echo '<table border="1">
        <thead>
            <tr>
                <th>#</th>
                <th>رقم الجهاز</th>
                <th>نوع الجهاز</th>
                <th>الحالة</th>
                <th>اسم الجهاز</th>
                <th>اسم المستلم</th>
                <th>الجهة المستلمة</th>
                <th>تاريخ الاستلام</th>
                <th>الشهر</th>
                <th>F</th>
                <th>S</th>
                <th>K</th>
                <th>هوائي</th>
                <th>شاحن</th>
                <th>قاعدة شحن</th>
                <th>بطارية</th>
                <th>كبل</th>
                <th>سماعة</th>
                <th>ملاحظات</th>
                <th>تاريخ الصيانة</th>
                <th>مدخل البيانات</th>
                <th>تاريخ الإدخال</th>
            </tr>
        </thead>
        <tbody>';
    
    $counter = 1;
    foreach ($devices as $device) {
        // تحديد لون الصف حسب حالة الجهاز
        $row_class = '';
        switch ($device['حالة_الجهاز']) {
            case 'معطل': $row_class = 'style="background-color: #ffe6e6;"'; break;
            case 'صيانة': $row_class = 'style="background-color: #fff3cd;"'; break;
            case 'مفقود': $row_class = 'style="background-color: #f8d7da;"'; break;
            case 'جيد': $row_class = 'style="background-color: #d4edda;"'; break;
        }
        
        echo '<tr ' . $row_class . '>
            <td>' . $counter++ . '</td>
            <td>' . htmlspecialchars($device['رقم_الجهاز']) . '</td>
            <td>' . htmlspecialchars($device['نوع_الجهاز']) . '</td>
            <td>' . htmlspecialchars($device['حالة_الجهاز']) . '</td>
            <td>' . htmlspecialchars($device['اسم_الجهاز'] ?? '') . '</td>
            <td>' . htmlspecialchars($device['اسم_المستلم']) . '</td>
            <td>' . htmlspecialchars($device['الجهة_المستلمة']) . '</td>
            <td>' . htmlspecialchars($device['تاريخ_الاستلام']) . '</td>
            <td>' . htmlspecialchars($device['الشهر'] ?? '') . '</td>
            <td>' . htmlspecialchars($device['F'] ?? '') . '</td>
            <td>' . htmlspecialchars($device['S'] ?? '') . '</td>
            <td>' . htmlspecialchars($device['K'] ?? '') . '</td>
            <td>' . htmlspecialchars($device['هوائي']) . '</td>
            <td>' . htmlspecialchars($device['شاحن']) . '</td>
            <td>' . htmlspecialchars($device['قاعدة_شحن']) . '</td>
            <td>' . htmlspecialchars($device['بطارية']) . '</td>
            <td>' . htmlspecialchars($device['كبل']) . '</td>
            <td>' . htmlspecialchars($device['سماعة']) . '</td>
            <td>' . htmlspecialchars($device['ملاحظات'] ?? '') . '</td>
            <td>' . htmlspecialchars($device['تاريخ_الصيانة'] ?? '') . '</td>
            <td>' . htmlspecialchars($device['مدخل_البيانات'] ?? '') . '</td>
            <td>' . htmlspecialchars($device['تاريخ_الإدخال']) . '</td>
        </tr>';
    }
    
    echo '</tbody></table>';
    
    // ملخص الإحصائيات
    $status_counts = [];
    $type_counts = [];
    foreach ($devices as $device) {
        $status = $device['حالة_الجهاز'];
        $type = $device['نوع_الجهاز'];
        
        if (!isset($status_counts[$status])) $status_counts[$status] = 0;
        if (!isset($type_counts[$type])) $type_counts[$type] = 0;
        
        $status_counts[$status]++;
        $type_counts[$type]++;
    }
    
    echo '<div class="summary">
        <h3>ملخص الإحصائيات</h3>
        <table border="1" style="width: auto; margin: 10px 0;">
            <tr><th>حالة الجهاز</th><th>العدد</th></tr>';
    
    foreach ($status_counts as $status => $count) {
        echo '<tr><td>' . htmlspecialchars($status) . '</td><td>' . $count . '</td></tr>';
    }
    
    echo '</table>
        <table border="1" style="width: auto; margin: 10px 0;">
            <tr><th>نوع الجهاز</th><th>العدد</th></tr>';
    
    foreach ($type_counts as $type => $count) {
        echo '<tr><td>' . htmlspecialchars($type) . '</td><td>' . $count . '</td></tr>';
    }
    
    echo '</table>
    </div>
    
    <div style="text-align: center; margin-top: 30px; font-size: 11px; color: #666;">
        <p>تم إنشاء هذا التقرير تلقائياً بواسطة نظام إدارة الأجهزة العسكرية</p>
        <p>تاريخ الإنشاء: ' . date('Y-m-d H:i:s') . ' | إجمالي السجلات: ' . count($devices) . '</p>
    </div>
    
    </body>
    </html>';
    
    exit;
}

// ============================================
// دالة تصدير إلى PDF (بديل باستخدام HTML)
// ============================================
function exportToPDF($devices) {
    // هذا مثال مبسط، يمكن استخدام مكتبة مثل TCPDF أو mPDF للPDF الحقيقي
    header('Content-Type: application/vnd.ms-excel'); // استخدام Excel كبديل
    header('Content-Disposition: attachment; filename="الأجهزة_' . date('Y-m-d_H-i') . '.xls"');
    
    // نفس كود Excel مع تعديلات طفيفة
    exportToExcel($devices);
}

// ============================================
// اختيار نوع التصدير المناسب
// ============================================
try {
    switch ($export_type) {
        case 'excel':
        case 'xls':
        case 'xlsx':
            exportToExcel($devices);
            break;
            
        case 'pdf':
            exportToPDF($devices);
            break;
            
        case 'csv':
        default:
            exportToCSV($devices);
            break;
    }
    
    // تسجيل عملية التصدير في سجل الأنشطة
    $user_id = $_SESSION['user_id'];
    $filter_summary = '';
    if (!empty($filters['device_type'])) $filter_summary .= 'نوع: ' . $filters['device_type'] . '، ';
    if (!empty($filters['status'])) $filter_summary .= 'حالة: ' . $filters['status'] . '، ';
    if (!empty($filters['unit'])) $filter_summary .= 'وحدة: ' . $filters['unit'] . '، ';
    if (!empty($filters['date_from'])) $filter_summary .= 'من: ' . $filters['date_from'] . '، ';
    if (!empty($filters['date_to'])) $filter_summary .= 'إلى: ' . $filters['date_to'] . '، ';
    
    $details = "تم تصدير " . count($devices) . " جهاز إلى " . strtoupper($export_type);
    if ($filter_summary) {
        $details .= " (مع فلاتر: " . rtrim($filter_summary, '، ') . ")";
    }
    
    // دالة تسجيل النشاط
    function logActivity($user_id, $activity_type, $table_name = null, $record_id = null, $details = null) {
        global $db;
        
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'غير معروف';
        $browser = $_SERVER['HTTP_USER_AGENT'] ?? 'غير معروف';
        
        try {
            $stmt = $db->getPDO()->prepare("
                INSERT INTO سجل_الأنشطة (
                    معرف_المستخدم, نوع_النشاط, الجدول_المستهدف, 
                    معرف_السجل_المستهدف, التفاصيل, عنوان_IP, المتصفح
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $user_id, 
                $activity_type, 
                'الأجهزة', 
                null, 
                $details, 
                $ip_address, 
                $browser
            ]);
        } catch (PDOException $e) {
            error_log("خطأ في تسجيل النشاط: " . $e->getMessage());
        }
    }
    
    logActivity($user_id, 'تصدير بيانات', 'الأجهزة', null, $details);
    
} catch (Exception $e) {
    die('<div style="text-align: center; padding: 50px; font-family: Arial; direction: rtl;">
        <h2 style="color: red;"><i class="fas fa-exclamation-triangle"></i> خطأ في عملية التصدير</h2>
        <p>' . htmlspecialchars($e->getMessage()) . '</p>
        <div style="margin-top: 20px;">
            <a href="devices.php" style="color: white; background: #007bff; padding: 10px 20px; 
               text-decoration: none; border-radius: 5px; margin: 5px;">
               العودة إلى الأجهزة
            </a>
            <a href="export_devices.php?type=csv" style="color: white; background: #28a745; padding: 10px 20px; 
               text-decoration: none; border-radius: 5px; margin: 5px;">
               محاولة تصدير CSV
            </a>
        </div>
    </div>');
}

// ============================================
// كود احتياطي إذا فشل التصدير
// ============================================
// هذا الكود لن ينفذ عادة لأنه يتم الخروج exit() في دوال التصدير
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>خطأ في التصدير - <?php echo htmlspecialchars('نظام إدارة الأجهزة'); ?></title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            direction: rtl;
        }
        
        .error-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
            max-width: 500px;
        }
        
        .error-icon {
            font-size: 60px;
            color: #dc3545;
            margin-bottom: 20px;
        }
        
        .btn-group {
            margin-top: 30px;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 5px;
            border-radius: 5px;
            text-decoration: none;
            color: white;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #007bff;
        }
        
        .btn-success {
            background: #28a745;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h2>حدث خطأ غير متوقع</h2>
        <p>فشلت عملية التصدير. يرجى المحاولة مرة أخرى أو اختيار نوع تصدير مختلف.</p>
        
        <div class="btn-group">
            <a href="devices.php" class="btn btn-primary">
                <i class="fas fa-arrow-right"></i> العودة إلى الأجهزة
            </a>
            <a href="export_devices.php?type=csv" class="btn btn-success">
                <i class="fas fa-file-csv"></i> تصدير كـ CSV
            </a>
            <a href="export_devices.php?type=excel" class="btn btn-success">
                <i class="fas fa-file-excel"></i> تصدير كـ Excel
            </a>
        </div>
        
        <div style="margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
            <p style="font-size: 14px; color: #666; margin: 0;">
                <strong>ملاحظة:</strong> يمكنك إعادة المحاولة أو الاتصال بالدعم الفني إذا استمرت المشكلة.
            </p>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html>
