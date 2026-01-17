<?php
require_once 'database.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function login($username, $password) {
        $stmt = $this->db->getPDO()->prepare("
            SELECT معرف_المستخدم, اسم_المستخدم, كلمة_المرور, الاسم_الكامل, مفعل 
            FROM المستخدمين 
            WHERE اسم_المستخدم = ? AND مفعل = 1
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && hash('sha256', $password . PASSWORD_SALT) === $user['كلمة_المرور']) {
            // تسجيل دخول ناجح
            $_SESSION['user_id'] = $user['معرف_المستخدم'];
            $_SESSION['username'] = $user['اسم_المستخدم'];
            $_SESSION['full_name'] = $user['الاسم_الكامل'];
            $_SESSION['last_activity'] = time();
            
            // تحديث آخر تسجيل دخول
            $update_stmt = $this->db->getPDO()->prepare("
                UPDATE المستخدمين 
                SET آخر_تسجيل_دخول = NOW() 
                WHERE معرف_المستخدم = ?
            ");
            $update_stmt->execute([$user['معرف_المستخدم']]);
            
            // تسجيل النشاط
            $this->db->logActivity($user['معرف_المستخدم'], 'تسجيل دخول', 'المستخدمين', $user['معرف_المستخدم']);
            
            return true;
        }
        
        return false;
    }
    
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $this->db->logActivity($_SESSION['user_id'], 'تسجيل خروج', 'المستخدمين', $_SESSION['user_id']);
        }
        
        session_unset();
        session_destroy();
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function hasPermission($permission_name) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $stmt = $this->db->getPDO()->prepare("
            SELECT COUNT(*) 
            FROM تخصيص_الصلاحيات tp
            JOIN الصلاحيات p ON tp.معرف_الصلاحية = p.معرف_الصلاحية
            WHERE tp.معرف_المستخدم = ? AND p.اسم_الصلاحية = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $permission_name]);
        
        return $stmt->fetchColumn() > 0;
    }
    
    public function getUserPermissions($user_id = null) {
        if ($user_id === null) {
            $user_id = $_SESSION['user_id'] ?? null;
        }
        
        if (!$user_id) {
            return [];
        }
        
        $stmt = $this->db->getPDO()->prepare("
            SELECT p.اسم_الصلاحية, p.وصف_الصلاحية
            FROM تخصيص_الصلاحيات tp
            JOIN الصلاحيات p ON tp.معرف_الصلاحية = p.معرف_الصلاحية
            WHERE tp.معرف_المستخدم = ?
            ORDER BY p.اسم_الصلاحية
        ");
        $stmt->execute([$user_id]);
        
        return $stmt->fetchAll();
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit();
        }
    }
    
    public function requirePermission($permission_name) {
        $this->requireLogin();
        
        if (!$this->hasPermission($permission_name)) {
            header('Location: access_denied.php');
            exit();
        }
    }
}

// دالة مساعدة للتحقق من الصلاحيات
function hasPermission($permission_name) {
    $auth = new Auth();
    return $auth->hasPermission($permission_name);
}

// دالة مساعدة لطلب تسجيل الدخول
function requireLogin() {
    $auth = new Auth();
    $auth->requireLogin();
}

// دالة مساعدة لطلب صلاحية معينة
function requirePermission($permission_name) {
    $auth = new Auth();
    $auth->requirePermission($permission_name);
}
?>