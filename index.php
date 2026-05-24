<?php

// 1. تضمين ملف الاتصال بقاعدة البيانات لجلب إحصائيات حماسية
include 'config/db.php';
// 2. تضمين الهيدر للناف بار المشترك
include 'includes/header.php';

// جلب أرقام وإحصائيات حية لبث الحماس في قلوب الزوار
try {
    // جلب إجمالي اللاعبين المسجلين
    $count_stmt = $pdo->query("SELECT COUNT(id) AS total_users FROM users");
    $total_users = $count_stmt->fetch(PDO::FETCH_ASSOC)['total_users'];

    // جلب أعلى سكور سُجّل في تاريخ المنصة واسم البطل صاحبه
    $top_stmt = $pdo->query("SELECT username, highest_score FROM users WHERE highest_score > 0 ORDER BY highest_score DESC LIMIT 1");
    $top_hero = $top_stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // قيم افتراضية في حال كانت الجداول فارغة
    $total_users = 0;
    $top_hero = false;
}
?>

<style>
    /* تحسينات فخمة مخصصة لصفحة البداية تضمن التكيف مع كل الشاشات */
    .hero-section {
        max-width: 1100px;
        margin: 40px auto;
        padding: 0 20px;
        text-align: center;
        font-family: 'Tajawal', sans-serif;
        box-sizing: border-box;
    }

    .hero-section h1 {
        font-size: clamp(28px, 5vw, 42px);
        margin-bottom: 15px;
        color: #fff;
        font-weight: 800;
    }

    .hero-section p {
        color: #94a3b8;
        font-size: clamp(15px, 3vw, 18px);
        max-width: 700px;
        margin: 0 auto 35px;
        line-height: 1.6;
    }

    /* لوحة الإحصائيات الحية للموقع - مرنة ومحاذية بشكل فخم */
    .live-stats-bar {
        display: flex;
        justify-content: center;
        gap: 40px;
        margin-bottom: 40px;
        flex-wrap: wrap;
        background: rgba(15, 23, 42, 0.6);
        padding: 20px;
        border-radius: 16px;
        border: 1px dashed #334155;
        box-sizing: border-box;
    }

    .stat-item {
        text-align: center;
        min-width: 200px;
    }

    .stat-item .stat-val {
        display: block;
        font-size: clamp(22px, 4vw, 28px);
        font-weight: 900;
        color: #38bdf8;
    }

    .stat-item .stat-lbl {
        font-size: 14px;
        color: #64748b;
        margin-top: 5px;
        display: block;
    }

    /* شبكة البطاقات المطورة والمستقرة على الجوال والكمبيوتر */
    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(290px, 1fr));
        gap: 25px;
        margin-top: 30px;
        box-sizing: border-box;
    }

    .card {
        background: #0f172a;
        border: 2px solid #1e293b;
        border-radius: 20px;
        padding: 30px;
        text-align: right;
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        position: relative;
        overflow: hidden;
        box-sizing: border-box;
    }

    .card:hover {
        transform: translateY(-5px);
        border-color: var(--accent, #38bdf8);
        box-shadow: 0 10px 25px rgba(56, 189, 248, 0.15);
    }

    .card h3 {
        font-size: 21px;
        color: #fff;
        margin-top: 0;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .card p {
        color: #64748b;
        font-size: 14.5px;
        text-align: right;
        margin: 0 0 25px 0;
        line-height: 1.6;
        flex-grow: 1;
    }

    /* هندسة الأزرار داخل البطاقات لتبدو متناسقة وموحدة الطول */
    .card .btn {
        background: #1e293b;
        color: #fff;
        border: 1px solid #475569;
        padding: 12px 20px;
        border-radius: 10px;
        font-weight: bold;
        text-decoration: none;
        text-align: center;
        transition: 0.2s;
        margin-top: auto;
        display: block;
        width: 100%;
        box-sizing: border-box;
    }

    .card:hover .btn {
        background: var(--accent, #38bdf8);
        color: #000;
        border-color: var(--accent, #38bdf8);
        box-shadow: 0 0 15px rgba(56, 189, 248, 0.3);
    }

    /* تمييز خاص لبطاقة الطور التنافسي */
    .card.ranked-special {
        border-color: #f59e0b;
        background: linear-gradient(145deg, #0f172a, #1e1b4b);
    }

    .card.ranked-special:hover {
        box-shadow: 0 10px 25px rgba(245, 158, 11, 0.25);
        border-color: #fbbf24;
    }

    .card.ranked-special .btn {
        background: #f59e0b;
        color: #000;
        border: none;
        box-shadow: 0 0 10px rgba(245, 158, 11, 0.2);
    }

    .card.ranked-special .btn:hover {
        background: #fbbf24;
    }

    /* 🔮 تمييز فخم باللون البنفسجي لطور الشبح (Ghost Mode) */
    .card.ghost-special {
        border-color: #7c3aed;
        background: linear-gradient(145deg, #090514, #1e1145);
    }

    .card.ghost-special:hover {
        border-color: #a78bfa;
        box-shadow: 0 10px 30px rgba(124, 58, 237, 0.4);
    }

    .card.ghost-special h3 {
        color: #d8b4fe;
    }

    .card.ghost-special .btn {
        background: linear-gradient(90deg, #7c3aed, #4c1d95);
        color: #fff;
        border: 1px solid #a78bfa;
        box-shadow: 0 0 10px rgba(124, 58, 237, 0.3);
    }

    .card.ghost-special .btn:hover {
        background: linear-gradient(90deg, #a78bfa, #7c3aed);
        color: #000;
        box-shadow: 0 0 20px rgba(167, 139, 250, 0.6);
    }

    .card.ghost-special .card-badge {
        background: #8b5cf6;
        box-shadow: 0 0 10px rgba(139, 92, 246, 0.6);
    }

    /* شارة حارة مخصصة داخل البطاقة */
    .card-badge {
        position: absolute;
        top: 15px;
        left: 15px;
        background: #ef4444;
        color: #fff;
        font-size: 11px;
        padding: 3px 8px;
        border-radius: 20px;
        font-weight: bold;
        box-shadow: 0 0 8px rgba(239, 68, 68, 0.5);
    }

    /* 📱 ميديا كويري ذكية ومضغوطة للهواتف وشاشات اللمس الصغيرة */
    @media (max-width: 768px) {
        .hero-section {
            margin: 20px auto;
            padding: 0 12px;
        }

        .live-stats-bar {
            gap: 15px;
            padding: 15px 10px;
            margin-bottom: 25px;
        }

        .stat-item {
            min-width: 100%;
            padding: 0 !important;
            border: none !important;
        }

        .stat-item:not(:last-child) {
            border-bottom: 1px dashed #334155 !important;
            padding-bottom: 15px !important;
        }

        .features-grid {
            grid-template-columns: 1fr;
            gap: 15px;
        }

        .card {
            padding: 20px 15px;
            border-radius: 16px;
        }

        .card p {
            margin-bottom: 20px;
            font-size: 14px;
        }

        .card .btn {
            padding: 14px 15px;
            font-size: 16px;
        }

        /* تكبير مساحة اللمس للزر لراحة الأصابع */

    }
</style>

<div class="hero-section">
    <h1>مرحباً بك ياشريكي في منصة <span class="highlight">Keyali</span></h1>
    <p>بوابتك واختيارك والشريك الأول لتطوير سرعتك الخارقة في كتابة الكلمات البرمجية والإنجليزية عبر تحديات وأنماط تفاعلية مشعلة للحماس ومصممة لكسر الملل <a href="register.php">انضم</a> لنا الان لكي نشاركك تقدمك 🔥</p>

    <div class="live-stats-bar">
        <div class="stat-item">
            <span class="stat-val">👥 <?php echo number_format($total_users); ?></span>
            <span class="stat-lbl">شركاء المنصة</span>
        </div>
        <div class="stat-item" style="border-right: 1px solid #334155; padding-right: 40px;">
            <span class="stat-val" style="color: #f59e0b;">
                🏆 <?php echo $top_hero ? $top_hero['highest_score'] . " نقطة" : "لا يوجد بعد"; ?>
            </span>
            <span class="stat-lbl">
                أعلى رقم قياسي بالمنصة
                <?php if ($top_hero) echo "(للبطل: " . htmlspecialchars($top_hero['username']) . ")"; ?>
            </span>
        </div>
    </div>

    <div class="features-grid">

        <div class="card ghost-special">
            <span class="card-badge">✨ NEW</span>
            <h3>👻 طور الشبح (Ghost Mode)</h3>
            <p>تحدي الـ دقيقتين المرعب! احفظ الكلمة في 5 ثوانٍ قبل أن تتلاشى كلياً، ثم اكتبها غيباً وبسرعة خاطفة قبل انتهاء الوقت لتجميع أرقام قياسية مذهلة.</p>
            <a href="ghost.php" class="btn">مواجهة الأشباح ⏱️💀</a>
        </div>

        <div class="card ranked-special">
            <span class="card-badge">🔥 HOT</span>
            <h3>🏆 الطور التنافسي (Ranked)</h3>
            <p>تحدي الـ 60 ثانية المرعب! اكتب بسرعة فائقة، اجمع مضاعفات النقاط (Multiplier)، وحطم الأرقام لتخليد اسمك في جدار العمالقة العالمي.</p>
            <a href="ranked-challenge.php" class="btn">دخول الحلبة الصامتة ⚔️</a>
        </div>
        <!-- 💡 بطاقة مختبر القواعد الذكي -->
        <div class="card grammar-special">

            <span class="card-badge">💡 NEW</span>

            <h3>🧠 مختبر القواعد الذكي</h3>

            <p>
                رتب الكلمات المبعثرة، ابنِ الجملة الصحيحة،
                واستخدم التلميحات الذكية لتطوير فهمك الحقيقي
                للقواعد الإنجليزية بطريقة تفاعلية ممتعة
                تشبه الألعاب الحديثة.
            </p>

            <a href="grammar_lab.php" class="btn">
                دخول المختبر الذكي ✨
            </a>

        </div>

        <style>
            /* 💡 تصميم بطاقة مختبر القواعد */

            .card.grammar-special {

                border-color: #a855f7;

                background:
                    linear-gradient(145deg, #12071f, #1e1033);

                position: relative;

                overflow: hidden;
            }

            .card.grammar-special::before {

                content: '';

                position: absolute;

                width: 180px;
                height: 180px;

                background: rgba(168, 85, 247, .18);

                border-radius: 50%;

                top: -60px;
                left: -60px;

                filter: blur(20px);
            }

            .card.grammar-special:hover {

                border-color: #d8b4fe;

                transform: translateY(-6px);

                box-shadow:
                    0 10px 30px rgba(168, 85, 247, .35);
            }

            .card.grammar-special h3 {

                color: #e9d5ff;
            }

            .card.grammar-special p {

                color: #cbd5e1;
            }

            .card.grammar-special .btn {

                background:
                    linear-gradient(90deg, #a855f7, #7c3aed);

                border: 1px solid #d8b4fe;

                color: white;

                box-shadow:
                    0 0 12px rgba(168, 85, 247, .25);

                transition: .3s ease;
            }

            .card.grammar-special .btn:hover {

                background:
                    linear-gradient(90deg, #d8b4fe, #a855f7);

                color: #000;

                box-shadow:
                    0 0 25px rgba(216, 180, 254, .5);
            }

            .card.grammar-special .card-badge {

                background: #a855f7;

                box-shadow:
                    0 0 10px rgba(168, 85, 247, .6);
            }

            /* 📱 للجوال */

            @media(max-width:600px) {

                .card.grammar-special {

                    padding: 20px 16px;
                }

                .card.grammar-special h3 {

                    font-size: 19px;
                }

                .card.grammar-special p {

                    font-size: 14px;

                    line-height: 1.7;
                }

                .card.grammar-special .btn {

                    padding: 14px;
                    font-size: 15px;
                }
            }
        </style>
        <div class="card" style="border-color: #38bdf8; background: linear-gradient(145deg, #0f172a, #032541);">
            <h3>🎯 بنك كلماتي وقاموسي الخاص</h3>
            <p>أضف كلماتك الصعبة، مصطلحاتك الخاصة، أو الأسطر البرمجية التي تدرسها حالياً، وتدرّب على كتابتها بمفردك لترسيخها في عقلك!</p>
            <a href="my-words.php" class="btn" style="background: #38bdf8; color: #000;">إدارة قاموسي الشخصي ⚙️</a>
        </div>

        <div class="card">
            <h3>💻 أكواد ومصطلحات برمجية</h3>
            <p>اختر لغتك البرمجية المفضلة (PHP, JavaScript, Python...) وابدأ باحتراف كتابة رموزها وسطورها البرمجية بدقة وسرعة خاطفة.</p>
            <a href="codes.php" class="btn">ابدأ التحدي البرمجي</a>
        </div>

        <div class="card">
            <h3>🧠 تحدي الجمل الإنجليزية</h3>
            <p>انتقل للمستوى المتقدم وتدرب على كتابة جمل إنجليزية كاملة وصحيحة لتحسين ذاكرة أصابعك العضلية على الكيبورد.</p>
            <a href="english-challenge.php" class="btn">تحدي الجمل</a>
        </div>

        <div class="card">
            <h3>⚡ اختبار السرعة السريع</h3>
            <p>اختبار كلاسيكي خفيف لمعرفة كم عدد الكلمات العامة التي يمكنك صيدها وكتابتها في الدقيقة الواحدة وبأقل نسبة أخطاء.</p>
            <a href="speed-test.php" class="btn">دخول الاختبار</a>
        </div>

        <div class="card">
            <h3>🔤 قاموس وقسم الكلمات</h3>
            <p>مخزن وبنك الكلمات والمصطلحات لتعلم وحفظ هجاء المفردات وممارستها بشكل فردي مريح وسلس.</p>
            <a href="words.php" class="btn">استعراض الكلمات</a>
        </div>

        <div class="card" style="border-style: dashed;">
            <h3>👤 بطاقتك الشخصية والإنجازات</h3>
            <p>تابع مستواك المتطور، شاهد أعلى متتالي (Streak) حققته، وتأمل أرقامك القياسية الخاصة (Personal Best) في بروفايلك المشفر.</p>
            <a href="profile.php" class="btn">عرض ملفك الشخصي</a>
        </div>

    </div>
</div>

<?php
// تضمين الفوتر لإغلاق الوسوم بشكل سليم
include 'includes/footer.php';
?>