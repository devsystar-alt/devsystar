from flask import render_template, request, redirect, url_for, flash, session, jsonify, send_file, make_response
from app import app
from auth import login_user, logout_user, login_required, permission_required, has_permission, get_user_permissions
from database import Database
from datetime import datetime
import sqlite3
import pandas as pd
import io
import os
import zipfile

@app.route('/')
def index():
    """الصفحة الرئيسية"""
    if 'user_id' in session:
        return redirect(url_for('dashboard'))
    return redirect(url_for('login'))

@app.route('/login', methods=['GET', 'POST'])
def login():
    """صفحة تسجيل الدخول"""
    if 'user_id' in session:
        return redirect(url_for('dashboard'))
    
    if request.method == 'POST':
        username = request.form.get('username')
        password = request.form.get('password')
        
        if username and password:
            ip_address = request.environ.get('REMOTE_ADDR', 'unknown')
            browser = request.headers.get('User-Agent', 'unknown')
            
            success, message = login_user(username, password, ip_address, browser)
            
            if success:
                flash(message, 'success')
                return redirect(url_for('dashboard'))
            else:
                flash(message, 'danger')
        else:
            flash('يرجى إدخال اسم المستخدم وكلمة المرور', 'warning')
    
    return render_template('login.html')

@app.route('/logout')
@login_required
def logout():
    """تسجيل الخروج"""
    logout_user()
    flash('تم تسجيل الخروج بنجاح', 'info')
    return redirect(url_for('login'))

@app.route('/dashboard')
@login_required
def dashboard():
    """لوحة التحكم الرئيسية"""
    db = Database()
    conn = db.get_connection()
    
    stats = {
        'total_devices': 0,
        'active_devices': 0,
        'maintenance_devices': 0,
        'broken_devices': 0,
        'total_users': 0,
        'recent_activities': []
    }
    
    if conn:
        try:
            # إحصائيات الأجهزة
            cursor = conn.execute("SELECT COUNT(*) FROM الأجهزة")
            stats['total_devices'] = cursor.fetchone()[0]
            
            cursor = conn.execute("SELECT COUNT(*) FROM الأجهزة WHERE حالة_الجهاز = 'جيد'")
            stats['active_devices'] = cursor.fetchone()[0]
            
            cursor = conn.execute("SELECT COUNT(*) FROM الأجهزة WHERE حالة_الجهاز = 'صيانة'")
            stats['maintenance_devices'] = cursor.fetchone()[0]
            
            cursor = conn.execute("SELECT COUNT(*) FROM الأجهزة WHERE حالة_الجهاز = 'معطل'")
            stats['broken_devices'] = cursor.fetchone()[0]
            
            # إحصائيات المستخدمين
            cursor = conn.execute("SELECT COUNT(*) FROM المستخدمين WHERE مفعل = 1")
            stats['total_users'] = cursor.fetchone()[0]
            
            # الأنشطة الحديثة
            cursor = conn.execute("""
                SELECT sa.نوع_النشاط, sa.التفاصيل, sa.التاريخ, u.الاسم_الكامل
                FROM سجل_الأنشطة sa
                JOIN المستخدمين u ON sa.معرف_المستخدم = u.معرف_المستخدم
                ORDER BY sa.التاريخ DESC
                LIMIT 10
            """)
            stats['recent_activities'] = [dict(row) for row in cursor.fetchall()]
            
        except Exception as e:
            print(f"خطأ في الحصول على الإحصائيات: {e}")
        finally:
            conn.close()
    
    user_permissions = get_user_permissions(session['user_id'])
    return render_template('dashboard.html', stats=stats, permissions=user_permissions)

@app.route('/devices')
@login_required
@permission_required('عرض_الأجهزة')
def devices():
    """صفحة عرض الأجهزة"""
    db = Database()
    conn = db.get_connection()
    devices = []
    
    search = request.args.get('search', '')
    status_filter = request.args.get('status', '')
    
    if conn:
        try:
            query = """
                SELECT d.*, u.الاسم_الكامل as مدخل_البيانات
                FROM الأجهزة d
                JOIN المستخدمين u ON d.مستخدم_الإدخال = u.معرف_المستخدم
                WHERE 1=1
            """
            params = []
            
            if search:
                query += " AND (d.رقم_الجهاز LIKE ? OR d.نوع_الجهاز LIKE ? OR d.اسم_المستلم LIKE ?)"
                search_param = f"%{search}%"
                params.extend([search_param, search_param, search_param])
            
            if status_filter:
                query += " AND d.حالة_الجهاز = ?"
                params.append(status_filter)
            
            query += " ORDER BY d.تاريخ_الإدخال DESC"
            
            cursor = conn.execute(query, params)
            devices = [dict(row) for row in cursor.fetchall()]
            
        except Exception as e:
            print(f"خطأ في الحصول على الأجهزة: {e}")
            flash('حدث خطأ في تحميل البيانات', 'danger')
        finally:
            conn.close()
    
    user_permissions = get_user_permissions(session['user_id'])
    return render_template('devices.html', devices=devices, search=search, status_filter=status_filter, permissions=user_permissions)

