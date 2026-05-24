<?php
// 1. 🔑 تشغيل الجلسة وحجز المخرجات فوراً في أول السيرفر لمنع أخطاء الهيدر والتحويل
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. 🔌 تضمين ملف الاتصال بقاعدة البيانات
include 'config/db.php';

// 🛡️ توجيه ذكي إذا كان المستخدم مسجلاً دخول مسبقاً بناءً على رتبته الفعالية
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if (in_array($_SESSION['role'], ['root', 'admin'])) {
        header("Location: admin/index.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_or_email = trim($_POST['username_or_email']);
    $password = $_POST['password'];

    if (empty($username_or_email) || empty($password)) {
        $error = "يرجى كتابة اسم المستخدم/الإيميل وكلمة المرور! 🛠️";
    } else {
        try {
            // البحث عن المستخدم بالاسم أو الإيميل
            $stmt = $pdo->prepare("SELECT id, username, email, password, role FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username_or_email, $username_or_email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // 🔐 فحص ذكي: يدعم التحقق من النصوص العادية (التي عدلتها بيدك) أو الهاشات المشفرة بـ Bcrypt معاً
                $is_valid = false;
                if (password_verify($password, $user['password']) || $password === $user['password']) {
                    $is_valid = true;
                }

                if ($is_valid) {
                    // تسجيل بيانات المستخدم ورتبته الحقيقية في السيرفر (Session)
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];

                    // تجديد معرف الجلسة لمنع هجمات الـ Session Fixation المخيفة
                    session_regenerate_id(true);

                    // 🚦 التوجيه الديناميكي والذكي بعد تسجيل الدخول الناجح
                    if (in_array($user['role'], ['root', 'admin'])) {
                        header("Location: admin/index.php");
                    } else {
                        header("Location: index.php");
                    }
                    exit();
                } else {
                    $error = "بيانات الدخول غير صحيحة، ركز يا محارب! ❌";
                }
            } else {
                $error = "بيانات الدخول غير صحيحة، ركز يا محارب! ❌";
            }
        } catch (PDOException $e) {
            $error = "حدث خطأ غير متوقع في السيرفر، يرجى المحاولة لاحقاً.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بوابة تسجيل دخول العمالقة 🔑</title>
    <style>
        body {
            background: #020617;
            color: #fff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 15px;
            box-sizing: border-box;
        }

        /* كارت تسجيل الدخول المتوافق كلياً مع الموبايل والشاشات الكبيرة */
        .auth-card {
            background: #0f172a;
            border: 2px solid #1e293b;
            padding: 35px 25px;
            border-radius: 24px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6);
            text-align: center;
            box-sizing: border-box;
        }

        .auth-card h2 {
            font-size: clamp(22px, 6vw, 28px);
            margin-top: 0;
            margin-bottom: 10px;
            color: #fff;
        }

        .auth-input {
            width: 100%;
            padding: 14px 20px;
            margin: 10px 0;
            box-sizing: border-box;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            color: #fff;
            font-size: 16px;
            outline: none;
            transition: 0.2s;
            text-align: center;
            font-family: inherit;
        }

        .auth-input:focus {
            border-color: #38bdf8;
            box-shadow: 0 0 15px rgba(56, 189, 248, 0.2);
        }

        .auth-btn {
            background: #38bdf8;
            color: #020617;
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
            margin-top: 15px;
            font-family: inherit;
        }

        .auth-btn:hover {
            background: #7dd3fc;
        }

        .auth-btn:active {
            transform: scale(0.98);
        }

        /* التنبيهات والأخطاء */
        .alert {
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: bold;
            font-size: 14px;
            background: rgba(239, 68, 68, 0.1);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
            line-height: 1.5;
        }

        a {
            color: #38bdf8;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
        }

        a:hover {
            text-decoration: underline;
        }

        /* تحسينات إضافية دقيقة جداً للهواتف الصغيرة */
        @media (max-width: 400px) {
            .auth-card {
                padding: 25px 15px;
                border-radius: 20px;
            }

            .auth-input {
                padding: 12px;
                font-size: 15px;
            }

            .auth-btn {
                padding: 12px;
                font-size: 15px;
            }
        }
    </style>
</head>

<body>

    <div class="auth-card">
        <h2>بوابة العمالقة 🔑</h2>
        <p style="color: #94a3b8; font-size: 14px; margin-bottom: 25px; line-height: 1.4;">سجل دخولك الآن واستعد لسحق الأرقام القياسية!</p>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'forbidden'): ?>
            <div class="alert">🛑 المنطقة مخصصة للروت والمشرفين فقط يا شريكي!</div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="text" name="username_or_email" class="auth-input" placeholder="اسم المستخدم أو البريد الإلكتروني" required autocomplete="off">
            <input type="password" name="password" class="auth-input" placeholder="كلمة المرور" required>

            <button type="submit" class="auth-btn">تسجيل الدخول وعودة للميدان ⚔️</button>
        </form>

        <p style="margin-top: 25px; font-size: 14px; color: #64748b;">
            ليس لديك حساب؟ <a href="register.php">أنشئ حسابك من هنا</a>
        </p>
    </div>

</body>

</html>
<?php
ob_end_flush();
?>