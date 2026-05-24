<?php
// 1. 🔑 تشغيل الجلسة وحجز المخرجات فوراً في أول سطر بالملف لحمايتها من أخطاء الهيدر
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_use_only_cookies', 1);
    session_start();
}

// 🛡️ جدار حماية مدمج: فحص صلاحيات الدخول (OWASP: Broken Access Control)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['root', 'admin'])) {
    header("Location: ../login.php?error=forbidden");
    exit();
}

// 🔄 تجديد معرف الجلسة للأمان الرقمي التلقائي
session_regenerate_id(true);

// 2. 🔌 الاتصال بقاعدة البيانات
include '../config/db.php';

$message = "";

// 3. 💾 محرك استقبال ومعالجة البيانات عند الإرسال
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sentence     = trim($_POST['sentence']);
    $correct_word = trim($_POST['correct_word']);
    $translation  = trim($_POST['translation']);

    // التحقق من تعبئة كافة الحقول، وأن الجملة تحتوي على رمز الفراغ المطلق ___
    if (!empty($sentence) && !empty($correct_word) && !empty($translation)) {
        if (strpos($sentence, '___') !== false) {
            try {
                $stmt = $pdo->prepare("INSERT INTO english_challenges (sentence, correct_word, translation) VALUES (?, ?, ?)");
                if ($stmt->execute([$sentence, $correct_word, $translation])) {
                    $message = "<div class='alert success'>🎉 تم زرع وإضافة التحدي بنجاح إلى بنك الجمل يا شريكي!</div>";
                }
            } catch (PDOException $e) {
                $message = "<div class='alert error'>⚠️ خطأ في السيرفر: فشل إدخال البيانات بأمان.</div>";
            }
        } else {
            $message = "<div class='alert error'>❌ خطأ: يجب أن تحتوي الجملة على رمز الفراغ السري <code>___</code> لكي يعرف النظام مكان الحذف!</div>";
        }
    } else {
        $message = "<div class='alert error'>❌ يرجى ملء جميع الحقول المطلوبة أولاً!</div>";
    }
}

