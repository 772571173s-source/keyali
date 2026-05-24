<?php
// 1. 🔑 تشغيل الجلسة فوراً وتأمين الحزم الهيدرية (OWASP)
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. 🛡️ جدار حماية مدمج: فحص صلاحيات الدخول (OWASP: Broken Access Control)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['root', 'admin'])) {
    header("Location: ../login.php?error=forbidden");
    exit();
}

// 3. 🔌 تضمين ملف الاتصال بقاعدة البيانات
include '../config/db.php';

// 🛠️ محرك الترقية والإقالة (خاص بالـ Root فقط)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['target_user_id'])) {

    // حماية قصوى: منع المشرف العادي من محاولة حقن طلبات ترقية
    if ($_SESSION['role'] !== 'root') {
        die("خطأ أمني: لا تملك صلاحية الروت المطلقة لتغيير رتب المستخدمين!");
    }

    $target_id = intval($_POST['target_user_id']);
    $action = $_POST['action'];
    $new_role = ($action === 'promote') ? 'admin' : 'user';

    try {
        // تحديث الرتبة في قاعدة البيانات مع حماية حساب الروت من التعديل
        $update_stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ? AND role != 'root'");
        $update_stmt->execute([$new_role, $target_id]);

        header("Location: admin-users.php?success=1");
        exit();
    } catch (PDOException $e) {
        die("فشلت العملية. تم حجب تفاصيل الخطأ للأمان.");
    }
}

