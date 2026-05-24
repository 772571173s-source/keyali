<?php
// 1. 🔑 تشغيل الجلسة وحجز المخرجات فوراً لمنع أخطاء التحويل والهيدر (OWASP Architecture)
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_use_only_cookies', 1);
    session_start();
}

// 🛡️ جدار حماية مدمج: فحص صلاحيات الدخول ومنع الاختراق (Broken Access Control)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['root', 'admin'])) {
    header("Location: ../login.php?error=forbidden");
    exit();
}

// 🔄 تجديد معرف الجلسة تلقائياً لتعزيز الأمان الرقمي
session_regenerate_id(true);

// 2. 🔌 الاتصال بقاعدة البيانات
include '../config/db.php';

$message = "";
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 3. 🔍 جلب بيانات الكود الحالي المراد تعديله من قاعدة البيانات
$stmt = $pdo->prepare("SELECT * FROM code_terms WHERE id = ?");
$stmt->execute([$id]);
$code_data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$code_data) {
    die("<div style='color:#f87171; background:rgba(248,113,113,0.1); padding:20px; text-align:center; font-family:sans-serif; font-weight:bold; border-radius:8px; margin:20px;'>❌ خطأ: هذا المصطلح البرمجي غير موجود في النظام!</div>");
}

// 4. 💾 محرك معالجة واستقبال البيانات وتحديثها (Update Engine)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $code_text   = trim($_POST['code_text']);
    $description = trim($_POST['description']);
    
    if (!empty($code_text) && !empty($description)) {
        try {
            $update_stmt = $pdo->prepare("UPDATE code_terms SET code_text = ?, description = ? WHERE id = ?");
            if ($update_stmt->execute([$code_text, $description, $id])) { 
                // توجيه فوري لصفحة الإدارة الشاملة مع إشارة النجاح
                header("Location: manage-all.php?success=update"); 
                exit(); 
            }
        } catch (PDOException $e) {
            $message = "<div class='alert error'>⚠️ خطأ في السيرفر: فشل تحديث البيانات بأمان.</div>";
        }
    } else {
        $message = "<div class='alert error'>❌ يرجى ملء الحقول المطلوبة أولاً!</div>";
    }
}

