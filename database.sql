-- ملف SQL لإنشاء قاعدة البيانات والجداول
-- يمكن استيراده مباشرة في phpMyAdmin أو MySQL

CREATE DATABASE IF NOT EXISTS military_devices CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE military_devices;

-- جدول الصلاحيات
CREATE TABLE IF NOT EXISTS الصلاحيات (
    معرف_الصلاحية INT PRIMARY KEY AUTO_INCREMENT,
    اسم_الصلاحية VARCHAR(100) NOT NULL UNIQUE,
    وصف_الصلاحية TEXT,
    تاريخ_الإنشاء DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول المستخدمين
CREATE TABLE IF NOT EXISTS المستخدمين (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول تخصيص الصلاحيات
CREATE TABLE IF NOT EXISTS تخصيص_الصلاحيات (
    معرف_التخصيص INT PRIMARY KEY AUTO_INCREMENT,
    معرف_المستخدم INT NOT NULL,
    معرف_الصلاحية INT NOT NULL,
    تاريخ_المنح DATETIME DEFAULT CURRENT_TIMESTAMP,
    منح_بواسطة INT,
    FOREIGN KEY (معرف_المستخدم) REFERENCES المستخدمين(معرف_المستخدم) ON DELETE CASCADE,
    FOREIGN KEY (معرف_الصلاحية) REFERENCES الصلاحيات(معرف_الصلاحية) ON DELETE CASCADE,
    FOREIGN KEY (منح_بواسطة) REFERENCES المستخدمين(معرف_المستخدم),
    UNIQUE KEY unique_user_permission (معرف_المستخدم, معرف_الصلاحية)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول الأجهزة
CREATE TABLE IF NOT EXISTS الأجهزة (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول سجل الأنشطة
CREATE TABLE IF NOT EXISTS سجل_الأنشطة (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إدراج الصلاحيات الافتراضية
INSERT IGNORE INTO الصلاحيات (اسم_الصلاحية, وصف_الصلاحية) VALUES
('عرض_الأجهزة', 'عرض قائمة الأجهزة والتفاصيل'),
('إدخال_الأجهزة', 'إضافة أجهزة جديدة'),
('تعديل_الأجهزة', 'تعديل بيانات الأجهزة الموجودة'),
('حذف_الأجهزة', 'حذف الأجهزة من النظام'),
('صيانة_الأجهزة', 'تحديث حالة صيانة الأجهزة'),
('إدارة_المستخدمين', 'إضافة وتعديل وحذف المستخدمين'),
('إدارة_الصلاحيات', 'تخصيص الصلاحيات للمستخدمين'),
('عرض_سجل_الأنشطة', 'عرض سجل أنشطة المستخدمين'),
('تصدير_البيانات', 'تصدير البيانات إلى ملفات Excel'),
('النسخ_الاحتياطي', 'إنشاء وإستعادة النسخ الاحتياطية');

-- إنشاء المستخدم المدير الافتراضي
-- كلمة المرور: admin123 (مشفرة بـ SHA-256)
INSERT IGNORE INTO المستخدمين (اسم_المستخدم, كلمة_المرور, الاسم_الكامل, الوحدة_العسكرية) 
VALUES ('admin', SHA2(CONCAT('admin123', 'military_system_2024'), 256), 'مدير النظام', 'الإدارة العامة');

-- منح جميع الصلاحيات للمدير
INSERT IGNORE INTO تخصيص_الصلاحيات (معرف_المستخدم, معرف_الصلاحية, منح_بواسطة)
SELECT 
    (SELECT معرف_المستخدم FROM المستخدمين WHERE اسم_المستخدم = 'admin') as معرف_المستخدم,
    معرف_الصلاحية,
    (SELECT معرف_المستخدم FROM المستخدمين WHERE اسم_المستخدم = 'admin') as منح_بواسطة
FROM الصلاحيات;