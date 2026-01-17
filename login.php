<?php
require_once 'config.php';
require_once 'auth.php';

$auth = new Auth();

// إعادة التوجيه إذا كان المستخدم مسجلاً بالفعل
if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

// معالجة انتهاء الجلسة
if (isset($_GET['expired'])) {
    $error = 'انتهت جلستك، يرجى تسجيل الدخول مرة أخرى';
}

// معالجة تسجيل الخروج
if (isset($_GET['logout'])) {
    $success = 'تم تسجيل الخروج بنجاح';
}

// معالجة طلب تسجيل الدخول
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'يرجى إدخال اسم المستخدم وكلمة المرور';
    } else {
        if ($auth->login($username, $password)) {
            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'اسم المستخدم أو كلمة المرور غير صحيحة';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap RTL CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts - Amiri Arabic Font -->
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }
        
        .elegant-font {
            font-family: 'Amiri', 'Times New Roman', serif;
            font-weight: 600;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
            letter-spacing: 1px;
        }

        .designer-signature {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .designer-signature:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }

        .contact-info {
            margin-top: 10px;
        }

        .contact-text {
            font-family: 'Amiri', 'Times New Roman', serif;
            font-size: 14px;
            font-weight: 500;
            margin-left: 8px;
        }

        .contact-info i {
            width: 20px;
            text-align: center;
        }

        .contact-info p {
            transition: all 0.2s ease;
        }

        .contact-info p:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card login-container shadow">
                    <div class="card-header text-center bg-primary text-white">
                        <h3>
                            <i class="fas fa-shield-alt"></i>
                            تسجيل الدخول
                        </h3>
                        <p class="mb-0"><?php echo SITE_NAME; ?></p>
                    </div>
                    
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-triangle"></i>
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="fas fa-check-circle"></i>
                                <?php echo htmlspecialchars($success); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">
                                    <i class="fas fa-user"></i>
                                    اسم المستخدم
                                </label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock"></i>
                                    كلمة المرور
                                </label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt"></i>
                                    تسجيل الدخول
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <div class="card-footer text-center text-muted">
                        <small>
                            <i class="fas fa-info-circle"></i>
                            للحصول على حساب جديد، يرجى التواصل مع مدير النظام
                        </small>
                    </div>
                </div>
                
                <!-- تصميم وبرمجة أبو ربيع الحميدي -->
                <div class="text-center mt-4 mb-3">
                    <div class="designer-signature">
                        <h5 class="elegant-font text-primary mb-2">
                            <i class="fas fa-code"></i>
                            تصميم وبرمجة أبو ربيع الحميدي
                        </h5>
                        <div class="contact-info">
                            <p class="mb-1 text-muted">
                                <i class="fas fa-phone text-success"></i>
                                <span class="contact-text">+967 777 669 270</span>
                            </p>
                            <p class="mb-0 text-muted">
                                <i class="fas fa-envelope text-info"></i>
                                <span class="contact-text">abu.rabee.alhumaydi@email.com</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>