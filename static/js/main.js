// وظائف JavaScript مخصصة لنظام إدارة الأجهزة العسكرية

document.addEventListener('DOMContentLoaded', function() {
    // تهيئة التطبيق
    initializeApp();
    
    // تهيئة النماذج
    initializeForms();
    
    // تهيئة الجداول
    initializeTables();
    
    // تهيئة التنبيهات
    initializeAlerts();
    
    // تهيئة الأدوات المساعدة
    initializeUtilities();
});

/**
 * تهيئة التطبيق الأساسية
 */
function initializeApp() {
    // إضافة تأثيرات التحميل
    showLoadingSpinner();
    
    // إخفاء مؤشر التحميل عند اكتمال التحميل
    setTimeout(hideLoadingSpinner, 500);
    
    // تهيئة تلميحات Bootstrap
    initializeTooltips();
    
    // تهيئة القوائم المنسدلة
    initializeDropdowns();
    
    console.log('تم تهيئة النظام بنجاح');
}

/**
 * تهيئة النماذج
 */
function initializeForms() {
    // التحقق من صحة النماذج
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
    
    // تحسين حقول التاريخ
    initializeDateFields();
    
    // تحسين حقول البحث
    initializeSearchFields();
    
    // تهيئة نماذج إضافة/تعديل الأجهزة
    initializeDeviceForms();
}

/**
 * تهيئة حقول التاريخ
 */
function initializeDateFields() {
    const dateFields = document.querySelectorAll('input[type="date"]');
    dateFields.forEach(field => {
        // تعيين التاريخ الحالي كحد أقصى للحقول المطلوبة
        if (field.hasAttribute('max-today')) {
            const today = new Date().toISOString().split('T')[0];
            field.setAttribute('max', today);
        }
        
        // تحسين عرض التاريخ
        field.addEventListener('change', function() {
            if (this.value) {
                this.classList.add('has-value');
            } else {
                this.classList.remove('has-value');
            }
        });
    });
}

/**
 * تهيئة حقول البحث
 */
function initializeSearchFields() {
    const searchFields = document.querySelectorAll('input[type="search"], .search-field');
    searchFields.forEach(field => {
        // إضافة البحث المباشر
        let searchTimeout;
        field.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                performSearch(this.value);
            }, 300);
        });
        
        // إضافة أيقونة البحث
        if (!field.parentElement.querySelector('.search-icon')) {
            const icon = document.createElement('i');
            icon.className = 'fas fa-search search-icon';
            field.parentElement.style.position = 'relative';
            field.parentElement.appendChild(icon);
        }
    });
}

/**
 * تهيئة نماذج الأجهزة
 */
function initializeDeviceForms() {
    // تحسين نموذج إضافة الأجهزة
    const deviceForm = document.querySelector('#deviceForm, form[action*="device"]');
    if (deviceForm) {
        // التحقق من رقم الجهاز
        const deviceNumberField = deviceForm.querySelector('#device_number, [name="device_number"]');
        if (deviceNumberField) {
            deviceNumberField.addEventListener('blur', function() {
                validateDeviceNumber(this.value);
            });
        }
        
        // تحسين حقول الملحقات
        const accessoryCheckboxes = deviceForm.querySelectorAll('input[type="checkbox"]');
        accessoryCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                updateAccessorySummary();
            });
        });
        
        // تحديث ملخص الملحقات عند تحميل الصفحة
        updateAccessorySummary();
    }
}

/**
 * تهيئة الجداول
 */
function initializeTables() {
    // تحسين عرض الجداول
    const tables = document.querySelectorAll('.table');
    tables.forEach(table => {
        // إضافة فرز للأعمدة
        addTableSorting(table);
        
        // تحسين عرض البيانات الطويلة
        improveTableDisplay(table);
        
        // إضافة تأثيرات التمرير
        addHoverEffects(table);
    });
    
    // تحسين الجداول المتجاوبة
    improveResponsiveTables();
}

/**
 * إضافة فرز الجداول
 */
