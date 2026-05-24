<?php 
// 1. تضمين ملف الاتصال بقاعدة البيانات وملف الناف بار المشترك
include 'config/db.php';
include 'includes/header.php'; 

// 2. جلب اللغات المتوفرة لتغذية شاشة الاختيار البرمجية
try {
    $lang_stmt = $pdo->query("SELECT * FROM languages ORDER BY lang_name ASC");
    $languages = $lang_stmt->fetchAll();
} catch (PDOException $e) {
    die("خطأ: " . $e->getMessage());
}
?>

<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

<style>
    .ranked-container { max-width: 900px; margin: 40px auto; text-align: center; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 0 15px; box-sizing: border-box; }
    
    /* 1. شاشة الاختيار المبدئية (Setup Screen) */
    .setup-card { background: #0f172a; border: 2px solid #334155; border-radius: 20px; padding: 40px; box-shadow: 0 15px 30px rgba(0,0,0,0.5); transition: all 0.3s ease; }
    .mode-options { display: flex; justify-content: center; gap: 20px; margin: 30px 0; flex-wrap: wrap; }
    .mode-btn { background: #1e293b; border: 2px solid #475569; color: #fff; padding: 20px 30px; font-size: 20px; font-weight: bold; border-radius: 15px; cursor: pointer; transition: all 0.3s; min-width: 200px; }
    .mode-btn:hover, .mode-btn.active { border-color: var(--accent); background: rgba(56, 189, 248, 0.1); transform: translateY(-3px); box-shadow: 0 5px 15px rgba(56, 189, 248, 0.2); }
    .sub-options { display: none; margin-top: 20px; animation: fadeIn 0.5s ease; }
    
    /* 🔥 التنسيق الفخم والمخصص الجديد للقائمة المنسدلة */
    .lang-selector { 
        font-size: 18px; 
        padding: 12px 25px; 
        background: #1e293b; 
        color: #fff; 
        border: 2px solid #334155; 
        border-radius: 12px; 
        outline: none; 
        cursor: pointer; 
        font-family: inherit;
        transition: all 0.3s ease;
        min-width: 280px;
        text-align: center;
        appearance: none; /* إخفاء سهم المتصفح الافتراضي المزعج */
        background-image: url("data:image/svg+xml;charset=UTF-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%2338bdf8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: left 15px center;
        background-size: 16px;
        padding-left: 45px;
    }
    .lang-selector:focus {
        border-color: var(--accent);
        box-shadow: 0 0 15px rgba(56, 189, 248, 0.3);
    }
    .lang-selector option { background: #0f172a; color: #fff; padding: 10px; }

    .start-btn { background: var(--accent); color: #000; font-size: 22px; font-weight: bold; padding: 15px 40px; border: none; border-radius: 12px; cursor: pointer; margin-top: 25px; box-shadow: 0 0 20px rgba(56, 189, 248, 0.4); transition: 0.2s; }
    .start-btn:hover { transform: scale(1.05); background: #7dd3fc; }

    /* 2. شاشة اللعب الحماسية المحدثة (Arena Screen) */
    .arena-card { display: none; background: #020617; border: 3px solid #1e293b; border-radius: 24px; padding: 40px; position: relative; overflow: hidden; transition: all 0.3s ease; }
    
    /* عداد الحماس والمضاعفات التفاعلي المتجاوب */
    .hud-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 10px; }
    .hud-box { background: #0f172a; border: 1px solid #334155; padding: 10px 25px; border-radius: 50px; font-weight: bold; font-size: 18px; min-width: 140px; box-sizing: border-box; }
    
    /* شارات الاشتعال النار والمضاعف */
    .multiplier-badge { font-size: 28px; font-weight: 900; color: #f59e0b; text-shadow: 0 0 15px rgba(245, 158, 11, 0.6); animation: pulse 1s infinite; display: inline-block; }
    .fire-streak { color: #ef4444; text-shadow: 0 0 10px rgba(239, 68, 68, 0.5); }

    /* لوحة عرض الكلمة الكبيرة في المنتصف بمرونة هائلة */
    .target-box { font-family: 'Fira Code', monospace; font-size: clamp(28px, 6vw, 46px); color: #fff; margin: 30px 0; font-weight: bold; min-height: 60px; word-break: break-all; text-shadow: 0 0 15px rgba(255,255,255,0.1); letter-spacing: 1px; line-height: 1.4; }
    
    /* حقل الكتابة الاحترافي السريع */
    .ranked-input { width: 100%; max-width: 600px; padding: 18px 25px; font-size: clamp(18px, 5vw, 26px); font-family: 'Fira Code', monospace; background-color: #0f172a; border: 3px solid #334155; border-radius: 16px; color: #fff; text-align: center; outline: none; transition: all 0.2s ease; box-sizing: border-box; }
    .ranked-input:focus { border-color: var(--accent); box-shadow: 0 0 25px rgba(56, 189, 248, 0.4); }
    .ranked-input.input-error { border-color: var(--error) !important; box-shadow: 0 0 20px rgba(248, 113, 113, 0.4) !important; animation: shake 0.2s ease-in-out 2; }
    
    /* شاشة تفاعل الصدمة والفوز */
    .feedback-alert { min-height: 45px; font-size: clamp(16px, 4.5vw, 24px); font-weight: bold; margin-bottom: 20px; transition: 0.2s; padding: 0 10px; }

    /* 3. لوحة تحدي الأصدقاء المتصدرين بالموقع (Live Leaderboard Wall) */
    .leaderboard-wall { margin-top: 40px; background: #0f172a; border: 2px solid #1e293b; border-radius: 20px; padding: 30px; text-align: right; box-sizing: border-box; }
    .leaderboard-title { color: #f59e0b; font-size: 22px; font-weight: bold; margin-bottom: 10px; display: flex; align-items: center; gap: 10px; }
    .leader-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    .leader-table th, .leader-table td { padding: 12px 15px; text-align: center; border-bottom: 1px solid #1e293b; font-size: clamp(13px, 3.5vw, 16px); }
    .leader-table th { color: var(--accent); }
    
    .empty-board-msg { text-align: center; padding: 30px; color: var(--text-muted); font-size: 16px; font-style: italic; background: rgba(255,255,255,0.02); border-radius: 10px; border: 1px dashed #334155; }

    /* 📱 الميديا كويري الذكية والسحرية للهواتف وضد قفزة الكيبورد */
    @media (max-width: 768px) {
        .ranked-container { margin: 15px auto; }
        .setup-card { padding: 20px 15px; border-radius: 15px; }
        .mode-btn { padding: 15px 20px; font-size: 17px; min-width: 100%; }
        .lang-selector { min-width: 100%; font-size: 16px; }
        .start-btn { font-size: 19px; padding: 12px 30px; width: 100%; margin-top: 15px; }
        
        /* تكييف الحلبة لتقليل المسافات الطولية لتبرز فوق لوحة المفاتيح */
        .arena-card { padding: 20px 15px; border-radius: 16px; border-width: 2px; }
        .hud-row { margin-bottom: 15px; gap: 6px; justify-content: center; }
        .hud-box { padding: 8px 12px; font-size: 14px; min-width: 28%; flex-grow: 1; text-align: center; }
        .multiplier-badge { font-size: 22px; width: 100%; text-align: center; margin: 5px 0; }
        
        #arena-hint-box { font-size: 15px; margin-bottom: 5px; }
        .target-box { margin: 15px 0; min-height: 45px; }
        .ranked-input { padding: 12px 15px; border-radius: 12px; }
        .feedback-alert { min-height: 35px; margin-bottom: 10px; }
        
        .leaderboard-wall { padding: 20px 15px; border-radius: 15px; margin-top: 25px; }
        .leader-table th, .leader-table td { padding: 8px 6px; }
    }

    /* حركات الأنيميشن الممتعة */
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.08); } }
    @keyframes shake { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-6px); } 75% { transform: translateX(6px); } }
    /* منع تحديد النص في كامل صفحة التحدي */
.ranked-container {
    -webkit-user-select: none; /* Safari */
    -ms-user-select: none;     /* IE 10 and IE 11 */
    user-select: none;         /* Standard syntax */
}

/* السماح بالتحديد فقط داخل حقل الإدخال ليتمكن المستخدم من مسح كلامه */
.ranked-input {
    -webkit-user-select: text;
    user-select: text;
}
</style>

<div class="ranked-container">
    
    <div class="setup-card" id="setup-screen">
        <h2>🏆 صالة التحدي والمنافسة الأسطورية (Ranked Mode)</h2>
        <p style="color: var(--text-muted);">تحدي الـ 60 ثانية! اختر المسار، واجمع المضاعفات، وحطّم الأرقام القياسية قبل نهاية الوقت!</p>
        
        <div class="mode-options">
            <button class="mode-btn" id="btn-choose-eng" onclick="selectMainMode('english')">🇬🇧 تحدي الكلمات الإنجليزية</button>
            <button class="mode-btn" id="btn-choose-code" onclick="selectMainMode('code')">💻 تحدي مصطلحات الأكواد</button>
        </div>

        <div class="sub-options" id="code-sub-menu">
            <label style="color: var(--accent); font-weight: bold; font-size: 18px; display:block; margin-bottom:15px;">حدد لغة البرمجة المطلوبة للتحدي:</label>
            <select id="ranked-lang-select" class="lang-selector">
                <option value="all">-- كل اللغات عشوائياً 🎲 --</option>
                <?php foreach ($languages as $lang): ?>
                    <option value="<?php echo htmlspecialchars($lang['lang_name']); ?>"><?php echo htmlspecialchars($lang['lang_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <button class="start-btn" onclick="startRankedArena()">دخول الحلبة وبدء الحماس ⚔️</button>
    </div>

    <div class="arena-card" id="arena-screen">
        <div class="hud-row">
            <div class="hud-box text-success">💰 النقاط: <span id="arena-score">0</span></div>
            <div class="hud-box" id="timer-box-wrapper" style="color: #ef4444; border-color: #ef4444;">⏱️ المتبقي: <span id="arena-timer">60</span>ث</div>
            <div class="multiplier-badge" id="arena-multiplier">1x</div>
            <div class="hud-box fire-streak">🔥 المتتالي: <span id="arena-streak">0</span></div>
        </div>

        <div id="arena-hint-box" style="color: var(--text-muted); font-size: 19px; margin-bottom: 5px;"></div>

        <div class="target-box" id="arena-target-display">Loading...</div>

        <div class="feedback-alert" id="arena-feedback"></div>

        <div>
            <input type="text" id="arena-input-field" class="ranked-input" placeholder="اكتب الكلمة بدقة وبسرعة خاطفة..." autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
        </div>
        
        <p style="margin-top: 15px; color: #475569; font-size: 13px;">اضغط على <span class="highlight" style="color:var(--accent);">Enter</span> فقط للتحقق الفوري والانتقال الصاروخي.</p>
    </div>

    <div class="leaderboard-wall">
        <div class="leaderboard-title">🥇 جدار العمالقة: لوحة الصدارة العالمية</div>
        <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 15px;">هنا سيتم استعراض الملوك الأسرع في كتابة الكلمات البرمجية والإنجليزية على مستوى المنصة!</p>
        
        <?php
        try {
            $leader_stmt = $pdo->query("SELECT username, highest_score, highest_streak FROM users WHERE highest_score > 0 ORDER BY highest_score DESC LIMIT 5");
            $leaders = $leader_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($leaders) > 0): 
        ?>
                <table class="leader-table">
                    <thead>
                        <tr>
                            <th>الترتيب</th>
                            <th>المحارب الأسطوري</th>
                            <th>🔥 أعلى Streak</th>
                            <th>💰 مجموع النقاط</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $rank = 1;
                        foreach ($leaders as $leader): 
                        ?>
                            <tr>
                                <td style="font-weight: bold; color: #f59e0b;">#<?php echo $rank++; ?></td>
                                <td style="color: #fff; font-weight: bold;"><?php echo htmlspecialchars($leader['username']); ?></td>
                                <td style="color: #ef4444;"><?php echo $leader['highest_streak']; ?></td>
                                <td style="color: #4ade80; font-weight: bold;"><?php echo $leader['highest_score']; ?> نقطة</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
        <?php 
            else: 
        ?>
                <div class="empty-board-msg">
                    📡 شاشات التنافس جاهزة وحية! بانتظار أول بطل يسجل رقمه القياسي لتشتعل لوحة الصدارة باسمه هنا! 🚀🔥
                </div>
        <?php 
            endif;
        } catch (PDOException $e) {
            echo "<p style='color:var(--error); text-align:center;'>خطأ في تحميل لوحة الصدارة.</p>";
        }
        ?>
        
        <div style="margin-top:20px; text-align:center; font-size:15px; color:var(--accent);">
            أعلى رقم قياسي لك في هذه الجلسة: <span id="session-pb" style="font-weight:bold; color:#fff;">0 متتالي (0 نقطة)</span>
        </div>
    </div>
</div>

<script>
    const engPool = <?php 
        try {
            $stmt = $pdo->query("SELECT word_text AS text, word_meaning AS hint FROM words");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch(Exception $e){ echo "[]"; }
    ?>;

    const codePool = <?php 
        try {
            $stmt = $pdo->query("SELECT code_terms.code_text AS text, languages.lang_name AS hint FROM code_terms JOIN languages ON code_terms.language_id = languages.id");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch(Exception $e){ echo "[]"; }
    ?>;
</script>

<script>
    let selectedMode = ""; 
    let currentPool = [];
    let activeItem = {};
    
    let score = 0;
    let streak = 0;
    let multiplier = 1;
    let correctWordsCount = 0; 

    let timeLeft = 60;
    let timerInterval = null;
    let isGameActive = false;

    let maxStreakRecord = 0;
    let maxScoreRecord = 0;

    // 🔊 تهيئة جميع الملفات والمؤثرات الصوتية التفاعلية مسبقاً لحظر تعليق اللعبة
    const clickSound = new Audio("assets/sounds/click.mp3");
    const spaceSound = new Audio("assets/sounds/space.mp3");
    const correctSound = new Audio("assets/sounds/correct.mp3"); // صوت النجاح الخاطف
    const wrongSound = new Audio("assets/sounds/wrong.mp3");     // صوت تحطم الستريك بوم
    const countdownSound = new Audio("assets/sounds/countdown.mp3"); // تكتكة الثواني الأخيرة
    const victorySound = new Audio("assets/sounds/victory.mp3");   // لحن نهاية الوقت والاحتفال العالي
    
    // ضمان التحميل المسبق بكفاءة عالية
    clickSound.preload = "auto";
    spaceSound.preload = "auto";
    correctSound.preload = "auto";
    wrongSound.preload = "auto";
    countdownSound.preload = "auto";
    victorySound.preload = "auto";

    function playAudioType(type) {
        try {
            if (type === "space") {
                spaceSound.currentTime = 0; spaceSound.play().catch(e=>{});
            } else if (type === "correct") {
                correctSound.currentTime = 0; correctSound.play().catch(e=>{});
            } else if (type === "wrong") {
                wrongSound.currentTime = 0; wrongSound.play().catch(e=>{});
            } else if (type === "countdown") {
                countdownSound.currentTime = 0; countdownSound.play().catch(e=>{});
            } else if (type === "victory") {
                victorySound.currentTime = 0; victorySound.play().catch(e=>{});
            } else {
                clickSound.currentTime = 0; clickSound.play().catch(e=>{});
            }
        } catch(e){}
    }

    // 🎆 دالة إطلاق القصاصات الاحتفالية عند الفوز العالي وسحق السكور
    function triggerHighFieldNameConfetti() {
        let duration = 3 * 1000;
        let end = Date.now() + duration;

        (function frame() {
            confetti({ particleCount: 4, angle: 60, spread: 55, origin: { x: 0, y: 0.8 } });
            confetti({ particleCount: 4, angle: 120, spread: 55, origin: { x: 1, y: 0.8 } });

            if (Date.now() < end) {
                requestAnimationFrame(frame);
            }
        }());
    }

    function selectMainMode(mode) {
        if(isGameActive) return; 
        playAudioType("click");
        selectedMode = mode;
        document.getElementById("btn-choose-eng").classList.remove("active");
        document.getElementById("btn-choose-code").classList.remove("active");
        
        if (mode === "english") {
            document.getElementById("btn-choose-eng").classList.add("active");
            document.getElementById("code-sub-menu").style.display = "none";
        } else {
            document.getElementById("btn-choose-code").classList.add("active");
            document.getElementById("code-sub-menu").style.display = "block";
        }
    }

    function startRankedArena() {
        if (!selectedMode) {
            alert("يرجى اختيار مسار التحدي أولاً يا شريكي! ⚔️");
            return;
        }
        playAudioType("click");

        if (selectedMode === "english") {
            if (engPool.length === 0) { alert("قائمة الكلمات الإنجليزية فارغة في قاعدة بياناتك!"); return; }
            currentPool = [...engPool];
        } else {
            if (codePool.length === 0) { alert("قائمة الأكواد البرمجية فارغة في قاعدة بياناتك!"); return; }
            const langFilter = document.getElementById("ranked-lang-select").value;
            if (langFilter === "all") {
                currentPool = [...codePool];
            } else {
                currentPool = codePool.filter(item => item.hint === langFilter);
            }
        }

        if (currentPool.length === 0) {
            alert("لا توجد كلمات متوفرة لهذا الفلتر بالتحديد!");
            return;
        }

        document.getElementById("setup-screen").style.display = "none";
        document.getElementById("arena-screen").style.display = "block";
        
        score = 0; streak = 0; multiplier = 1; correctWordsCount = 0;
        timeLeft = 60;
        isGameActive = true;

        const timerWrapper = document.getElementById("timer-box-wrapper");
        timerWrapper.style.animation = "none";
        document.getElementById("arena-timer").textContent = timeLeft;
        
        updateHud();
        nextArenaTarget();
        startTimer();

        const inputField = document.getElementById("arena-input-field");
        inputField.disabled = false;
        inputField.placeholder = "اكتب بأقصى سرعة، الوقت يركض! 🏃‍♂️";
        
        // تفادي قفز المتصفح المزعج في شاشات الجوالات والتابلت عند التركيز الفوري
        if (window.innerWidth > 768) {
            inputField.focus();
        }
    }

    function startTimer() {
        if(timerInterval) clearInterval(timerInterval);
        
        timerInterval = setInterval(() => {
            timeLeft--;
            const timerDisplay = document.getElementById("arena-timer");
            if(timerDisplay) timerDisplay.textContent = timeLeft;

            // آخر 10 ثواني تشتعل الشاشة بالنبض الصوتي والبصري المخيف
            if(timeLeft <= 10 && timeLeft > 0 && timerDisplay) {
                playAudioType("countdown");
                timerDisplay.parentElement.style.animation = "pulse 0.5s infinite";
            }

            if(timeLeft <= 0) {
                endGameSession();
            }
        }, 1000);
    }

    function endGameSession() {
        clearInterval(timerInterval);
        isGameActive = false;
        playAudioType("victory"); // لحن النصر النهائي الحماسي
        
        const inputField = document.getElementById("arena-input-field");
        inputField.disabled = true;
        inputField.value = "";
        inputField.placeholder = "⏱️ انتهى الوقت! انتهت المعركة.";
        
        const displayEl = document.getElementById("arena-target-display");
        const feedbackEl = document.getElementById("arena-feedback");
        
        displayEl.style.fontSize = "24px";
        displayEl.innerHTML = `
            🎉 انتهت الدقيقة يا شريكي الأسطورة! 🎉<br>
            <span style="color:var(--success);">✅ الكلمات الصحيحة المكتوبة: ${correctWordsCount} كلمة</span><br>
            <span style="color:var(--accent);">💰 مجموع نقاط جولاتك المضروبة: ${score} نقطة</span>
        `;
        
        feedbackEl.className = "feedback-alert";
        feedbackEl.innerHTML = `<span style="color: #f59e0b;">⏳ جاري إرسال نتيجتك وفحص جدار العمالقة...</span>`;

        // 💥 شرط الذكاء الاحتفالي: إذا جاب سكور عالي (أكثر من 50 نقطة مثلاً) تطلق اللعبة الألعاب النارية
        if (score >= 50) {
            triggerHighFieldNameConfetti();
        }

        const formData = new FormData();
        formData.append('score', score);
        formData.append('streak', maxStreakRecord);

        fetch('save-score.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
                if(data.is_new_pb) {
                    triggerHighFieldNameConfetti(); // تأكيد الاحتفال بتحطيم السكور الشخصي مجدداً
                    feedbackEl.innerHTML = `<span style="color: #4ade80;">🔥 أسطووورة! حطمت رقمك القياسي السابق على السيرفر! 🏆</span><br><button onclick="window.location.reload()" class="start-btn" style="margin-top:10px; font-size:16px; padding:8px 20px;">تحديث لوحة الصدارة ورؤية اسمك 🔄</button>`;
                } else {
                    feedbackEl.innerHTML = `<span style="color: #94a3b8;">${data.message}</span><br><button onclick="resetToSetup()" class="start-btn" style="margin-top:10px; font-size:16px; padding:8px 20px;">تحدي جديد وسحق الأرقام 🔄</button>`;
                }
            } else {
                feedbackEl.innerHTML = `<span style="color: var(--error);">${data.message}</span><br><button onclick="resetToSetup()" class="start-btn" style="margin-top:10px; font-size:16px; padding:8px 20px;">تحدي جديد 🔄</button>`;
            }
        })
        .catch(err => {
            feedbackEl.innerHTML = `<span style="color: var(--error);">❌ فشل الاتصال بالسيرفر لحفظ النتيجة.</span><br><button onclick="resetToSetup()" class="start-btn" style="margin-top:10px; font-size:16px; padding:8px 20px;">تحدي جديد 🔄</button>`;
        });
    }

    function resetToSetup() {
        playAudioType("click");
        document.getElementById("arena-screen").style.display = "none";
        document.getElementById("setup-screen").style.display = "block";
        document.getElementById("arena-target-display").style.fontSize = "46px";
    }

    function nextArenaTarget() {
        if(!isGameActive) return;
        const randIdx = Math.floor(Math.random() * currentPool.length);
        activeItem = currentPool[randIdx];

        const displayEl = document.getElementById("arena-target-display");
        const hintEl = document.getElementById("arena-hint-box");
        const inputField = document.getElementById("arena-input-field");

        displayEl.textContent = activeItem.text.trim();

        if (selectedMode === "english") {
            hintEl.innerHTML = `💡 المعنى والمساعدة: <strong>${activeItem.hint}</strong>`;
        } else {
            hintEl.innerHTML = `💻 لغة البرمجة للرمز المستهدف: <strong>${activeItem.hint}</strong>`;
        }

        inputField.value = "";
        inputField.className = "ranked-input";
        document.getElementById("arena-feedback").textContent = "";
    }

    function updateHud() {
        document.getElementById("arena-score").textContent = score;
        document.getElementById("arena-streak").textContent = streak;
        
        if (streak >= 15) multiplier = 4;
        else if (streak >= 10) multiplier = 3;
        else if (streak >= 5) multiplier = 2;
        else multiplier = 1;

        const multBadge = document.getElementById("arena-multiplier");
        multBadge.textContent = multiplier + "x";

        if (multiplier > 1) {
            multBadge.style.color = multiplier === 4 ? "#ef4444" : "#f59e0b";
            multBadge.style.textShadow = `0 0 15px ${multiplier === 4 ? '#ef4444' : '#f59e0b'}`;
        } else {
            multBadge.style.color = "#475569";
            multBadge.style.textShadow = "none";
        }

        if (streak > maxStreakRecord) maxStreakRecord = streak;
        if (score > maxScoreRecord) maxScoreRecord = score;
        
        document.getElementById("session-pb").innerHTML = `🔥 ${maxStreakRecord} متتالي (${maxScoreRecord} نقطة)`;
    }

    const inputField = document.getElementById("arena-input-field");
    inputField.addEventListener("input", () => {
        if(!isGameActive) return;
        let typed = inputField.value;
        let correct = activeItem.text.trim();

        let isMatch = (selectedMode === "english") 
            ? correct.toLowerCase().startsWith(typed.toLowerCase())
            : correct.startsWith(typed);

        if (isMatch) {
            inputField.classList.remove("input-error");
        } else {
            inputField.classList.add("input-error");
        }
    });

    inputField.addEventListener("keydown", (e) => {
        if(!isGameActive) return;
        
        if (e.key === " ") {
            playAudioType("space");
            return; 
        } else if (e.key === "Enter") {
            // يتم كتم صوت الكليك العادي هنا ليفسح المجال لصوت التحكيم (صح/خطأ) المطور
            e.preventDefault();
        } else {
            if (e.key.length === 1 || e.key === "Backspace") {
                playAudioType("click");
            }
        }

        if (e.key === "Enter") {
            let typedVal = inputField.value.trim();
            let correctVal = activeItem.text.trim();

            let isCorrect = (selectedMode === "english")
                ? typedVal.toLowerCase() === correctVal.toLowerCase()
                : typedVal === correctVal;

            const feedbackEl = document.getElementById("arena-feedback");

            if (isCorrect && typedVal !== "") {
                playAudioType("correct"); // 🔊 تأثير النجاح السريع الممتع
                streak++;
                correctWordsCount++; 
                score += (10 * multiplier); 
                
                feedbackEl.className = "feedback-alert text-success";
                
                const cheerMsgs = ["أووووه خطييير! ⚡", "سرييع ومتقن يا ملك! 👑", "استمر، الشاشة تشتعل! 🔥", "عاش شريكي الأسطورة! 🦾"];
                feedbackEl.textContent = cheerMsgs[Math.floor(Math.random() * cheerMsgs.length)];

                updateHud();
                nextArenaTarget();
            } else if (typedVal !== "") {
                playAudioType("wrong"); // 🔊 بوم! صوت التحطم الرائع والعقوبة
                streak = 0; 
                multiplier = 1; 
                
                feedbackEl.className = "feedback-alert text-error";
                feedbackEl.textContent = "💥 بوم! خربت الـ Streak ورجعت للصفر! ركز يا شريكي 🛠️";
                
                inputField.classList.add("input-error");
                updateHud(); 
            }
        }
    });
</script>