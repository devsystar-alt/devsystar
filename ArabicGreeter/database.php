<?php
require_once 'config.php';

class Database {
    private $pdo;
    
    public function __construct() {
        $this->connect();
        $this->createTables();
        $this->createDefaultPermissions();
        $this->createDefaultAdmin();
    }
    
    private function connect() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
        }
    }
    
    public function getPDO() {
        return $this->pdo;
    }
    
    private function createTables() {
        $tables = [
            // جدول الصلاحيات
            "CREATE TABLE IF NOT EXISTS الصلاحيات (
                معرف_الصلاحية INT PRIMARY KEY AUTO_INCREMENT,
                اسم_الصلاحية VARCHAR(100) NOT NULL UNIQUE,
                وصف_الصلاحية TEXT,
                تاريخ_الإنشاء DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            // جدول المستخدمين
            "CREATE TABLE IF NOT EXISTS المستخدمين (
                معرف_المستخدم INT PRIMARY KEY AUTO_INCREMENT,
                اسم_المستخدم VARCHAR(50) NOT NULL UNIQUE,
                كلمة_المرور VARCHAR(255) NOT NULL,
                الاسم_الكامل VARCHAR(100) NOT NULL,
                البريد_الإلكتروني VARCHAR(100) UNIQUE,
                رقم_الهاتف VARCHAR(20),
                الوحدة_العسكرية VARCHAR(100),
                مفعل TINYINT DEFAULT 1,
                آخر_تسجيل_دخول DATETIME,
                تاريخ_انتهاء_الحساب DATE,
                تاريخ_التسجيل DATETIME DEFAULT CURRENT_TIMESTAMP,
                ملاحظات TEXT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            // جدول تخصيص الصلاحيات
            "CREATE TABLE IF NOT EXISTS تخصيص_الصلاحيات (
                معرف_التخصيص INT PRIMARY KEY AUTO_INCREMENT,
                معرف_المستخدم INT NOT NULL,
                معرف_الصلاحية INT NOT NULL,
                تاريخ_المنح DATETIME DEFAULT CURRENT_TIMESTAMP,
                منح_بواسطة INT,
                FOREIGN KEY (معرف_المستخدم) REFERENCES المستخدمين(معرف_المستخدم) ON DELETE CASCADE,
                FOREIGN KEY (معرف_الصلاحية) REFERENCES الصلاحيات(معرف_الصلاحية) ON DELETE CASCADE,
                FOREIGN KEY (منح_بواسطة) REFERENCES المستخدمين(معرف_المستخدم),
                UNIQUE KEY unique_user_permission (معرف_المستخدم, معرف_الصلاحية)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            // جدول الأجهزة
            "CREATE TABLE IF NOT EXISTS الأجهزة (
                معرف_فريد INT PRIMARY KEY AUTO_INCREMENT,
                رقم_الجهاز VARCHAR(50) NOT NULL UNIQUE,
                نوع_الجهاز VARCHAR(100) NOT NULL,
                حالة_الجهاز ENUM('جيد', 'معطل', 'صيانة', 'مفقود') NOT NULL,
                الشهر VARCHAR(20),
                F VARCHAR(50) NOT NULL,
                S VARCHAR(50) NOT NULL,
                K VARCHAR(50) NOT NULL,
                هوائي TINYINT DEFAULT 0,
                شاحن TINYINT DEFAULT 0,
                قاعدة_شحن TINYINT DEFAULT 0,
                بطارية TINYINT DEFAULT 0,
                كبل TINYINT DEFAULT 0,
                سماعة TINYINT DEFAULT 0,
                اسم_الجهاز VARCHAR(100),
                اسم_المستلم VARCHAR(100) NOT NULL,
                تاريخ_الاستلام DATE NOT NULL,
                الجهة_المستلمة VARCHAR(100) NOT NULL,
                ملاحظات TEXT,
                الاحتياجات TEXT,
                أخرى TEXT,
                تاريخ_الصيانة DATE,
                مستخدم_الإدخال INT NOT NULL,
                تاريخ_الإدخال DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (مستخدم_الإدخال) REFERENCES المستخدمين(معرف_المستخدم)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            // جدول سجل الأنشطة
            "CREATE TABLE IF NOT EXISTS سجل_الأنشطة (
                معرف_السجل INT PRIMARY KEY AUTO_INCREMENT,
                معرف_المستخدم INT NOT NULL,
                نوع_النشاط VARCHAR(100) NOT NULL,
                الجدول_المستهدف VARCHAR(50),
                معرف_السجل_المستهدف INT,
                التفاصيل TEXT,
                العنوان VARCHAR(255),
                عنوان_IP VARCHAR(45),
                المتصفح TEXT,
                التاريخ DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (معرف_المستخدم) REFERENCES المستخدمين(معرف_المستخدم)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        ];
        
        foreach ($tables as $table) {
            try {
                $this->pdo->exec($table);
            } catch (PDOException $e) {
                echo "خطأ في إنشاء الجداول: " . $e->getMessage();
            }
        }
    }
    
    private function createDefaultPermissions() {
        $permissions = [
            ['عرض_الأجهزة', 'عرض قائمة الأجهزة والتفاصيل'],
            ['إدخال_الأجهزة', 'إضافة أجهزة جديدة'],
            ['تعديل_الأجهزة', 'تعديل بيانات الأجهزة الموجودة'],
            ['حذف_الأجهزة', 'حذف الأجهزة من النظام'],
            ['صيانة_الأجهزة', 'تحديث حالة صيانة الأجهزة'],
            ['إدارة_المستخدمين', 'إضافة وتعديل وحذف المستخدمين'],
            ['إدارة_الصلاحيات', 'تخصيص الصلاحيات للمستخدمين'],
            ['عرض_سجل_الأنشطة', 'عرض سجل أنشطة المستخدمين'],
            ['تصدير_البيانات', 'تصدير البيانات إلى ملفات Excel'],
            ['النسخ_الاحتياطي', 'إنشاء وإستعادة النسخ الاحتياطية']
        ];
        
        $stmt = $this->pdo->prepare("INSERT IGNORE INTO الصلاحيات (اسم_الصلاحية, وصف_الصلاحية) VALUES (?, ?)");
        foreach ($permissions as $permission) {
            $stmt->execute($permission);
        }
    }
    
    private function createDefaultAdmin() {
        // التحقق من وجود المدير
        $stmt = $this->pdo->prepare("SELECT معرف_المستخدم FROM المستخدمين WHERE اسم_المستخدم = ?");
        $stmt->execute(['admin']);
        
        if ($stmt->rowCount() == 0) {
            // إنشاء حساب المدير
            $password_hash = hash('sha256', 'admin123' . PASSWORD_SALT);
            $stmt = $this->pdo->prepare("
                INSERT INTO المستخدمين (اسم_المستخدم, كلمة_المرور, الاسم_الكامل, الوحدة_العسكرية) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute(['admin', $password_hash, 'مدير النظام', 'الإدارة العامة']);
            
            $admin_id = $this->pdo->lastInsertId();
            
            // منح جميع الصلاحيات للمدير
            $stmt = $this->pdo->prepare("SELECT معرف_الصلاحية FROM الصلاحيات");
            $stmt->execute();
            $permissions = $stmt->fetchAll();
            
            $assign_stmt = $this->pdo->prepare("
                INSERT INTO تخصيص_الصلاحيات (معرف_المستخدم, معرف_الصلاحية, منح_بواسطة) 
                VALUES (?, ?, ?)
            ");
            
            foreach ($permissions as $permission) {
                $assign_stmt->execute([$admin_id, $permission['معرف_الصلاحية'], $admin_id]);
            }
        }
    }
    
    public function logActivity($user_id, $activity_type, $target_table = null, $target_id = null, $details = null) {
        $stmt = $this->pdo->prepare("
            INSERT INTO سجل_الأنشطة (معرف_المستخدم, نوع_النشاط, الجدول_المستهدف, معرف_السجل_المستهدف, التفاصيل, عنوان_IP, المتصفح) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'غير معروف';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'غير معروف';
        
        $stmt->execute([$user_id, $activity_type, $target_table, $target_id, $details, $ip, $user_agent]);
    }
}
?>