// 📊 جلب جميع الأكواد والمصطلحات المضافة مسبقاً مع تصنيفاتها للمراجعة السريعة واللايف عبر الـ JS
$all_codes = [];
try {
    $all_codes = $pdo->query("SELECT ct.*, l.lang_name FROM code_terms ct JOIN languages l ON ct.language_id = l.id ORDER BY ct.id DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Failed to fetch code terms: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل الكود البرمجي ✏️</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700;900&family=Fira+Code:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Tajawal', sans-serif; background: #020617; color: #fff; margin: 0; padding-bottom: 40px; }
        
        .navbar { background: #0f172a; border-bottom: 1px solid #1e293b; padding: 15px 20px; }
        .nav-container { max-width: 1100px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 18px; font-weight: bold; color: #fff; text-decoration: none; }
        .nav-links { margin: 0; padding: 0; list-style: none; display: flex; gap: 15px; }
        .nav-links a { text-decoration: none; font-size: 14px; color: #94a3b8; font-weight: bold; transition: color 0.2s; }
        .nav-links a:hover { color: #f59e0b; }

        .wrapper { padding: 30px 20px; box-sizing: border-box; }

        .form-container { max-width: 700px; margin: 0 auto; background: #0f172a; padding: 35px; border-radius: 20px; border: 1px solid #f59e0b; box-shadow: 0 10px 30px rgba(245, 158, 11, 0.05); box-sizing: border-box; }
        .page-header { text-align: center; margin-bottom: 30px; border-bottom: 1px solid #1e293b; padding-bottom: 20px; }
        .page-header h2 { font-size: clamp(18px, 4.5vw, 24px); margin: 0; }
        .page-header p { font-size: clamp(13px, 3.5vw, 15px); color: #94a3b8; margin-top: 8px; }
        
        .form-group { margin-bottom: 25px; text-align: right; }
        .form-group label { display: block; margin-bottom: 10px; color: #f59e0b; font-weight: bold; font-size: 15px; }
        
        .form-control { width: 100%; padding: 14px 18px; background: #1e293b; border: 1px solid #334155; border-radius: 10px; color: #fff; font-size: 16px; box-sizing: border-box; transition: all 0.2s ease; }
        .form-control:focus { border-color: #f59e0b; outline: none; box-shadow: 0 0 12px rgba(245, 158, 11, 0.15); }
        
        .textarea-control { resize: vertical; min-height: 120px; font-family: 'Fira Code', monospace; font-size: 14px; direction: ltr; text-align: left; line-height: 1.5; }
        
        .btn-submit { width: 100%; padding: 16px; background: #f59e0b; color: #020617; border: none; border-radius: 10px; font-size: 16px; font-weight: bold; cursor: pointer; transition: all 0.2s ease; box-sizing: border-box; }
        .btn-submit:hover { background: #d97706; }
        .btn-submit:active { transform: scale(0.98); }
        
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 25px; text-align: center; font-weight: bold; font-size: 15px; box-sizing: border-box; }
        .alert.error { background: rgba(248, 113, 113, 0.15); color: #f87171; border: 1px solid rgba(248, 113, 113, 0.3); }
        
        /* ✨ قسم فحص ومقارنة بنك الأكواد المشترك المدمج بتجاوب كامل */
        .recent-section { margin-top: 35px; border-top: 1px solid #1e293b; padding-top: 25px; }
        .recent-title { font-size: 15px; font-weight: bold; color: #f59e0b; margin-bottom: 15px; }
        
        .mini-search { width: 100%; padding: 14px 15px; background: #0f172a; border: 1px solid #334155; border-radius: 10px; color: #fff; font-size: 14px; margin-bottom: 15px; outline: none; box-sizing: border-box; font-family: inherit; transition: 0.2s; }
        .mini-search:focus { border-color: #f59e0b; box-shadow: 0 0 12px rgba(245, 158, 11, 0.15); }

        .table-responsive { width: 100%; overflow-x: auto; border-radius: 10px; border: 1px solid #1e293b; background: #0f172a; max-height: 300px; overflow-y: auto; }
        .recent-table { width: 100%; border-collapse: collapse; text-align: right; font-size: 14px; }
        .recent-table th { background: #1e293b; color: #94a3b8; padding: 12px; font-weight: bold; border-bottom: 1px solid #1e293b; position: sticky; top: 0; z-index: 5; }
        .recent-table td { padding: 12px; border-bottom: 1px solid #1e293b; color: #e2e8f0; }
        .recent-table tr:last-child td { border-bottom: none; }
        
        .badge-lang { background: rgba(56, 189, 248, 0.15); color: #38bdf8; padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: bold; border: 1px solid rgba(56, 189, 248, 0.3); }
        .code-snippet { font-family: 'Fira Code', monospace; color: #4ade80; background: #1e293b; padding: 4px 8px; border-radius: 6px; font-size: 13px; font-weight: 500; display: inline-block; direction: ltr; }
        
        #no-match-msg { display: none; padding: 20px; text-align: center; color: #64748b; font-size: 14px; font-weight: bold; }

        @media (max-width: 768px) {
            .wrapper { padding: 15px 10px; }
            .form-container { padding: 25px 15px; border-radius: 14px; }
            .form-control { padding: 12px 14px; font-size: 15px; } 
            .btn-submit { padding: 14px; font-size: 15px; }
            .recent-table th, .recent-table td { padding: 10px; font-size: 13px; }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="nav-container">
        <a href="../index.php" class="logo">🔑 Keyali Control Center</a>
        <ul class="nav-links">
            <li><a href="index.php">الرئيسية للوحة 🛠️</a></li>
            <li><a href="manage-all.php">إدارة المحتوى 📝</a></li>
        </ul>
    </div>
</nav>

<div class="wrapper">
    <div class="form-container">
        <div class="page-header">
            <h2>✏️ تعديل المصطلح والكود البرمجي (#<?php echo $id; ?>)</h2>
            <p>تحديث متن السطر البرمجي أو صياغة الوصف التوضيحي له لايف</p>
        </div>

        <?php echo $message; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="code_text">الكود البرمجي:</label>
                <textarea name="code_text" id="code_text" class="form-control textarea-control" required autocomplete="off"><?php echo htmlspecialchars($code_data['code_text']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="description">الوصف أو اسم المصطلح المرتبط:</label>
                <input type="text" name="description" id="description" class="form-control" value="<?php echo htmlspecialchars($code_data['description']); ?>" required autocomplete="off">
            </div>

            <button type="submit" class="btn-submit">تحديث وحفظ التغييرات الفورية 🚀</button>
        </form>

        <div class="recent-section">
            <div class="recent-title">
                <span id="title-text">📝 آخر 5 مصطلحات في بنك الأكواد (للمراجعة أثناء التعديل):</span>
            </div>
            
            <?php if(!empty($all_codes)): ?>
                <input type="text" id="table-search" class="mini-search" placeholder="ابحث هنا لمقارنة كودك الحالي مع كامل بنك الأكواد منعاً للتكرار... 🔎" autocomplete="off">
                
                <div class="table-responsive">
                    <table class="recent-table">
                        <thead>
                            <tr>
                                <th>اللغة</th>
                                <th>المصطلح / الوصف</th>
                                <th>السطر البرمجي</th>
                            </tr>
                        </thead>
                        <tbody id="recent-tbody">
                            <?php foreach($all_codes as $index => $rc): ?>
                                <tr class="code-row" data-index="<?php echo $index; ?>" style="<?php echo ($rc['id'] == $id) ? 'background: rgba(245, 158, 11, 0.05);' : ''; ?>">
                                    <td><span class="badge-lang target-lang"><?php echo htmlspecialchars($rc['lang_name']); ?></span></td>
                                    <td class="target-desc" style="font-weight: bold; color: #e2e8f0;"><?php echo htmlspecialchars($rc['description']); ?></td>
                                    <td><code class="code-snippet target-text"><?php echo htmlspecialchars($rc['code_text']); ?></code></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div id="no-match-msg">لا توجد أكواد مطابقة لهذا البحث، الصياغة الحالية فريدة تماماً! 🟢</div>
                </div>
            <?php endif; ?>
        </div>

        <div style="text-align: center; margin-top: 25px;">
            <a href="manage-all.php" style="color: #94a3b8; text-decoration: none; font-size: 14px;">❌ إلغاء وتراجع والعودة للخلف</a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tableSearch = document.getElementById('table-search');
    const titleText = document.getElementById('title-text');
    const rows = document.querySelectorAll('.code-row');
    const noMatchMsg = document.getElementById('no-match-msg');

    // تهيئة العرض الافتراضي: أول 5 صفوف فقط لراحة العين وسرعة الصفحة
    function showDefaultFive() {
        rows.forEach(row => {
            const index = parseInt(row.getAttribute('data-index'));
            if (index < 5) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
        if(titleText) titleText.textContent = "📝 آخر 5 مصطلحات في بنك الأكواد (للمراجعة أثناء التعديل):";
    }
    
    showDefaultFive();

    // تشغيل محرك الفلترة المتكامل لايف في كامل قاعدة البيانات عند الكتابة
    if (tableSearch) {
        tableSearch.addEventListener('input', function() {
            const query = this.value.trim().toLowerCase();
            
            if (query === '') {
                showDefaultFive();
                noMatchMsg.style.display = 'none';
                return;
            }

            let visibleRows = 0;
            titleText.textContent = "🔍 نتائج الفحص والمطابقة في كامل بنك الأكواد:";

            rows.forEach(row => {
                const lang = row.querySelector('.target-lang').textContent.toLowerCase();
                const desc = row.querySelector('.target-desc').textContent.toLowerCase();
                const code = row.querySelector('.target-text').textContent.toLowerCase();
                
                if (lang.includes(query) || desc.includes(query) || code.includes(query)) {
                    row.style.display = '';
                    visibleRows++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (visibleRows === 0) {
                noMatchMsg.style.display = 'block';
            } else {
                noMatchMsg.style.display = 'none';
            }
        });
    }
});
</script>

</body>
</html>
<?php 
ob_end_flush(); 
?>