// 4. جلب المستخدمين (مع إخفاء حسابك الحالي من القائمة لكي لا تعدل على نفسك بالخطأ)
try {
    $stmt = $pdo->prepare("SELECT id, username, email, password, role FROM users WHERE id != ? ORDER BY id DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("خطأ في جلب البيانات من السيرفر.");
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مركز إدارة الحسابات والصلاحيات ⚙️</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700;900&family=Fira+Code:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background: #020617;
            color: #fff;
            margin: 0;
            padding-bottom: 20px;
        }

        /* شريط تنقل متجاوب للموبايل */
        .navbar {
            background: #0f172a;
            border-bottom: 1px solid #1e293b;
            padding: 15px 20px;
        }

        .nav-container {
            max-width: 1200px;
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
        }

        .nav-links a:hover {
            color: #38bdf8;
        }

        .admin-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
            box-sizing: border-box;
        }

        .header-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .admin-title {
            color: #fff;
            font-weight: bold;
            margin: 0;
            font-size: clamp(20px, 4.5vw, 26px);
        }

        .your-role-badge {
            background: #0f172a;
            border: 1px solid #38bdf8;
            padding: 8px 16px;
            border-radius: 8px;
            color: #38bdf8;
            font-weight: bold;
            font-size: 14px;
        }

        .users-table-wrapper {
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            margin-top: 20px;
            padding: 10px;
            box-sizing: border-box;
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
            text-align: right;
            color: #f8fafc;
        }

        .users-table th {
            background: #1e293b;
            padding: 15px;
            color: #38bdf8;
            font-weight: bold;
            font-size: 15px;
            border-bottom: 2px solid #334155;
        }

        .users-table td {
            padding: 15px;
            border-bottom: 1px solid #1e293b;
            font-size: 14px;
            vertical-align: middle;
            color: #cbd5e1;
        }

        .users-table tr:hover {
            background: rgba(56, 189, 248, 0.02);
        }

        .role-badge {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }

        .role-badge.root {
            background: rgba(168, 85, 247, 0.15);
            color: #c084fc;
            border: 1px solid #a855f7;
        }

        .role-badge.admin {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
            border: 1px solid #ef4444;
        }

        .role-badge.user {
            background: rgba(74, 222, 128, 0.15);
            color: #4ade80;
            border: 1px solid #4ade80;
        }

        /* تنسيق خط كلمة السر للروت */
        .pass-text {
            font-family: 'Fira Code', monospace;
            color: #f43f5e;
            font-size: 13px;
            background: #1e293b;
            padding: 4px 8px;
            border-radius: 6px;
            word-break: break-all;
        }

        .action-btn {
            font-family: 'Tajawal', sans-serif;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: bold;
            border: none;
            cursor: pointer;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            width: 100%;
            justify-content: center;
            box-sizing: border-box;
        }

        .action-btn.promote {
            background-color: #e11d48;
            color: #fff;
        }

        .action-btn.promote:hover {
            background-color: #be123c;
        }

        .action-btn.demote {
            background-color: #475569;
            color: #fff;
        }

        .action-btn.demote:hover {
            background-color: #334155;
        }

        .action-locked {
            color: #64748b;
            font-size: 13px;
            font-style: italic;
            display: block;
        }

        /* 📱 التوافق الكامل مع الموبايل */
        @media (max-width: 768px) {
            .nav-container {
                flex-direction: column;
                text-align: center;
                gap: 8px;
            }

            .admin-container {
                padding: 20px 10px;
                margin: 10px auto;
            }

            .header-box {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .your-role-badge {
                width: 100%;
                box-sizing: border-box;
                text-align: center;
            }

            .users-table-wrapper {
                padding: 8px;
                border-radius: 14px;
            }

            .users-table,
            .users-table thead,
            .users-table tbody,
            .users-table th,
            .users-table td,
            .users-table tr {
                display: block;
            }

            .users-table thead {
                display: none;
            }

            .users-table tr {
                background: #1e293b;
                margin-bottom: 15px;
                border-radius: 12px;
                padding: 15px;
                border: 1px solid #334155;
            }

            /* التحكم في الهوامش الجانبية لعناوين الحقول في الجوال */
            .users-table td {
                text-align: right;
                padding: 10px 0;
                position: relative;
                padding-right: 120px;
                border-bottom: 1px solid rgba(51, 65, 85, 0.4);
                font-size: 14px;
                min-height: 25px;
            }

            .users-table td:last-child {
                border-bottom: none;
            }

            .users-table td::before {
                position: absolute;
                right: 0;
                top: 10px;
                color: #38bdf8;
                font-weight: bold;
                font-size: 13px;
                width: 110px;
            }

            /* حقن العناوين برمجياً وديناميكياً للموبايل */
            .users-table tr td.cell-id::before {
                content: "المعرف:";
            }

            .users-table tr td.cell-username::before {
                content: "اسم المستخدم:";
            }

            .users-table tr td.cell-email::before {
                content: "الإيميل:";
            }

            .users-table tr td.cell-password::before {
                content: "كلمة السر:";
            }

            .users-table tr td.cell-role::before {
                content: "الرتبة:";
            }

            .users-table tr td.cell-actions::before {
                content: "التحكم:";
            }

            .action-btn {
                width: 100%;
                margin-top: 5px;
            }
        }
    </style>
</head>

<body>

    <nav class="navbar">
        <div class="nav-container">
            <a href="../index.php" class="logo">🔑 Keyali Admin</a>
            <ul class="nav-links">
                <li><a href="index.php">الرئيسية للوحة 🛠️</a></li>
                <li><a href="../index.php">الموقع الرئيسي 🏠</a></li>
            </ul>
        </div>
    </nav>

    <div class="admin-container">
        <div class="header-box">
            <div>
                <h2 class="admin-title">👥 مركز إدارة الحسابات والصلاحيات</h2>
                <p style="color: #94a3b8; margin-top: 5px; margin-bottom: 0;">مراقبة الحسابات الحساسة، التحكم بالصلاحيات ورؤية كلمات السر حسب رتبتك.</p>
            </div>
            <div class="your-role-badge">
                👤 رتبتك الحالية في النظام:
                <strong><?php echo ($_SESSION['role'] === 'root') ? '👑 الروت (صلاحيات مطلقة)' : '🛠️ مشرف / أدمين (صلاحيات محدودة)'; ?></strong>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div style="background: rgba(74, 222, 128, 0.15); color: #4ade80; border: 1px solid #4ade80; padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: bold;">
                ✓ تم تحديث رتبة المستخدم بنجاح في النظام!
            </div>
        <?php endif; ?>

        <div class="users-table-wrapper">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>اسم المستخدم</th>
                        <th>البريد الإلكتروني</th>
                        <?php if ($_SESSION['role'] === 'root'): ?>
                            <th>كلمة المرور</th>
                        <?php endif; ?>
                        <th>الرتبة</th>
                        <th>صلاحية التحكم والترقية</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_users as $user): ?>
                        <tr>
                            <td class="cell-id"><strong>#<?php echo htmlspecialchars($user['id']); ?></strong></td>
                            <td class="cell-username" style="font-weight: bold; color: #fff;"><?php echo htmlspecialchars($user['username']); ?></td>
                            <td class="cell-email"><?php echo htmlspecialchars($user['email']); ?></td>

                            <?php if ($_SESSION['role'] === 'root'): ?>
                                <td class="cell-password">
                                    <span class="pass-text"><?php echo htmlspecialchars($user['password']); ?></span>
                                </td>
                            <?php endif; ?>

                            <td class="cell-role">
                                <?php if ($user['role'] === 'root'): ?>
                                    <span class="role-badge root">👑 روت / المالك</span>
                                <?php elseif ($user['role'] === 'admin'): ?>
                                    <span class="role-badge admin">🛠️ أدمين / مشرف</span>
                                <?php else: ?>
                                    <span class="role-badge user">👤 مستخدم عادي</span>
                                <?php endif; ?>
                            </td>
                            <td class="cell-actions">
                                <?php if ($_SESSION['role'] === 'root'): ?>
                                    <form method="POST" style="margin: 0; display: inline-block; width: 100%;">
                                        <input type="hidden" name="target_user_id" value="<?php echo $user['id']; ?>">

                                        <?php if ($user['role'] === 'user'): ?>
                                            <button type="submit" name="action" value="promote" class="action-btn promote">⚡ ترقية إلى أدمين</button>
                                        <?php elseif ($user['role'] === 'admin'): ?>
                                            <button type="submit" name="action" value="demote" class="action-btn demote">📉 إنزال إلى مستخدم</button>
                                        <?php else: ?>
                                            <span class="action-locked">🔒 لا يمكن تعديل روت آخر</span>
                                        <?php endif; ?>
                                    </form>
                                <?php else: ?>
                                    <span class="action-locked">🚫 ميزة الترقية مغلقة (خاصة بالروت)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>

</html>
<?php ob_end_flush(); ?>