@app.route('/device/add', methods=['GET', 'POST'])
@login_required
@permission_required('إدارة_الأجهزة')
def add_device():
    """إضافة جهاز جديد"""
    if request.method == 'POST':
        db = Database()
        conn = db.get_connection()
        
        if conn:
            try:
                device_data = {
                    'رقم_الجهاز': request.form.get('device_number'),
                    'نوع_الجهاز': request.form.get('device_type'),
                    'حالة_الجهاز': request.form.get('device_status'),
                    'الشهر': request.form.get('month'),
                    'F': request.form.get('f_value'),
                    'S': request.form.get('s_value'),
                    'K': request.form.get('k_value'),
                    'هوائي': 1 if request.form.get('antenna') else 0,
                    'شاحن': 1 if request.form.get('charger') else 0,
                    'قاعدة_شحن': 1 if request.form.get('charging_base') else 0,
                    'بطارية': 1 if request.form.get('battery') else 0,
                    'كبل': 1 if request.form.get('cable') else 0,
                    'سماعة': 1 if request.form.get('headset') else 0,
                    'اسم_الجهاز': request.form.get('device_name'),
                    'اسم_المستلم': request.form.get('recipient_name'),
                    'تاريخ_الاستلام': request.form.get('receipt_date'),
                    'الجهة_المستلمة': request.form.get('receiving_entity'),
                    'ملاحظات': request.form.get('notes'),
                    'الاحتياجات': request.form.get('requirements'),
                    'أخرى': request.form.get('others'),
                    'تاريخ_الصيانة': request.form.get('maintenance_date'),
                    'مستخدم_الإدخال': session['user_id']
                }
                
                conn.execute("""
                    INSERT INTO الأجهزة (
                        رقم_الجهاز, نوع_الجهاز, حالة_الجهاز, الشهر, F, S, K,
                        هوائي, شاحن, قاعدة_شحن, بطارية, كبل, سماعة,
                        اسم_الجهاز, اسم_المستلم, تاريخ_الاستلام, الجهة_المستلمة,
                        ملاحظات, الاحتياجات, أخرى, تاريخ_الصيانة, مستخدم_الإدخال
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                """, tuple(device_data.values()))
                
                conn.commit()
                
                # تسجيل النشاط
                db.log_activity(
                    session['user_id'], 
                    "إضافة جهاز", 
                    "الأجهزة", 
                    details=f"تم إضافة الجهاز رقم {device_data['رقم_الجهاز']}"
                )
                
                flash('تم إضافة الجهاز بنجاح', 'success')
                return redirect(url_for('devices'))
                
            except sqlite3.IntegrityError:
                flash('رقم الجهاز موجود مسبقاً', 'danger')
            except Exception as e:
                print(f"خطأ في إضافة الجهاز: {e}")
                flash('حدث خطأ في إضافة الجهاز', 'danger')
            finally:
                conn.close()
    
    return render_template('device_form.html', action='add')

@app.route('/device/edit/<int:device_id>', methods=['GET', 'POST'])
@login_required
@permission_required('إدارة_الأجهزة')
def edit_device(device_id):
    """تعديل جهاز"""
    db = Database()
    conn = db.get_connection()
    device = None
    
    if conn:
        try:
            cursor = conn.execute("SELECT * FROM الأجهزة WHERE معرف_فريد = ?", (device_id,))
            device = cursor.fetchone()
            
            if not device:
                flash('الجهاز غير موجود', 'danger')
                return redirect(url_for('devices'))
            
            device = dict(device)
            
            if request.method == 'POST':
                device_data = {
                    'رقم_الجهاز': request.form.get('device_number'),
                    'نوع_الجهاز': request.form.get('device_type'),
                    'حالة_الجهاز': request.form.get('device_status'),
                    'الشهر': request.form.get('month'),
                    'F': request.form.get('f_value'),
                    'S': request.form.get('s_value'),
                    'K': request.form.get('k_value'),
                    'هوائي': 1 if request.form.get('antenna') else 0,
                    'شاحن': 1 if request.form.get('charger') else 0,
                    'قاعدة_شحن': 1 if request.form.get('charging_base') else 0,
                    'بطارية': 1 if request.form.get('battery') else 0,
                    'كبل': 1 if request.form.get('cable') else 0,
                    'سماعة': 1 if request.form.get('headset') else 0,
                    'اسم_الجهاز': request.form.get('device_name'),
                    'اسم_المستلم': request.form.get('recipient_name'),
                    'تاريخ_الاستلام': request.form.get('receipt_date'),
                    'الجهة_المستلمة': request.form.get('receiving_entity'),
                    'ملاحظات': request.form.get('notes'),
                    'الاحتياجات': request.form.get('requirements'),
                    'أخرى': request.form.get('others'),
                    'تاريخ_الصيانة': request.form.get('maintenance_date')
                }
                
                conn.execute("""
                    UPDATE الأجهزة SET
                        رقم_الجهاز = ?, نوع_الجهاز = ?, حالة_الجهاز = ?, الشهر = ?, F = ?, S = ?, K = ?,
                        هوائي = ?, شاحن = ?, قاعدة_شحن = ?, بطارية = ?, كبل = ?, سماعة = ?,
                        اسم_الجهاز = ?, اسم_المستلم = ?, تاريخ_الاستلام = ?, الجهة_المستلمة = ?,
                        ملاحظات = ?, الاحتياجات = ?, أخرى = ?, تاريخ_الصيانة = ?
                    WHERE معرف_فريد = ?
                """, tuple(device_data.values()) + (device_id,))
                
                conn.commit()
                
                # تسجيل النشاط
                db.log_activity(
                    session['user_id'], 
                    "تعديل جهاز", 
                    "الأجهزة", 
                    device_id,
                    details=f"تم تعديل الجهاز رقم {device_data['رقم_الجهاز']}"
                )
                
                flash('تم تحديث الجهاز بنجاح', 'success')
                return redirect(url_for('devices'))
                
        except sqlite3.IntegrityError:
            flash('رقم الجهاز موجود مسبقاً', 'danger')
        except Exception as e:
            print(f"خطأ في تعديل الجهاز: {e}")
            flash('حدث خطأ في تعديل الجهاز', 'danger')
        finally:
            conn.close()
    
    return render_template('device_form.html', action='edit', device=device)

