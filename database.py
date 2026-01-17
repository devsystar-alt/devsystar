import sqlite3
from sqlite3 import Error
import hashlib
from datetime import datetime

class Database:
    def __init__(self, db_name="military_devices.db"):
        self.db_name = db_name
        self.create_tables()
        self.create_default_admin()
        self.create_default_permissions()
    
    def get_connection(self):
        """إنشاء اتصال بقاعدة البيانات"""
        try:
            conn = sqlite3.connect(self.db_name)
            conn.execute("PRAGMA foreign_keys = ON")
            conn.row_factory = sqlite3.Row
            return conn
        except Error as e:
            print(f"خطأ في الاتصال بقاعدة البيانات: {e}")
            return None
    
    def create_tables(self):
        """إنشاء الجداول الأساسية"""
        tables = [
            # جدول الصلاحيات
            """
            CREATE TABLE IF NOT EXISTS الصلاحيات (
                معرف_الصلاحية INTEGER PRIMARY KEY AUTOINCREMENT,
                اسم_الصلاحية TEXT NOT NULL UNIQUE,
                وصف_الصلاحية TEXT,
                مفعل INTEGER DEFAULT 1
            )
            """,
            
            # جدول المستخدمين
            """
            CREATE TABLE IF NOT EXISTS المستخدمين (
                معرف_المستخدم INTEGER PRIMARY KEY AUTOINCREMENT,
                اسم_المستخدم TEXT NOT NULL UNIQUE,
                كلمة_المرور TEXT NOT NULL,
                الاسم_الكامل TEXT NOT NULL,
                البريد_الإلكتروني TEXT UNIQUE,
                رقم_الهاتف TEXT,
                الوحدة_العسكرية TEXT,
                مفعل INTEGER DEFAULT 1,
                آخر_تسجيل_دخول TEXT,
                تاريخ_انتهاء_الحساب TEXT,
                تاريخ_التسجيل TEXT DEFAULT CURRENT_TIMESTAMP,
                ملاحظات TEXT
            )
            """,
            
            # جدول تخصيص الصلاحيات
            """
            CREATE TABLE IF NOT EXISTS تخصيص_الصلاحيات (
                معرف_التخصيص INTEGER PRIMARY KEY AUTOINCREMENT,
                معرف_المستخدم INTEGER NOT NULL,
                معرف_الصلاحية INTEGER NOT NULL,
                تاريخ_المنح TEXT DEFAULT CURRENT_TIMESTAMP,
                منح_بواسطة INTEGER,
                FOREIGN KEY (معرف_المستخدم) REFERENCES المستخدمين(معرف_المستخدم) ON DELETE CASCADE,
                FOREIGN KEY (معرف_الصلاحية) REFERENCES الصلاحيات(معرف_الصلاحية) ON DELETE CASCADE,
                FOREIGN KEY (منح_بواسطة) REFERENCES المستخدمين(معرف_المستخدم),
                UNIQUE (معرف_المستخدم, معرف_الصلاحية)
            )
            """,
            
            # جدول الأجهزة
            """
            CREATE TABLE IF NOT EXISTS الأجهزة (
                معرف_فريد INTEGER PRIMARY KEY AUTOINCREMENT,
                رقم_الجهاز TEXT NOT NULL UNIQUE,
                نوع_الجهاز TEXT NOT NULL,
                حالة_الجهاز TEXT CHECK (حالة_الجهاز IN ('جيد', 'معطل', 'صيانة', 'مفقود')),
                الشهر TEXT,
                F TEXT NOT NULL,
                S TEXT NOT NULL,
                K TEXT NOT NULL,
                هوائي INTEGER DEFAULT 0,
                شاحن INTEGER DEFAULT 0,
                قاعدة_شحن INTEGER DEFAULT 0,
                بطارية INTEGER DEFAULT 0,
                كبل INTEGER DEFAULT 0,
                سماعة INTEGER DEFAULT 0,
                اسم_الجهاز TEXT,
                اسم_المستلم TEXT NOT NULL,
                تاريخ_الاستلام TEXT NOT NULL,
                الجهة_المستلمة TEXT NOT NULL,
                ملاحظات TEXT,
                الاحتياجات TEXT,
                أخرى TEXT,
                تاريخ_الصيانة TEXT,
                مستخدم_الإدخال INTEGER NOT NULL,
                تاريخ_الإدخال TEXT DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (مستخدم_الإدخال) REFERENCES المستخدمين(معرف_المستخدم)
            )
            """,
            
            # جدول سجل الأنشطة
            """
            CREATE TABLE IF NOT EXISTS سجل_الأنشطة (
                معرف_السجل INTEGER PRIMARY KEY AUTOINCREMENT,
                معرف_المستخدم INTEGER NOT NULL,
                نوع_النشاط TEXT NOT NULL,
                الجدول_المستهدف TEXT,
                معرف_السجل_المستهدف INTEGER,
                التفاصيل TEXT,
                العنوان TEXT,
                عنوان_IP TEXT,
                المتصفح TEXT,
                التاريخ TEXT DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (معرف_المستخدم) REFERENCES المستخدمين(معرف_المستخدم)
            )
            """,
            
            # جدول جلسات المستخدمين
            """
            CREATE TABLE IF NOT EXISTS جلسات_المستخدمين (
                معرف_الجلسة TEXT PRIMARY KEY,
                معرف_المستخدم INTEGER NOT NULL,
                رمز_الجلسة TEXT NOT NULL,
                عنوان_IP TEXT NOT NULL,
                المتصفح TEXT,
                تاريخ_الإنشاء TEXT DEFAULT CURRENT_TIMESTAMP,
                تاريخ_انتهاء_الصلاحية TEXT NOT NULL,
                مفعل INTEGER DEFAULT 1,
                FOREIGN KEY (معرف_المستخدم) REFERENCES المستخدمين(معرف_المستخدم)
            )
            """
        ]
        
        conn = self.get_connection()
        if conn:
            try:
                for table in tables:
                    conn.execute(table)
                conn.commit()
                print("تم إنشاء الجداول بنجاح")
            except Error as e:
                print(f"خطأ في إنشاء الجداول: {e}")
            finally:
                conn.close()
    
    def create_default_admin(self):
        """إنشاء مستخدم المدير الافتراضي"""
        conn = self.get_connection()
        if conn:
            try:
                # التحقق من وجود مستخدم المدير
                cursor = conn.execute("SELECT COUNT(*) FROM المستخدمين WHERE اسم_المستخدم = 'admin'")
                if cursor.fetchone()[0] == 0:
                    # تشفير كلمة المرور
                    password_hash = hashlib.sha256("admin123".encode()).hexdigest()
                    
                    conn.execute("""
                        INSERT INTO المستخدمين (اسم_المستخدم, كلمة_المرور, الاسم_الكامل, الوحدة_العسكرية)
                        VALUES (?, ?, ?, ?)
                    """, ("admin", password_hash, "مدير النظام", "إدارة النظام"))
                    conn.commit()
                    print("تم إنشاء مستخدم المدير الافتراضي")
            except Error as e:
                print(f"خطأ في إنشاء مستخدم المدير: {e}")
            finally:
                conn.close()
    
    def create_default_permissions(self):
        """إنشاء الصلاحيات الافتراضية"""
        permissions = [
            ("إدارة_المستخدمين", "صلاحية إدارة المستخدمين وحساباتهم"),
            ("إدارة_الصلاحيات", "صلاحية تعديل صلاحيات المستخدمين"),
            ("إدارة_الأجهزة", "صلاحية إضافة وتعديل وحذف الأجهزة"),
            ("عرض_الأجهزة", "صلاحية عرض قائمة الأجهزة"),
            ("إضافة_جهاز", "صلاحية إضافة جهاز جديد"),
            ("تعديل_حالة_جهاز", "صلاحية تغيير حالة الجهاز للصيانة"),
            ("فلترة_البيانات", "صلاحية فلترة وبحث البيانات"),
            ("عرض_التقارير", "صلاحية عرض التقارير والإحصائيات"),
            ("عرض_سجل_الأنشطة", "صلاحية عرض سجل أنشطة النظام"),
            ("إدارة_النظام", "صلاحية إدارة النظام وتصدير البيانات"),
            ("تصدير_البيانات", "صلاحية تصدير البيانات إلى ملفات Excel")
        ]
        
        conn = self.get_connection()
        if conn:
            try:
                for permission in permissions:
                    conn.execute("""
                        INSERT OR IGNORE INTO الصلاحيات (اسم_الصلاحية, وصف_الصلاحية)
                        VALUES (?, ?)
                    """, permission)
                
                # منح جميع الصلاحيات للمدير
                admin_cursor = conn.execute("SELECT معرف_المستخدم FROM المستخدمين WHERE اسم_المستخدم = 'admin'")
                admin_row = admin_cursor.fetchone()
                if admin_row:
                    admin_id = admin_row[0]
                    permission_cursor = conn.execute("SELECT معرف_الصلاحية FROM الصلاحيات")
                    for perm_row in permission_cursor.fetchall():
                        conn.execute("""
                            INSERT OR IGNORE INTO تخصيص_الصلاحيات (معرف_المستخدم, معرف_الصلاحية, منح_بواسطة)
                            VALUES (?, ?, ?)
                        """, (admin_id, perm_row[0], admin_id))
                
                conn.commit()
                print("تم إنشاء الصلاحيات الافتراضية")
            except Error as e:
                print(f"خطأ في إنشاء الصلاحيات: {e}")
            finally:
                conn.close()
    
    def create_limited_user_permissions(self, user_id):
        """منح صلاحيات محدودة للمستخدم العادي (عرض، إدخال، صيانة، فلترة فقط - بدون إدارة مستخدمين أو سجل أنشطة)"""
        limited_permissions = [
            "عرض_الأجهزة",
            "إضافة_جهاز", 
            "تعديل_حالة_جهاز",
            "فلترة_البيانات",
            "تصدير_البيانات"  # للمستخدمين العاديين فقط تصدير الأجهزة، وليس قاعدة البيانات
        ]
        
        conn = self.get_connection()
        if conn:
            try:
                # حذف الصلاحيات الحالية للمستخدم
                conn.execute("DELETE FROM تخصيص_الصلاحيات WHERE معرف_المستخدم = ?", (user_id,))
                
                # منح الصلاحيات المحدودة فقط
                for perm_name in limited_permissions:
                    perm_cursor = conn.execute("SELECT معرف_الصلاحية FROM الصلاحيات WHERE اسم_الصلاحية = ?", (perm_name,))
                    perm_row = perm_cursor.fetchone()
                    if perm_row:
                        conn.execute("""
                            INSERT OR IGNORE INTO تخصيص_الصلاحيات (معرف_المستخدم, معرف_الصلاحية, منح_بواسطة)
                            VALUES (?, ?, ?)
                        """, (user_id, perm_row[0], 1))  # 1 = معرف المدير
                
                conn.commit()
                return True
            except Error as e:
                print(f"خطأ في منح الصلاحيات المحدودة: {e}")
                return False
            finally:
                conn.close()
        return False
    
    def log_activity(self, user_id, activity_type, table_name=None, record_id=None, details=None, ip_address=None, browser=None):
        """تسجيل نشاط في سجل الأنشطة"""
        conn = self.get_connection()
        if conn:
            try:
                conn.execute("""
                    INSERT INTO سجل_الأنشطة (معرف_المستخدم, نوع_النشاط, الجدول_المستهدف, معرف_السجل_المستهدف, التفاصيل, عنوان_IP, المتصفح)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                """, (user_id, activity_type, table_name, record_id, details, ip_address, browser))
                conn.commit()
            except Error as e:
                print(f"خطأ في تسجيل النشاط: {e}")
            finally:
                conn.close()
