<?php
// 1. تفعيل التخزين المؤقت لمنع أخطاء الـ Headers نهائياً
ob_start();

// 2. بدء الجلسة إذا لم تكن بدأت بعد لقراءة تفاصيل الـ session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. تضمين الاتصال بقاعدة البيانات
include 'config/db.php';

// 4. 🛡️ جدار الحماية: التحقق أولاً قبل طباعة أي HTML أو تضمين الهيدر
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=must_login");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_data = null;

try {
    // جلب بيانات وإحصائيات المستخدم الحالية
    $stmt = $pdo->prepare("SELECT username, email, highest_score, highest_streak, created_at FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    // في حال تم حذف الحساب من القاعدة وهو لا يزال يمتلك جلسة مفتوحة
    if (!$user_data) {
        session_destroy();
        header("Location: login.php");
        exit();
    }
} catch (PDOException $e) {
    die("خطأ في تحميل بيانات الملف الشخصي: " . $e->getMessage());
}

// 5. الآن وبعد الاطمئنان للحماية، ندرج الهيدر بأمان تام
include 'includes/header.php';
?>

<style>
    /* تحسينات متجاوبة وفخمة لبطاقة الملف الشخصي */
    .profile-container {
        max-width: 650px;
        margin: 60px auto;
        font-family: 'Tajawal', 'Segoe UI', sans-serif;
        text-align: center;
        padding: 0 20px;
        box-sizing: border-box;
    }

    .profile-card {
        background: #0f172a;
        border: 2px solid #1e293b;
        border-radius: 24px;
        padding: 40px 30px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6);
        position: relative;
        box-sizing: border-box;
    }

    .avatar-icon {
        font-size: 60px;
        background: #1e293b;
        width: 110px;
        height: 110px;
        line-height: 110px;
        border-radius: 50%;
        margin: 0 auto 20px;
        border: 3px solid var(--accent, #38bdf8);
        box-shadow: 0 0 20px rgba(56, 189, 248, 0.2);
    }

    /* شبكة مرنة ومستقرة تماماً للإحصائيات */
    .stats-grid {
        display: flex;
        justify-content: center;
        gap: 20px;
        margin: 35px 0;
        flex-wrap: wrap;
    }

    .stat-box {
        background: #020617;
        border: 1px solid #334155;
        padding: 20px;
        border-radius: 16px;
        min-width: 140px;
        flex: 1;
        transition: 0.3s ease;
        box-sizing: border-box;
    }

    .stat-box:hover {
        border-color: var(--accent, #38bdf8);
        transform: translateY(-3px);
    }

    .stat-num {
        font-size: clamp(24px, 4vw, 32px);
        font-weight: 900;
        display: block;
        margin-top: 8px;
    }

    .join-date {
        font-size: 14px;
        color: #64748b;
        margin-top: 25px;
        border-top: 1px solid #1e293b;
        padding-top: 15px;
    }

    .action-btn {
        background: var(--accent, #38bdf8);
        color: #000;
        font-weight: bold;
        padding: 14px 30px;
        border-radius: 10px;
        text-decoration: none;
        display: inline-block;
        transition: 0.2s;
        margin-top: 10px;
        width: 100%;
        max-width: 280px;
        box-sizing: border-box;
    }

    .action-btn:hover {
        background: #7dd3fc;
        transform: scale(1.03);
    }

    /* 📱 ميديا كويري لضمان تجربة فخمة على الشاشات الصغيرة */
    @media (max-width: 480px) {
        .profile-container {
            margin: 30px auto;
        }

        .profile-card {
            padding: 30px 15px;
        }

        .stats-grid {
            gap: 12px;
        }

        .stat-box {
            min-width: 100%;
        }

        /* فرد الصناديق لتملأ العرض كاملاً بنسق طولي جذاب */
        .action-btn {
            max-width: 100%;
        }
    }
</style>

<div class="profile-container">
    <div class="profile-card">
        <div class="avatar-icon">🥷</div>

        <h2>ملف المحارب: <?php echo htmlspecialchars($user_data['username']); ?></h2>
        <p style="color: #94a3b8; font-size: 15px;"><?php echo htmlspecialchars($user_data['email']); ?></p>

        <div class="stats-grid">
            <div class="stat-box">
                <span style="color: #f59e0b; font-size: 14px; font-weight: bold;">🏆 أعلى نقاط (PB)</span>
                <span class="stat-num" style="color: #4ade80;"><?php echo number_format($user_data['highest_score']); ?></span>
            </div>
            <div class="stat-box">
                <span style="color: #f59e0b; font-size: 14px; font-weight: bold;">🔥 أعلى متتالي (Streak)</span>
                <span class="stat-num" style="color: #ef4444;"><?php echo number_format($user_data['highest_streak']); ?></span>
            </div>
        </div>

        <a href="ranked-challenge.php" class="action-btn">دخول الحلبة وتحطيم أرقامك ⚔️</a>

        <div class="join-date">
            📅 تاريخ انضمامك لكتيبة العمالقة:
            <strong><?php echo date('Y-m-d', strtotime($user_data['created_at'])); ?></strong>
        </div>
    </div>
</div>

<?php
// إغلاق المخرجات وتضمين الفوتر بشكل سليم
ob_end_flush();
include 'includes/footer.php';
?>