@app.route('/device/delete/<int:device_id>')
@login_required
@permission_required('إدارة_الأجهزة')
def delete_device(device_id):
    """حذف جهاز"""
    db = Database()
    conn = db.get_connection()
    
    if conn:
        try:
            # الحصول على معلومات الجهاز قبل الحذف
            cursor = conn.execute("SELECT رقم_الجهاز FROM الأجهزة WHERE معرف_فريد = ?", (device_id,))
            device = cursor.fetchone()
            
            if device:
                conn.execute("DELETE FROM الأجهزة WHERE معرف_فريد = ?", (device_id,))
                conn.commit()
                
                # تسجيل النشاط
                db.log_activity(
                    session['user_id'], 
                    "حذف جهاز", 
                    "الأجهزة", 
                    device_id,
                    details=f"تم حذف الجهاز رقم {device['رقم_الجهاز']}"
                )
                
                flash('تم حذف الجهاز بنجاح', 'success')
            else:
                flash('الجهاز غير موجود', 'danger')
                
        except Exception as e:
            print(f"خطأ في حذف الجهاز: {e}")
            flash('حدث خطأ في حذف الجهاز', 'danger')
        finally:
            conn.close()
    
    return redirect(url_for('devices'))

@app.route('/users')
@login_required
@permission_required('إدارة_المستخدمين')
def users():
    """صفحة إدارة المستخدمين"""
    db = Database()
    conn = db.get_connection()
    users = []
    
    if conn:
        try:
            cursor = conn.execute("""
                SELECT معرف_المستخدم, اسم_المستخدم, الاسم_الكامل, البريد_الإلكتروني, 
                       الوحدة_العسكرية, مفعل, آخر_تسجيل_دخول, تاريخ_التسجيل
                FROM المستخدمين
                ORDER BY تاريخ_التسجيل DESC
            """)
            users = [dict(row) for row in cursor.fetchall()]
            
        except Exception as e:
            print(f"خطأ في الحصول على المستخدمين: {e}")
            flash('حدث خطأ في تحميل البيانات', 'danger')
        finally:
            conn.close()
    
    return render_template('users.html', users=users)

@app.route('/user/add', methods=['GET', 'POST'])
@login_required
@permission_required('إدارة_المستخدمين')
def add_user():
    """إضافة مستخدم جديد"""
    if request.method == 'POST':
        from auth import hash_password
        
        db = Database()
        conn = db.get_connection()
        
        if conn:
            try:
                user_data = {
                    'اسم_المستخدم': request.form.get('username'),
                    'كلمة_المرور': hash_password(request.form.get('password')),
                    'الاسم_الكامل': request.form.get('full_name'),
                    'البريد_الإلكتروني': request.form.get('email'),
                    'رقم_الهاتف': request.form.get('phone'),
                    'الوحدة_العسكرية': request.form.get('military_unit'),
                    'تاريخ_انتهاء_الحساب': request.form.get('expiry_date'),
                    'ملاحظات': request.form.get('notes')
                }
                
                conn.execute("""
                    INSERT INTO المستخدمين (
                        اسم_المستخدم, كلمة_المرور, الاسم_الكامل, البريد_الإلكتروني,
                        رقم_الهاتف, الوحدة_العسكرية, تاريخ_انتهاء_الحساب, ملاحظات
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                """, tuple(user_data.values()))
                
                conn.commit()
                
                # الحصول على معرف المستخدم الجديد
                new_user_cursor = conn.execute("SELECT معرف_المستخدم FROM المستخدمين WHERE اسم_المستخدم = ?", (user_data['اسم_المستخدم'],))
                new_user_row = new_user_cursor.fetchone()
                
                if new_user_row:
                    new_user_id = new_user_row[0]
                    # منح الصلاحيات المحدودة للمستخدم الجديد (ما عدا المدير)
                    if user_data['اسم_المستخدم'] != 'admin':
                        db.create_limited_user_permissions(new_user_id)
                
                # تسجيل النشاط
                db.log_activity(
                    session['user_id'], 
                    "إضافة مستخدم", 
                    "المستخدمين", 
                    details=f"تم إضافة المستخدم {user_data['اسم_المستخدم']} مع صلاحيات محدودة"
                )
                
                flash('تم إضافة المستخدم بنجاح مع الصلاحيات المحدودة', 'success')
                return redirect(url_for('users'))
                
            except sqlite3.IntegrityError:
                flash('اسم المستخدم أو البريد الإلكتروني موجود مسبقاً', 'danger')
            except Exception as e:
                print(f"خطأ في إضافة المستخدم: {e}")
                flash('حدث خطأ في إضافة المستخدم', 'danger')
            finally:
                conn.close()
    
    return render_template('user_form.html', action='add')