// 📊 جلب جميع التحديات والجمل من قاعدة البيانات بالكامل لفلترتها والتحقق منها لايف عبر الـ JS
$all_challenges = [];
try {
    $all_challenges = $pdo->query("SELECT * FROM english_challenges ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Failed to fetch english challenges: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة تحدي إنجليزي جديد 🇬🇧</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700;900&family=Fira+Code:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background: #020617;
            color: #fff;
            margin: 0;
            padding-bottom: 40px;
        }

        .navbar {
            background: #0f172a;
            border-bottom: 1px solid #1e293b;
            padding: 15px 20px;
        }

        .nav-container {
            max-width: 1100px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .logo {
            font-size: 18px;
            font-weight: bold;
            color: #fff;
            text-decoration: none;
        }

        .nav-links {
            margin: 0;
            padding: 0;
            list-style: none;
            display: flex;
            gap: 15px;
        }

        .nav-links a {
            text-decoration: none;
            font-size: 14px;
            color: #94a3b8;
            font-weight: bold;
            transition: color 0.2s;
        }

        .nav-links a:hover {
            color: #38bdf8;
        }

        .wrapper {
            padding: 30px 20px;
            box-sizing: border-box;
        }

        .form-container {
            max-width: 700px;
            margin: 0 auto;
            background: #0f172a;
            padding: 35px;
            border-radius: 20px;
            border: 1px solid #1e293b;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            box-sizing: border-box;
        }

        .page-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 1px solid #1e293b;
            padding-bottom: 20px;
        }

        .page-header h2 {
            font-size: clamp(18px, 4.5vw, 24px);
            margin: 0;
        }

        .page-header p {
            font-size: clamp(13px, 3.5vw, 15px);
            color: #94a3b8;
            margin-top: 8px;
        }

        .form-group {
            margin-bottom: 25px;
            text-align: right;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            color: #38bdf8;
            font-weight: bold;
            font-size: 15px;
        }

        .form-control {
            width: 100%;
            padding: 14px 18px;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 10px;
            color: #fff;
            font-size: 16px;
            box-sizing: border-box;
            transition: all 0.2s ease;
            -webkit-appearance: none;
        }

        .form-control:focus {
            border-color: #38bdf8;
            outline: none;
            box-shadow: 0 0 12px rgba(56, 189, 248, 0.15);
        }

        .textarea-control {
            resize: vertical;
            min-height: 100px;
            font-family: 'Fira Code', monospace;
            direction: ltr;
            text-align: left;
            line-height: 1.5;
        }

        .input-ltr {
            direction: ltr;
            text-align: left;
            font-family: 'Fira Code', monospace;
        }

        .btn-submit {
            width: 100%;
            padding: 16px;
            background: #4ade80;
            color: #020617;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s ease;
            box-sizing: border-box;
        }

        .btn-submit:hover {
            background: #22c55e;
        }

        .btn-submit:active {
            transform: scale(0.98);
        }

        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: bold;
            font-size: 15px;
            box-sizing: border-box;
        }

        .alert.success {
            background: rgba(74, 222, 128, 0.15);
            color: #4ade80;
            border: 1px solid rgba(74, 222, 128, 0.3);
        }

        .alert.error {
            background: rgba(248, 113, 113, 0.15);
            color: #f87171;
            border: 1px solid rgba(248, 113, 113, 0.3);
        }

        .note-card {
            background: rgba(245, 158, 11, 0.08);
            border: 1px dashed #f59e0b;
            padding: 15px;
            border-radius: 10px;
            color: #f59e0b;
            font-size: 14px;
            margin-bottom: 25px;
            line-height: 1.6;
            text-align: right;
            box-sizing: border-box;
        }

        /* ✨ قسم فحص الجمل الذكي المدمج (5 افتراضي / والكل عند البحث) */
        .recent-section {
            margin-top: 35px;
            border-top: 1px solid #1e293b;
            padding-top: 25px;
        }

        .recent-title {
            font-size: 16px;
            font-weight: bold;
            color: #38bdf8;
            margin-bottom: 15px;
        }

        .mini-search {
            width: 100%;
            padding: 14px 15px;
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 10px;
            color: #fff;
            font-size: 14px;
            margin-bottom: 15px;
            outline: none;
            box-sizing: border-box;
            font-family: inherit;
            transition: 0.2s;
        }

        .mini-search:focus {
            border-color: #38bdf8;
            box-shadow: 0 0 12px rgba(56, 189, 248, 0.15);
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            border-radius: 10px;
            border: 1px solid #1e293b;
            background: #0f172a;
            max-height: 350px;
            overflow-y: auto;
        }

        .recent-table {
            width: 100%;
            border-collapse: collapse;
            text-align: right;
            font-size: 14px;
        }

        .recent-table th {
            background: #1e293b;
            color: #94a3b8;
            padding: 12px;
            font-weight: bold;
            border-bottom: 1px solid #1e293b;
            position: sticky;
            top: 0;
            z-index: 5;
        }

        .recent-table td {
            padding: 12px;
            border-bottom: 1px solid #1e293b;
            color: #e2e8f0;
        }

        .recent-table tr:last-child td {
            border-bottom: none;
        }

        .badge-word {
            background: rgba(74, 222, 128, 0.15);
            color: #4ade80;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 13px;
            font-family: 'Fira Code', monospace;
            font-weight: bold;
            border: 1px solid rgba(74, 222, 128, 0.3);
        }

        .text-sentence {
            font-family: 'Fira Code', monospace;
            direction: ltr;
            text-align: left;
            color: #38bdf8;
            font-size: 13px;
            display: inline-block;
        }

        #no-match-msg {
            display: none;
            padding: 20px;
            text-align: center;
            color: #64748b;
            font-size: 14px;
            font-weight: bold;
        }

        @media (max-width: 768px) {
            .nav-container {
                flex-direction: column;
                text-align: center;
                gap: 8px;
            }

            .wrapper {
                padding: 15px 10px;
            }

            .form-container {
                padding: 25px 15px;
                border-radius: 14px;
            }

            .form-control {
                padding: 12px 14px;
                font-size: 15px;
            }

            .btn-submit {
                padding: 14px;
                font-size: 15px;
            }

            .note-card {
                font-size: 13px;
                padding: 12px;
            }

            .recent-table th,
            .recent-table td {
                padding: 10px;
                font-size: 13px;
            }
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
                <h2>🇬🇧 إضافة تحدي جمل جاهزة جديد</h2>
                <p>تغذية جدول تحديات اللغة الإنجليزية لتظهر عشوائياً وبقوة للمستخدمين</p>
            </div>

            <?php echo $message; ?>

            <div class="note-card">
                <strong>💡 ملاحظة ذهبية لشريكي:</strong> عند كتابة الجملة الإنجليزية، ضع ثلاث شرطات سفلية متصلة هكذا <code>___</code> مكان الكلمة التي تريد من المستخدم تخمينها وكتابتها.
                <br><em>مثال: He wants to ___ a professional software engineer.</em>
            </div>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="sentence">الجملة الإنجليزية (مع الفراغ السري):</label>
                    <textarea name="sentence" id="sentence" class="form-control textarea-control" placeholder="He wants to ___ a professional software engineer." required autocomplete="off"></textarea>
                </div>

                <div class="form-group">
                    <label for="correct_word">الكلمة الصحيحة المحذوفة (المطلوب كتابتها):</label>
                    <input type="text" name="correct_word" id="correct_word" class="form-control input-ltr" placeholder="become" required autocomplete="off">
                </div>

                <div class="form-group">
                    <label for="translation">الترجمة العربية المساعدة:</label>
                    <input type="text" name="translation" id="translation" class="form-control" placeholder="هو يريد أن يصبح مهندس برمجيات محترف." required autocomplete="off">
                </div>

                <button type="submit" class="btn-submit">زرع التحدي في بنك الجمل 🚀</button>
            </form>

            <div class="recent-section">
                <div class="recent-title">
                    <span id="title-text">📝 آخر 5 تحديات تم زرعها مؤخراً:</span>
                </div>

                <?php if (!empty($all_challenges)): ?>
                    <input type="text" id="table-search" class="mini-search" placeholder="ابحث هنا لتفادي التكرار وفحص بنك الجمل بالكامل لايف... 🔎" autocomplete="off">

                    <div class="table-responsive">
                        <table class="recent-table">
                            <thead>
                                <tr>
                                    <th>الجملة الإنجليزية</th>
                                    <th>الكلمة المستهدفة</th>
                                    <th>الترجمة المساعدة</th>
                                </tr>
                            </thead>
                            <tbody id="recent-tbody">
                                <?php foreach ($all_challenges as $index => $rc): ?>
                                    <tr class="challenge-row" data-index="<?php echo $index; ?>">
                                        <td><span class="text-sentence target-sentence"><?php echo htmlspecialchars($rc['sentence']); ?></span></td>
                                        <td><span class="badge-word target-word"><?php echo htmlspecialchars($rc['correct_word']); ?></span></td>
                                        <td class="target-trans" style="color: #94a3b8; font-weight: bold;"><?php echo htmlspecialchars($rc['translation']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div id="no-match-msg">هذا التحدي غير مكرر، يمكنك زرعه وإضافته بأمان! 🟢</div>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <div style="padding: 20px; text-align: center; color: #64748b; font-size: 13px;">لم يتم زرع أي تحديات في قاعدة البيانات بعد.</div>
                    </div>
                <?php endif; ?>
            </div>

            <div style="text-align: center; margin-top: 25px;">
                <a href="index.php" style="color: #94a3b8; text-decoration: none; font-size: 14px;">⬅️ العودة للوحة التحكم الرئيسية</a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tableSearch = document.getElementById('table-search');
            const titleText = document.getElementById('title-text');
            const rows = document.querySelectorAll('.challenge-row');
            const noMatchMsg = document.getElementById('no-match-msg');

            // هندسة العرض الافتراضي: أول 5 تحديات فقط عند تحميل السيرفر للملف
            function showDefaultFive() {
                rows.forEach(row => {
                    const index = parseInt(row.getAttribute('data-index'));
                    if (index < 5) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
                if (titleText) titleText.textContent = "📝 آخر 5 تحديات تم زرعها مؤخراً:";
            }

            showDefaultFive();

            // تشغيل محرك الفلترة لايف والتمدد في كامل بنك الجمل عند إدخال حروف بالبحث
            if (tableSearch) {
                tableSearch.addEventListener('input', function() {
                    const query = this.value.trim().toLowerCase();

                    if (query === '') {
                        showDefaultFive();
                        noMatchMsg.style.display = 'none';
                        return;
                    }

                    let visibleRows = 0;
                    titleText.textContent = "🔍 نتائج الفحص والبحث في كامل بنك التحديات:";

                    rows.forEach(row => {
                        const sentence = row.querySelector('.target-sentence').textContent.toLowerCase();
                        const word = row.querySelector('.target-word').textContent.toLowerCase();
                        const trans = row.querySelector('.target-trans').textContent.toLowerCase();

                        if (sentence.includes(query) || word.includes(query) || trans.includes(query)) {
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