function addTableSorting(table) {
    const headers = table.querySelectorAll('th');
    headers.forEach((header, index) => {
        if (!header.classList.contains('no-sort')) {
            header.style.cursor = 'pointer';
            header.addEventListener('click', () => {
                sortTable(table, index);
            });
            
            // إضافة أيقونة الفرز
            if (!header.querySelector('.sort-icon')) {
                const icon = document.createElement('i');
                icon.className = 'fas fa-sort sort-icon ms-2';
                header.appendChild(icon);
            }
        }
    });
}

/**
 * فرز الجداول
 */
function sortTable(table, columnIndex) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const header = table.querySelectorAll('th')[columnIndex];
    const icon = header.querySelector('.sort-icon');
    
    // تحديد اتجاه الفرز
    let ascending = true;
    if (icon.classList.contains('fa-sort-up')) {
        ascending = false;
    }
    
    // فرز الصفوف
    rows.sort((a, b) => {
        const aValue = a.cells[columnIndex].textContent.trim();
        const bValue = b.cells[columnIndex].textContent.trim();
        
        // محاولة فرز الأرقام
        const aNumber = parseFloat(aValue);
        const bNumber = parseFloat(bValue);
        
        if (!isNaN(aNumber) && !isNaN(bNumber)) {
            return ascending ? aNumber - bNumber : bNumber - aNumber;
        }
        
        // فرز النصوص
        return ascending ? aValue.localeCompare(bValue, 'ar') : bValue.localeCompare(aValue, 'ar');
    });
    
    // إعادة ترتيب الصفوف
    rows.forEach(row => tbody.appendChild(row));
    
    // تحديث أيقونة الفرز
    table.querySelectorAll('.sort-icon').forEach(i => {
        i.className = 'fas fa-sort sort-icon ms-2';
    });
    
    icon.className = `fas fa-sort-${ascending ? 'up' : 'down'} sort-icon ms-2`;
}

/**
 * تحسين عرض الجداول
 */
function improveTableDisplay(table) {
    // إضافة أرقام الصفوف
    const rows = table.querySelectorAll('tbody tr');
    rows.forEach((row, index) => {
        if (!row.querySelector('.row-number')) {
            const numberCell = document.createElement('td');
            numberCell.className = 'row-number text-muted';
            numberCell.textContent = index + 1;
            row.insertBefore(numberCell, row.firstChild);
        }
    });
    
    // إضافة رقم العمود إذا لم يكن موجوداً
    const headerRow = table.querySelector('thead tr');
    if (headerRow && !headerRow.querySelector('.row-number-header')) {
        const numberHeader = document.createElement('th');
        numberHeader.className = 'row-number-header';
        numberHeader.textContent = '#';
        headerRow.insertBefore(numberHeader, headerRow.firstChild);
    }
}

/**
 * تحسين الجداول المتجاوبة
 */
function improveResponsiveTables() {
    const responsiveTables = document.querySelectorAll('.table-responsive');
    responsiveTables.forEach(container => {
        const table = container.querySelector('table');
        if (table) {
            // إضافة مؤشر التمرير
            container.addEventListener('scroll', function() {
                if (this.scrollLeft > 0) {
                    this.classList.add('scrolled');
                } else {
                    this.classList.remove('scrolled');
                }
            });
        }
    });
}

/**
 * تهيئة التنبيهات
 */
function initializeAlerts() {
    // إخفاء التنبيهات تلقائياً
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            fadeOut(alert);
        }, 5000);
    });
    
    // تحسين إغلاق التنبيهات
    const closeButtons = document.querySelectorAll('.alert .btn-close');
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            fadeOut(this.parentElement);
        });
    });
}

/**
 * تهيئة الأدوات المساعدة
 */
function initializeUtilities() {
    // إضافة أزرار "العودة لأعلى"
    addBackToTopButton();
    
    // تحسين التنقل بلوحة المفاتيح
    improveKeyboardNavigation();
    
    // إضافة اختصارات لوحة المفاتيح
    addKeyboardShortcuts();
    
    // تحسين إمكانية الوصول
    improveAccessibility();
    
    // إضافة تأكيدات الحذف
    addDeleteConfirmations();
}

/**
 * إضافة زر العودة لأعلى
 */