@app.route('/user/edit/<int:user_id>', methods=['GET', 'POST'])
@login_required
@permission_required('إدارة_المستخدمين')
def edit_user(user_id):
    """تعديل مستخدم"""
    db = Database()
    conn = db.get_connection()
    user = None
    
    if conn:
        try:
            cursor = conn.execute("SELECT * FROM المستخدمين WHERE معرف_المستخدم = ?", (user_id,))
            user = cursor.fetchone()
            
            if not user:
                flash('المستخدم غير موجود', 'danger')
                return redirect(url_for('users'))
            
            user = dict(user)
            
            if request.method == 'POST':
                from auth import hash_password
                
                user_data = {
                    'الاسم_الكامل': request.form.get('full_name'),
                    'البريد_الإلكتروني': request.form.get('email'),
                    'رقم_الهاتف': request.form.get('phone'),
                    'الوحدة_العسكرية': request.form.get('military_unit'),
                    'تاريخ_انتهاء_الحساب': request.form.get('expiry_date'),
                    'ملاحظات': request.form.get('notes'),
                    'مفعل': 1 if request.form.get('active') else 0
                }
                
                # تحديث كلمة المرور إذا تم إدخال واحدة جديدة
                password = request.form.get('password')
                if password:
                    user_data['كلمة_المرور'] = hash_password(password)
                    
                    conn.execute("""
                        UPDATE المستخدمين SET
                            الاسم_الكامل = ?, البريد_الإلكتروني = ?, رقم_الهاتف = ?, 
                            الوحدة_العسكرية = ?, تاريخ_انتهاء_الحساب = ?, ملاحظات = ?, 
                            مفعل = ?, كلمة_المرور = ?
                        WHERE معرف_المستخدم = ?
                    """, (user_data['الاسم_الكامل'], user_data['البريد_الإلكتروني'], 
                          user_data['رقم_الهاتف'], user_data['الوحدة_العسكرية'], 
                          user_data['تاريخ_انتهاء_الحساب'], user_data['ملاحظات'], 
                          user_data['مفعل'], user_data['كلمة_المرور'], user_id))
                else:
                    conn.execute("""
                        UPDATE المستخدمين SET
                            الاسم_الكامل = ?, البريد_الإلكتروني = ?, رقم_الهاتف = ?, 
                            الوحدة_العسكرية = ?, تاريخ_انتهاء_الحساب = ?, ملاحظات = ?, مفعل = ?
                        WHERE معرف_المستخدم = ?
                    """, (user_data['الاسم_الكامل'], user_data['البريد_الإلكتروني'], 
                          user_data['رقم_الهاتف'], user_data['الوحدة_العسكرية'], 
                          user_data['تاريخ_انتهاء_الحساب'], user_data['ملاحظات'], 
                          user_data['مفعل'], user_id))
                
                conn.commit()
                
                # تسجيل النشاط
                db.log_activity(
                    session['user_id'], 
                    "تعديل مستخدم", 
                    "المستخدمين", 
                    user_id,
                    details=f"تم تعديل المستخدم {user['اسم_المستخدم']}"
                )
                
                flash('تم تحديث المستخدم بنجاح', 'success')
                return redirect(url_for('users'))
                
        except sqlite3.IntegrityError:
            flash('البريد الإلكتروني موجود مسبقاً', 'danger')
        except Exception as e:
            print(f"خطأ في تعديل المستخدم: {e}")
            flash('حدث خطأ في تعديل المستخدم', 'danger')
        finally:
            conn.close()
    
    return render_template('user_form.html', action='edit', user=user)

@app.route('/user/toggle/<int:user_id>')
@login_required
@permission_required('إدارة_المستخدمين')
def toggle_user(user_id):
    """تفعيل/إلغاء تفعيل مستخدم"""
    db = Database()
    conn = db.get_connection()
    
    if conn:
        try:
            # منع إلغاء تفعيل حساب المدير
            cursor = conn.execute("SELECT اسم_المستخدم, مفعل FROM المستخدمين WHERE معرف_المستخدم = ?", (user_id,))
            user = cursor.fetchone()
            
            if user and user['اسم_المستخدم'] == 'admin':
                flash('لا يمكن إلغاء تفعيل حساب المدير', 'warning')
                return redirect(url_for('users'))
            
            if user:
                new_status = 0 if user['مفعل'] == 1 else 1
                conn.execute("UPDATE المستخدمين SET مفعل = ? WHERE معرف_المستخدم = ?", (new_status, user_id))
                conn.commit()
                
                # تسجيل النشاط
                action = "تفعيل مستخدم" if new_status == 1 else "إلغاء تفعيل مستخدم"
                db.log_activity(
                    session['user_id'], 
                    action, 
                    "المستخدمين", 
                    user_id,
                    details=f"تم {action} {user['اسم_المستخدم']}"
                )
                
                status_text = "تفعيل" if new_status == 1 else "إلغاء تفعيل"
                flash(f'تم {status_text} المستخدم بنجاح', 'success')
            else:
                flash('المستخدم غير موجود', 'danger')
                
        except Exception as e:
            print(f"خطأ في تغيير حالة المستخدم: {e}")
            flash('حدث خطأ في تغيير حالة المستخدم', 'danger')
        finally:
            conn.close()
    
    return redirect(url_for('users'))

