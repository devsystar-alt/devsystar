<?php
require_once 'config.php';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>غير مصرح - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap RTL CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .error-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card error-container">
                    <div class="card-body text-center p-5">
                        <div class="mb-4">
                            <i class="fas fa-ban fa-5x text-danger"></i>
                        </div>
                        
                        <h2 class="text-danger mb-3">غير مصرح بالوصول</h2>
                        
                        <p class="text-muted mb-4">
                            عذراً، ليس لديك الصلاحية للوصول إلى هذه الصفحة.
                            <br>
                            يرجى التواصل مع مدير النظام للحصول على الصلاحيات المطلوبة.
                        </p>
                        
                        <div class="d-grid gap-2 d-md-block">
                            <a href="dashboard.php" class="btn btn-primary">
                                <i class="fas fa-home"></i>
                                العودة إلى الرئيسية
                            </a>
                            <a href="javascript:history.back()" class="btn btn-secondary">
                                <i class="fas fa-arrow-right"></i>
                                العودة للصفحة السابقة
                            </a>
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