function addBackToTopButton() {
    const button = document.createElement('button');
    button.innerHTML = '<i class="fas fa-arrow-up"></i>';
    button.className = 'btn btn-primary back-to-top';
    button.style.cssText = `
        position: fixed;
        bottom: 20px;
        left: 20px;
        z-index: 1000;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        display: none;
    `;
    
    document.body.appendChild(button);
    
    // إظهار/إخفاء الزر حسب التمرير
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            button.style.display = 'block';
        } else {
            button.style.display = 'none';
        }
    });
    
    // العودة لأعلى عند النقر
    button.addEventListener('click', function() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
}

/**
 * إضافة تأكيدات الحذف
 */
function addDeleteConfirmations() {
    const deleteLinks = document.querySelectorAll('a[href*="delete"], .btn-danger');
    deleteLinks.forEach(link => {
        link.addEventListener('click', function(event) {
            const confirmMessage = this.getAttribute('data-confirm') || 
                                 'هل أنت متأكد من أنك تريد حذف هذا العنصر؟';
            
            if (!confirm(confirmMessage)) {
                event.preventDefault();
                return false;
            }
        });
    });
}

/**
 * تحسين التنقل بلوحة المفاتيح
 */
function improveKeyboardNavigation() {
    // إضافة مؤشرات التركيز المرئية
    const focusableElements = document.querySelectorAll(
        'a, button, input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );
    
    focusableElements.forEach(element => {
        element.addEventListener('focus', function() {
            this.classList.add('keyboard-focus');
        });
        
        element.addEventListener('blur', function() {
            this.classList.remove('keyboard-focus');
        });
    });
}

/**
 * إضافة اختصارات لوحة المفاتيح
 */
function addKeyboardShortcuts() {
    document.addEventListener('keydown', function(event) {
        // Ctrl/Cmd + الصفحة الرئيسية
        if ((event.ctrlKey || event.metaKey) && event.key === 'h') {
            event.preventDefault();
            window.location.href = '/dashboard';
        }
        
        // Ctrl/Cmd + إضافة جهاز جديد
        if ((event.ctrlKey || event.metaKey) && event.key === 'n') {
            event.preventDefault();
            const addButton = document.querySelector('a[href*="add"], .btn[href*="add"]');
            if (addButton) {
                addButton.click();
            }
        }
        
        // Escape لإغلاق النوافذ المنبثقة
        if (event.key === 'Escape') {
            const modals = document.querySelectorAll('.modal.show');
            modals.forEach(modal => {
                bootstrap.Modal.getInstance(modal)?.hide();
            });
        }
    });
}

/**
 * تحسين إمكانية الوصول
 */
function improveAccessibility() {
    // إضافة تسميات ARIA للعناصر
    const buttons = document.querySelectorAll('button:not([aria-label])');
    buttons.forEach(button => {
        const icon = button.querySelector('i[class*="fa-"]');
        if (icon) {
            const iconClass = Array.from(icon.classList).find(cls => cls.startsWith('fa-'));
            const label = getAriaLabelForIcon(iconClass);
            if (label) {
                button.setAttribute('aria-label', label);
            }
        }
    });
    
    // تحسين الجداول
    const tables = document.querySelectorAll('table:not([role])');
    tables.forEach(table => {
        table.setAttribute('role', 'table');
        const caption = table.querySelector('caption');
        if (!caption) {
            const newCaption = document.createElement('caption');
            newCaption.className = 'sr-only';
            newCaption.textContent = 'جدول بيانات النظام';
            table.insertBefore(newCaption, table.firstChild);
        }
    });
}

/**
 * الحصول على تسمية ARIA للأيقونات
 */
function getAriaLabelForIcon(iconClass) {
    const iconLabels = {
        'fa-edit': 'تعديل',
        'fa-trash': 'حذف',
        'fa-plus': 'إضافة',
        'fa-search': 'بحث',
        'fa-save': 'حفظ',
        'fa-cancel': 'إلغاء',
        'fa-home': 'الرئيسية',
        'fa-user': 'المستخدم',
        'fa-users': 'المستخدمون',
        'fa-desktop': 'الأجهزة',
        'fa-chart-bar': 'التقارير',
        'fa-history': 'السجل',
        'fa-key': 'الصلاحيات'
    };
    
    return iconLabels[iconClass] || '';
}

/**
 * تهيئة التلميحات
 */
function initializeTooltips() {
    const tooltipElements = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipElements.forEach(element => {
        new bootstrap.Tooltip(element);
    });
}

/**
 * تهيئة القوائم المنسدلة
 */
function initializeDropdowns() {
    const dropdownElements = document.querySelectorAll('[data-bs-toggle="dropdown"]');
    dropdownElements.forEach(element => {
        new bootstrap.Dropdown(element);
    });
}

/**
 * وظائف مساعدة
 */

/**
 * إظهار مؤشر التحميل
 */
function showLoadingSpinner() {
    const spinner = document.createElement('div');
    spinner.id = 'loadingSpinner';
    spinner.innerHTML = `
        <div class="d-flex justify-content-center align-items-center position-fixed w-100 h-100" 
             style="top: 0; left: 0; background: rgba(255,255,255,0.8); z-index: 9999;">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">جاري التحميل...</span>
            </div>
        </div>
    `;
    document.body.appendChild(spinner);
}

/**
 * إخفاء مؤشر التحميل
 */
function hideLoadingSpinner() {
    const spinner = document.getElementById('loadingSpinner');
    if (spinner) {
        fadeOut(spinner);
    }
}

/**
 * تأثير الإخفاء التدريجي
 */
function fadeOut(element) {
    element.style.opacity = '1';
    element.style.transition = 'opacity 0.3s ease';
    
    setTimeout(() => {
        element.style.opacity = '0';
        setTimeout(() => {
            if (element.parentNode) {
                element.parentNode.removeChild(element);
            }
        }, 300);
    }, 10);
}

/**
 * إضافة تأثيرات التمرير
 */
function addHoverEffects(table) {
    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.01)';
            this.style.transition = 'transform 0.2s ease';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });
}