@app.route('/apply_limited_permissions/<int:user_id>')
@login_required
@permission_required('إدارة_الصلاحيات')
def apply_limited_permissions(user_id):
    """تطبيق الصلاحيات المحدودة على مستخدم"""
    db = Database()
    conn = db.get_connection()
    
    if conn:
        try:
            # التحقق من أن المستخدم ليس المدير
            cursor = conn.execute("SELECT اسم_المستخدم FROM المستخدمين WHERE معرف_المستخدم = ?", (user_id,))
            user = cursor.fetchone()
            
            if user and user['اسم_المستخدم'] == 'admin':
                flash('لا يمكن تطبيق صلاحيات محدودة على المدير', 'warning')
                return redirect(url_for('permissions'))
            
            if user:
                success = db.create_limited_user_permissions(user_id)
                if success:
                    flash(f'تم تطبيق الصلاحيات المحدودة على المستخدم {user["اسم_المستخدم"]} (عرض، إدخال، صيانة، فلترة فقط)', 'success')
                    
                    # تسجيل النشاط
                    db.log_activity(
                        session['user_id'], 
                        "تطبيق صلاحيات محدودة", 
                        "تخصيص_الصلاحيات", 
                        user_id,
                        details=f"تم تطبيق الصلاحيات المحدودة على {user['اسم_المستخدم']}"
                    )
                else:
                    flash('حدث خطأ في تطبيق الصلاحيات المحدودة', 'danger')
            else:
                flash('المستخدم غير موجود', 'danger')
                
        except Exception as e:
            print(f"خطأ في تطبيق الصلاحيات المحدودة: {e}")
            flash('حدث خطأ في تطبيق الصلاحيات المحدودة', 'danger')
        finally:
            conn.close()
    
    return redirect(url_for('permissions'))

@app.route('/apply_limited_to_all')
@login_required
@permission_required('إدارة_الصلاحيات')
def apply_limited_to_all():
    """تطبيق الصلاحيات المحدودة على جميع المستخدمين (ما عدا المدير)"""
    db = Database()
    conn = db.get_connection()
    updated_count = 0
    
    if conn:
        try:
            # الحصول على جميع المستخدمين ما عدا المدير
            cursor = conn.execute("SELECT معرف_المستخدم, اسم_المستخدم FROM المستخدمين WHERE اسم_المستخدم != 'admin' AND مفعل = 1")
            users = cursor.fetchall()
            
            for user in users:
                success = db.create_limited_user_permissions(user['معرف_المستخدم'])
                if success:
                    updated_count += 1
                    
                    # تسجيل النشاط
                    db.log_activity(
                        session['user_id'], 
                        "تطبيق صلاحيات محدودة شامل", 
                        "تخصيص_الصلاحيات", 
                        user['معرف_المستخدم'],
                        details=f"تم تطبيق الصلاحيات المحدودة على {user['اسم_المستخدم']}"
                    )
            
            if updated_count > 0:
                flash(f'تم تطبيق الصلاحيات المحدودة على {updated_count} مستخدم بنجاح', 'success')
            else:
                flash('لا يوجد مستخدمون لتطبيق الصلاحيات عليهم', 'info')
                
        except Exception as e:
            print(f"خطأ في تطبيق الصلاحيات على الجميع: {e}")
            flash('حدث خطأ في تطبيق الصلاحيات', 'danger')
        finally:
            conn.close()
    
    return redirect(url_for('permissions'))

@app.route('/permissions')
@login_required
@permission_required('إدارة_الصلاحيات')
def permissions():
    """صفحة إدارة الصلاحيات"""
    db = Database()
    conn = db.get_connection()
    
    users = []
    permissions = []
    user_permissions = {}
    
    if conn:
        try:
            # الحصول على المستخدمين
            cursor = conn.execute("SELECT معرف_المستخدم, اسم_المستخدم, الاسم_الكامل FROM المستخدمين WHERE مفعل = 1")
            users = [dict(row) for row in cursor.fetchall()]
            
            # الحصول على الصلاحيات
            cursor = conn.execute("SELECT معرف_الصلاحية, اسم_الصلاحية, وصف_الصلاحية FROM الصلاحيات WHERE مفعل = 1")
            permissions = [dict(row) for row in cursor.fetchall()]
            
            # الحصول على صلاحيات كل مستخدم
            for user in users:
                cursor = conn.execute("""
                    SELECT p.معرف_الصلاحية
                    FROM تخصيص_الصلاحيات up
                    JOIN الصلاحيات p ON up.معرف_الصلاحية = p.معرف_الصلاحية
                    WHERE up.معرف_المستخدم = ? AND p.مفعل = 1
                """, (user['معرف_المستخدم'],))
                
                user_permissions[user['معرف_المستخدم']] = [row[0] for row in cursor.fetchall()]
            
        except Exception as e:
            print(f"خطأ في الحصول على الصلاحيات: {e}")
            flash('حدث خطأ في تحميل البيانات', 'danger')
        finally:
            conn.close()
    
    return render_template('permissions.html', users=users, permissions=permissions, user_permissions=user_permissions)

