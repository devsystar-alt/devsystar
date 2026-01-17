import hashlib
import secrets
from functools import wraps
from flask import session, request, redirect, url_for, flash
from database import Database

def hash_password(password):
    """تشفير كلمة المرور"""
    return hashlib.sha256(password.encode()).hexdigest()

def verify_password(password, hashed):
    """التحقق من كلمة المرور"""
    return hash_password(password) == hashed

def login_user(username, password, ip_address, browser):
    """تسجيل دخول المستخدم"""
    db = Database()
    conn = db.get_connection()
    
    if conn:
        try:
            cursor = conn.execute("""
                SELECT معرف_المستخدم, كلمة_المرور, الاسم_الكامل, مفعل, تاريخ_انتهاء_الحساب
                FROM المستخدمين 
                WHERE اسم_المستخدم = ?
            """, (username,))
            
            user = cursor.fetchone()
            
            if user and user['مفعل'] == 1:
                if verify_password(password, user['كلمة_المرور']):
                    # إنشاء جلسة جديدة
                    session_token = secrets.token_urlsafe(32)
                    session['user_id'] = user['معرف_المستخدم']
                    session['username'] = username
                    session['full_name'] = user['الاسم_الكامل']
                    session['session_token'] = session_token
                    
                    # حفظ الجلسة في قاعدة البيانات
                    from datetime import datetime, timedelta
                    expiry_time = (datetime.now() + timedelta(hours=8)).isoformat()
                    
                    conn.execute("""
                        INSERT INTO جلسات_المستخدمين (معرف_الجلسة, معرف_المستخدم, رمز_الجلسة, عنوان_IP, المتصفح, تاريخ_انتهاء_الصلاحية)
                        VALUES (?, ?, ?, ?, ?, ?)
                    """, (session_token, user['معرف_المستخدم'], session_token, ip_address, browser, expiry_time))
                    
                    # تحديث آخر تسجيل دخول
                    conn.execute("""
                        UPDATE المستخدمين 
                        SET آخر_تسجيل_دخول = CURRENT_TIMESTAMP 
                        WHERE معرف_المستخدم = ?
                    """, (user['معرف_المستخدم'],))
                    
                    conn.commit()
                    
                    # تسجيل النشاط
                    db.log_activity(user['معرف_المستخدم'], "تسجيل دخول", ip_address=ip_address, browser=browser)
                    
                    return True, "تم تسجيل الدخول بنجاح"
                else:
                    return False, "كلمة المرور غير صحيحة"
            else:
                return False, "اسم المستخدم غير موجود أو الحساب غير مفعل"
                
        except Exception as e:
            print(f"خطأ في تسجيل الدخول: {e}")
            return False, "خطأ في النظام"
        finally:
            conn.close()
    
    return False, "خطأ في الاتصال بقاعدة البيانات"

def logout_user():
    """تسجيل خروج المستخدم"""
    if 'user_id' in session:
        db = Database()
        conn = db.get_connection()
        
        if conn:
            try:
                # إلغاء تفعيل الجلسة
                if 'session_token' in session:
                    conn.execute("""
                        UPDATE جلسات_المستخدمين 
                        SET مفعل = 0 
                        WHERE رمز_الجلسة = ?
                    """, (session['session_token'],))
                    conn.commit()
                
                # تسجيل النشاط
                db.log_activity(session['user_id'], "تسجيل خروج")
                
            except Exception as e:
                print(f"خطأ في تسجيل الخروج: {e}")
            finally:
                conn.close()
    
    session.clear()

def login_required(f):
    """ديكوريتر للتحقق من تسجيل الدخول"""
    @wraps(f)
    def decorated_function(*args, **kwargs):
        if 'user_id' not in session:
            flash('يجب تسجيل الدخول للوصول لهذه الصفحة', 'warning')
            return redirect(url_for('login'))
        return f(*args, **kwargs)
    return decorated_function

def permission_required(permission_name):
    """ديكوريتر للتحقق من الصلاحيات"""
    def decorator(f):
        @wraps(f)
        def decorated_function(*args, **kwargs):
            if 'user_id' not in session:
                flash('يجب تسجيل الدخول للوصول لهذه الصفحة', 'warning')
                return redirect(url_for('login'))
            
            if not has_permission(session['user_id'], permission_name):
                flash('ليس لديك صلاحية للوصول لهذه الصفحة', 'danger')
                return redirect(url_for('dashboard'))
            
            return f(*args, **kwargs)
        return decorated_function
    return decorator

def has_permission(user_id, permission_name):
    """التحقق من وجود صلاحية معينة للمستخدم"""
    db = Database()
    conn = db.get_connection()
    
    if conn:
        try:
            cursor = conn.execute("""
                SELECT COUNT(*) FROM تخصيص_الصلاحيات ts
                JOIN الصلاحيات p ON ts.معرف_الصلاحية = p.معرف_الصلاحية
                WHERE ts.معرف_المستخدم = ? AND p.اسم_الصلاحية = ? AND p.مفعل = 1
            """, (user_id, permission_name))
            
            count = cursor.fetchone()[0]
            return count > 0
            
        except Exception as e:
            print(f"خطأ في التحقق من الصلاحية: {e}")
            return False
        finally:
            conn.close()
    
    return False

def get_user_permissions(user_id):
    """الحصول على قائمة صلاحيات المستخدم"""
    db = Database()
    conn = db.get_connection()
    permissions = []
    
    if conn:
        try:
            cursor = conn.execute("""
                SELECT p.اسم_الصلاحية, p.وصف_الصلاحية
                FROM تخصيص_الصلاحيات ts
                JOIN الصلاحيات p ON ts.معرف_الصلاحية = p.معرف_الصلاحية
                WHERE ts.معرف_المستخدم = ? AND p.مفعل = 1
            """, (user_id,))
            
            permissions = [dict(row) for row in cursor.fetchall()]
            
        except Exception as e:
            print(f"خطأ في الحصول على الصلاحيات: {e}")
        finally:
            conn.close()
    
    return permissions