/**
 * تحديث ملخص الملحقات
 */
function updateAccessorySummary() {
    const checkboxes = document.querySelectorAll('input[type="checkbox"][name*="accessories"], input[type="checkbox"]:not([name*="active"])');
    const summary = document.getElementById('accessorySummary');
    
    if (!summary) return;
    
    const selected = Array.from(checkboxes)
        .filter(cb => cb.checked)
        .map(cb => cb.nextElementSibling?.textContent || cb.value);
    
    if (selected.length > 0) {
        summary.innerHTML = `<strong>الملحقات المحددة:</strong> ${selected.join('، ')}`;
        summary.className = 'alert alert-info mt-2';
    } else {
        summary.innerHTML = '';
        summary.className = '';
    }
}

/**
 * التحقق من رقم الجهاز
 */
function validateDeviceNumber(deviceNumber) {
    if (!deviceNumber) return;
    
    // يمكن إضافة تحقق من قاعدة البيانات هنا
    // مؤقتاً سنتحقق من طول الرقم فقط
    if (deviceNumber.length < 3) {
        showAlert('رقم الجهاز قصير جداً', 'warning');
        return false;
    }
    
    return true;
}

/**
 * البحث المباشر
 */
function performSearch(searchTerm) {
    // تطبيق البحث على الجداول المرئية
    const tables = document.querySelectorAll('.table tbody');
    tables.forEach(tbody => {
        const rows = tbody.querySelectorAll('tr');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const shouldShow = text.includes(searchTerm.toLowerCase());
            row.style.display = shouldShow ? '' : 'none';
        });
    });
}

/**
 * إظهار رسالة تنبيه
 */
function showAlert(message, type = 'info') {
    const alertContainer = document.querySelector('.alert-container') || document.body;
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    alertContainer.insertBefore(alert, alertContainer.firstChild);
    
    // إخفاء تلقائي بعد 5 ثوان
    setTimeout(() => fadeOut(alert), 5000);
}

/**
 * تحديث الصفحة بشكل منعش
 */
function refreshPage() {
    window.location.reload();
}

/**
 * تصدير البيانات (وهمي - سيحتاج لتطبيق من جانب الخادم)
 */
function exportData(format = 'excel') {
    showAlert('جاري تحضير ملف التصدير...', 'info');
    // هنا يمكن إضافة منطق التصدير الفعلي
}

// إضافة متغيرات عامة مفيدة
window.DeviceManagement = {
    showAlert,
    refreshPage,
    exportData,
    validateDeviceNumber,
    updateAccessorySummary
};

console.log('تم تحميل ملف JavaScript الرئيسي بنجاح');