@app.route('/update_permissions', methods=['POST'])
@login_required
@permission_required('إدارة_الصلاحيات')
def update_permissions():
    """تحديث صلاحيات المستخدم"""
    user_id = request.form.get('user_id')
    selected_permissions = request.form.getlist('permissions')
    
    db = Database()
    conn = db.get_connection()
    
    if conn:
        try:
            # حذف الصلاحيات الحالية
            conn.execute("DELETE FROM تخصيص_الصلاحيات WHERE معرف_المستخدم = ?", (user_id,))
            
            # إضافة الصلاحيات الجديدة
            for permission_id in selected_permissions:
                conn.execute("""
                    INSERT INTO تخصيص_الصلاحيات (معرف_المستخدم, معرف_الصلاحية, منح_بواسطة)
                    VALUES (?, ?, ?)
                """, (user_id, permission_id, session['user_id']))
            
            conn.commit()
            
            # تسجيل النشاط
            cursor = conn.execute("SELECT اسم_المستخدم FROM المستخدمين WHERE معرف_المستخدم = ?", (user_id,))
            username = cursor.fetchone()[0]
            
            db.log_activity(
                session['user_id'], 
                "تحديث صلاحيات", 
                "تخصيص_الصلاحيات", 
                user_id,
                details=f"تم تحديث صلاحيات المستخدم {username}"
            )
            
            flash('تم تحديث الصلاحيات بنجاح', 'success')
            
        except Exception as e:
            print(f"خطأ في تحديث الصلاحيات: {e}")
            flash('حدث خطأ في تحديث الصلاحيات', 'danger')
        finally:
            conn.close()
    
    return redirect(url_for('permissions'))

@app.route('/activity_log')
@login_required
@permission_required('عرض_سجل_الأنشطة')
def activity_log():
    """صفحة سجل الأنشطة"""
    db = Database()
    conn = db.get_connection()
    activities = []
    
    page = request.args.get('page', 1, type=int)
    per_page = 50
    
    if conn:
        try:
            cursor = conn.execute("""
                SELECT sa.*, u.الاسم_الكامل
                FROM سجل_الأنشطة sa
                JOIN المستخدمين u ON sa.معرف_المستخدم = u.معرف_المستخدم
                ORDER BY sa.التاريخ DESC
                LIMIT ? OFFSET ?
            """, (per_page, (page - 1) * per_page))
            
            activities = [dict(row) for row in cursor.fetchall()]
            
        except Exception as e:
            print(f"خطأ في الحصول على سجل الأنشطة: {e}")
            flash('حدث خطأ في تحميل البيانات', 'danger')
        finally:
            conn.close()
    
    return render_template('activity_log.html', activities=activities, page=page)

@app.route('/reports')
@login_required
@permission_required('عرض_التقارير')
def reports():
    """صفحة التقارير"""
    db = Database()
    conn = db.get_connection()
    
    report_data = {
        'devices_by_status': [],
        'devices_by_type': [],
        'devices_by_unit': [],
        'monthly_additions': []
    }
    
    if conn:
        try:
            # الأجهزة حسب الحالة
            cursor = conn.execute("""
                SELECT حالة_الجهاز, COUNT(*) as count
                FROM الأجهزة
                GROUP BY حالة_الجهاز
            """)
            report_data['devices_by_status'] = [dict(row) for row in cursor.fetchall()]
            
            # الأجهزة حسب النوع
            cursor = conn.execute("""
                SELECT نوع_الجهاز, COUNT(*) as count
                FROM الأجهزة
                GROUP BY نوع_الجهاز
                ORDER BY count DESC
                LIMIT 10
            """)
            report_data['devices_by_type'] = [dict(row) for row in cursor.fetchall()]
            
            # الأجهزة حسب الوحدة
            cursor = conn.execute("""
                SELECT الجهة_المستلمة, COUNT(*) as count
                FROM الأجهزة
                GROUP BY الجهة_المستلمة
                ORDER BY count DESC
                LIMIT 10
            """)
            report_data['devices_by_unit'] = [dict(row) for row in cursor.fetchall()]
            
            # الإضافات الشهرية
            cursor = conn.execute("""
                SELECT DATE(تاريخ_الإدخال) as date, COUNT(*) as count
                FROM الأجهزة
                WHERE تاريخ_الإدخال >= date('now', '-30 days')
                GROUP BY DATE(تاريخ_الإدخال)
                ORDER BY date
            """)
            report_data['monthly_additions'] = [dict(row) for row in cursor.fetchall()]
            
        except Exception as e:
            print(f"خطأ في إنشاء التقارير: {e}")
            flash('حدث خطأ في تحميل التقارير', 'danger')
        finally:
            conn.close()
    
    return render_template('reports.html', report_data=report_data)

# ======= وظائف التصدير =======

