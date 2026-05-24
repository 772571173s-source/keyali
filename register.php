<?php
include 'config/db.php';
session_start();

// إذا كان المستخدم مسجل دخول بالفعل، ينقلب فوراً لصالة التحدي
if (isset($_SESSION['user_id'])) {
    header("Location: ranked-challenge.php");
    exit();
}

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($username) || empty($email) || empty($password)) {
        $error = "يا شريكي، لا تترك حقولاً فارغة! 🛠️";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "صيغة البريد الإلكتروني غير صحيحة! ✉️";
    } else {
        try {
            // التحقق من تكرار اسم المستخدم أو الإيميل
            $check_stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $check_stmt->execute([$username, $email]);

            if ($check_stmt->rowCount() > 0) {
                $error = "الاسم أو البريد مسجل مسبقاً! اختر هوية أخرى للمعركة. ⚔️";
            } else {
                // 🔐 تشفير كلمة المرور بأحدث وأقوى خوارزميات التشفير القياسية
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);

                // إدخال المحارب الجديد في الداتا بيز
                $insert_stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                $insert_stmt->execute([$username, $email, $hashed_password]);

                $success = "تم إنشاء حسابك الأسطوري بنجاح! جاري تحويلك لبوابة الدخول... 🚀";
                echo "<meta http-equiv='refresh' content='2;url=login.php'>";
            }
        } catch (PDOException $e) {
            $error = "خطأ غير متوقع في النظام: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>الانضمام لكتيبة العمالقة ⚔️</title>
    <style>
        body {
            background: #020617;
            color: #fff;
            font-family: 'Segoe UI', Tahoma, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .auth-card {
            background: #0f172a;
            border: 2px solid #334155;
            padding: 40px;
            border-radius: 20px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.5);
            text-align: center;
        }

        .auth-input {
            width: 100%;
            padding: 12px 20px;
            margin: 12px 0;
            box-sizing: border-box;
            background: #1e293b;
            border: 1px solid #475569;
            border-radius: 10px;
            color: #fff;
            font-size: 16px;
            outline: none;
            transition: 0.3s;
            text-align: center;
        }

        .auth-input:focus {
            border-color: #38bdf8;
            box-shadow: 0 0 15px rgba(56, 189, 248, 0.3);
        }

        .auth-btn {
            background: #38bdf8;
            color: #000;
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.2s;
            margin-top: 15px;
        }

        .auth-btn:hover {
            background: #7dd3fc;
            transform: scale(1.02);
        }

        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-weight: bold;
            font-size: 14px;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
            border: 1px solid #ef4444;
        }

        .alert-success {
            background: rgba(74, 222, 128, 0.2);
            color: #4ade80;
            border: 1px solid #4ade80;
        }

        a {
            color: #38bdf8;
            text-decoration: none;
            font-size: 14px;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <div class="auth-card">
        <h2>انضم لكتيبة العمالقة ⚔️</h2>
        <p style="color: #94a3b8; font-size: 14px; margin-bottom: 25px;">أنشئ حسابك الآن وسجل أرقامك في جدار الصدارة العالمي!</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="text" name="username" class="auth-input" placeholder="اسم المحارب (Username)" required autocomplete="off">
            <input type="email" name="email" class="auth-input" placeholder="البريد الإلكتروني" required autocomplete="off">
            <input type="password" name="password" class="auth-input" placeholder="كلمة المرور القوية" required>

            <button type="submit" class="auth-btn">إنشاء الحساب وبدء المجد 🚀</button>
        </form>

        <p style="margin-top: 20px; font-size: 14px; color: #64748b;">
            لديك حساب بالفعل؟ <a href="login.php">تسجيل الدخول من هنا</a>
        </p>
    </div>

</body>

</html>