@app.route('/export/devices')
@login_required
@permission_required('تصدير_البيانات')
def export_devices():
    """تصدير جميع الأجهزة إلى Excel"""
    db = Database()
    conn = db.get_connection()
    
    if conn:
        try:
            # الحصول على جميع الأجهزة
            cursor = conn.execute("""
                SELECT 
                    معرف_فريد as 'معرف الجهاز',
                    رقم_الجهاز as 'رقم الجهاز',
                    نوع_الجهاز as 'نوع الجهاز',
                    حالة_الجهاز as 'حالة الجهاز',
                    اسم_الجهاز as 'اسم الجهاز',
                    الجهة_المستلمة as 'الجهة المستلمة',
                    اسم_المستلم as 'اسم المستلم',
                    تاريخ_الاستلام as 'تاريخ الاستلام',
                    تاريخ_الإدخال as 'تاريخ الإدخال',
                    ملاحظات as 'ملاحظات'
                FROM الأجهزة
                ORDER BY معرف_فريد
            """)
            
            devices = [dict(row) for row in cursor.fetchall()]
            
            # إنشاء DataFrame
            df = pd.DataFrame(devices)
            
            # إنشاء ملف Excel في الذاكرة
            if devices:
                output = io.BytesIO()
                with pd.ExcelWriter(output, engine='openpyxl') as writer:
                    df.to_excel(writer, sheet_name='الأجهزة', index=False)
            else:
                flash('لا توجد أجهزة للتصدير', 'warning')
                return redirect(url_for('devices'))
            
            output.seek(0)
            
            # تسجيل النشاط
            db.log_activity(
                session['user_id'], 
                "تصدير بيانات", 
                "الأجهزة", 
                details=f"تم تصدير {len(devices)} جهاز إلى Excel"
            )
            
            filename = f"أجهزة_{datetime.now().strftime('%Y-%m-%d_%H-%M')}.xlsx"
            
            return send_file(
                output,
                mimetype='application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                as_attachment=True,
                download_name=filename
            )
            
        except Exception as e:
            print(f"خطأ في تصدير الأجهزة: {e}")
            flash('حدث خطأ في تصدير البيانات', 'danger')
        finally:
            conn.close()
    
    return redirect(url_for('devices'))

@app.route('/export/devices_filtered')
@login_required
@permission_required('تصدير_البيانات')
def export_devices_filtered():
    """تصدير الأجهزة المُفلترة إلى Excel"""
    db = Database()
    conn = db.get_connection()
    
    # الحصول على معايير الفلترة من GET parameters
    device_type = request.args.get('device_type', '')
    status = request.args.get('status', '')
    unit = request.args.get('unit', '')
    date_from = request.args.get('date_from', '')
    date_to = request.args.get('date_to', '')
    
    if conn:
        try:
            # إنشاء استعلام SQL مع الفلاتر
            query = """
                SELECT 
                    معرف_فريد as 'معرف الجهاز',
                    رقم_الجهاز as 'رقم الجهاز',
                    نوع_الجهاز as 'نوع الجهاز',
                    حالة_الجهاز as 'حالة الجهاز',
                    اسم_الجهاز as 'اسم الجهاز',
                    الجهة_المستلمة as 'الجهة المستلمة',
                    اسم_المستلم as 'اسم المستلم',
                    تاريخ_الاستلام as 'تاريخ الاستلام',
                    تاريخ_الإدخال as 'تاريخ الإدخال',
                    ملاحظات as 'ملاحظات'
                FROM الأجهزة WHERE 1=1
            """
            params = []
            
            if device_type:
                query += " AND نوع_الجهاز = ?"
                params.append(device_type)
            
            if status:
                query += " AND حالة_الجهاز = ?"
                params.append(status)
            
            if unit:
                query += " AND الجهة_المستلمة = ?"
                params.append(unit)
            
            if date_from:
                query += " AND DATE(تاريخ_الإدخال) >= ?"
                params.append(date_from)
            
            if date_to:
                query += " AND DATE(تاريخ_الإدخال) <= ?"
                params.append(date_to)
            
            query += " ORDER BY معرف_فريد"
            
            cursor = conn.execute(query, params)
            devices = [dict(row) for row in cursor.fetchall()]
            
            # إنشاء DataFrame
            df = pd.DataFrame(devices)
            
            # إنشاء ملف Excel في الذاكرة مع معايير الفلترة
            if devices:
                output = io.BytesIO()
                with pd.ExcelWriter(output, engine='openpyxl') as writer:
                    # كتابة البيانات الأساسية
                    df.to_excel(writer, sheet_name='الأجهزة المفلترة', index=False)
                    
                    # إضافة ملخص الفلترة
                    filter_data = [
                        ['نوع الجهاز', device_type or 'الكل'],
                        ['الحالة', status or 'الكل'],
                        ['الجهة', unit or 'الكل'],
                        ['من تاريخ', date_from or 'غير محدد'],
                        ['إلى تاريخ', date_to or 'غير محدد'],
                        ['تاريخ التصدير', datetime.now().strftime('%Y-%m-%d %H:%M:%S')],
                        ['المستخدم', session.get('username', 'غير معروف')],
                        ['إجمالي النتائج', len(devices)]
                    ]
                    filter_df = pd.DataFrame(filter_data)
                    filter_df.to_excel(writer, sheet_name='معايير الفلترة', index=False, header=False)
            else:
                flash('لا توجد نتائج للتصدير', 'warning')
                return redirect(url_for('devices'))

            
            output.seek(0)
            
            # تسجيل النشاط
            filter_details = f"نوع:{device_type}, حالة:{status}, جهة:{unit}, من:{date_from}, إلى:{date_to}"
            db.log_activity(
                session['user_id'], 
                "تصدير بيانات مفلترة", 
                "الأجهزة", 
                details=f"تم تصدير {len(devices)} جهاز مفلتر ({filter_details}) إلى Excel"
            )
            
            filename = f"أجهزة_مفلترة_{datetime.now().strftime('%Y-%m-%d_%H-%M')}.xlsx"
            
            return send_file(
                output,
                mimetype='application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                as_attachment=True,
                download_name=filename
            )
            
        except Exception as e:
            print(f"خطأ في تصدير الأجهزة المفلترة: {e}")
            flash('حدث خطأ في تصدير البيانات المفلترة', 'danger')
        finally:
            conn.close()
    
    return redirect(url_for('devices'))

@app.route('/export/database')
@login_required
@permission_required('إدارة_النظام')
def export_database():
    """تصدير قاعدة البيانات كاملة"""
    db = Database()
    conn = db.get_connection()
    
    if conn:
        try:
            # إنشاء ملف مضغوط في الذاكرة
            zip_buffer = io.BytesIO()
            
            with zipfile.ZipFile(zip_buffer, 'w', zipfile.ZIP_DEFLATED) as zip_file:
                
                # تصدير جدول الأجهزة
                cursor = conn.execute("SELECT * FROM الأجهزة")
                devices_df = pd.DataFrame([dict(row) for row in cursor.fetchall()])
                devices_excel = io.BytesIO()
                with pd.ExcelWriter(devices_excel, engine='openpyxl') as writer:
                    devices_df.to_excel(writer, index=False)
                zip_file.writestr(f"الأجهزة_{datetime.now().strftime('%Y-%m-%d')}.xlsx", devices_excel.getvalue())
                
                # تصدير جدول المستخدمين (بدون كلمات المرور)
                cursor = conn.execute("""
                    SELECT معرف_المستخدم, اسم_المستخدم, الاسم_الكامل, البريد_الإلكتروني, 
                           رقم_الهاتف, الوحدة_العسكرية, تاريخ_الإنشاء, مفعل 
                    FROM المستخدمين
                """)
                users_df = pd.DataFrame([dict(row) for row in cursor.fetchall()])
                users_excel = io.BytesIO()
                with pd.ExcelWriter(users_excel, engine='openpyxl') as writer:
                    users_df.to_excel(writer, index=False)
                zip_file.writestr(f"المستخدمون_{datetime.now().strftime('%Y-%m-%d')}.xlsx", users_excel.getvalue())
                
                # تصدير سجل الأنشطة
                cursor = conn.execute("""
                    SELECT sa.*, u.اسم_المستخدم, u.الاسم_الكامل
                    FROM سجل_الأنشطة sa
                    JOIN المستخدمين u ON sa.معرف_المستخدم = u.معرف_المستخدم
                    ORDER BY sa.التاريخ DESC
                """)
                activities_df = pd.DataFrame([dict(row) for row in cursor.fetchall()])
                activities_excel = io.BytesIO()
                with pd.ExcelWriter(activities_excel, engine='openpyxl') as writer:
                    activities_df.to_excel(writer, index=False)
                zip_file.writestr(f"سجل_الأنشطة_{datetime.now().strftime('%Y-%m-%d')}.xlsx", activities_excel.getvalue())
                
                # إضافة معلومات التصدير
                export_data = [
                    ['تاريخ التصدير', datetime.now().strftime('%Y-%m-%d %H:%M:%S')],
                    ['المستخدم', session.get('username', 'غير معروف')],
                    ['إجمالي الأجهزة', len(devices_df)],
                    ['إجمالي المستخدمين', len(users_df)],
                    ['إجمالي الأنشطة', len(activities_df)],
                    ['نسخة النظام', '1.0']
                ]
                export_info = pd.DataFrame(export_data, columns=['البيان', 'القيمة'])
                
                info_excel = io.BytesIO()
                with pd.ExcelWriter(info_excel, engine='openpyxl') as writer:
                    export_info.to_excel(writer, index=False)
                zip_file.writestr(f"معلومات_التصدير_{datetime.now().strftime('%Y-%m-%d')}.xlsx", info_excel.getvalue())
            
            zip_buffer.seek(0)
            
            # تسجيل النشاط
            db.log_activity(
                session['user_id'], 
                "تصدير قاعدة البيانات", 
                "النظام", 
                details="تم تصدير قاعدة البيانات كاملة"
            )
            
            filename = f"قاعدة_البيانات_{datetime.now().strftime('%Y-%m-%d_%H-%M')}.zip"
            
            return send_file(
                zip_buffer,
                mimetype='application/zip',
                as_attachment=True,
                download_name=filename
            )
            
        except Exception as e:
            print(f"خطأ في تصدير قاعدة البيانات: {e}")
            flash('حدث خطأ في تصدير قاعدة البيانات', 'danger')
        finally:
            conn.close()
    
    return redirect(url_for('dashboard'))
