<?php
// 1. تضمين ملف الاتصال بقاعدة البيانات وملف الناف بار المشترك
include 'config/db.php';
include 'includes/header.php';

// 2. جلب اللغات المتوفرة فقط لتغذية خيارات التصفية في الواجهة
try {
    $lang_stmt = $pdo->query("SELECT lang_name FROM languages ORDER BY lang_name ASC");
    $languages = $lang_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    die("خطأ في جلب اللغات: " . $e->getMessage());
}
?>

<style>
    .speed-test-box {
        max-width: 900px;
        margin: 30px auto;
        text-align: center;
        font-family: 'Tajawal', sans-serif;
        padding: 0 15px;
        box-sizing: border-box;
    }

    /* صندوق التحكم بمصدر البيانات المطور */
    .source-selector-box {
        background: #0f172a;
        border: 1px solid #1e293b;
        padding: 15px 25px;
        border-radius: 12px;
        margin: 20px auto 25px auto;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        flex-wrap: wrap;
        text-align: right;
    }

    .selector-title {
        font-weight: bold;
        color: #94a3b8;
        font-size: 15px;
    }

    .selector-options {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .source-btn {
        background: #1e293b;
        color: #fff;
        border: 1px solid #475569;
        padding: 8px 18px;
        border-radius: 8px;
        font-weight: bold;
        cursor: pointer;
        transition: 0.2s;
        font-size: 14px;
        text-decoration: none;
        display: inline-block;
    }

    .source-btn:hover {
        border-color: #38bdf8;
    }

    .source-btn.active {
        background: rgba(56, 189, 248, 0.15);
        border-color: #38bdf8;
        color: #38bdf8;
        box-shadow: 0 0 10px rgba(56, 189, 248, 0.1);
    }

    .source-btn.disabled-btn {
        background: #090d16;
        color: #475569;
        border-color: #1e293b;
        cursor: not-allowed;
    }

    /* لوحة إعدادات التحدي */
    .setup-panel {
        background-color: #1e293b;
        border: 2px solid #334155;
        border-radius: 16px;
        padding: 25px;
        margin-bottom: 25px;
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 20px;
        align-items: center;
    }

    .setup-group {
        display: flex;
        flex-direction: column;
        text-align: right;
        gap: 8px;
        width: 100%;
        max-width: 220px;
    }

    .setup-group label {
        font-size: 14px;
        color: #38bdf8;
        font-weight: bold;
    }

    .setup-select {
        padding: 10px 15px;
        background-color: #0f172a;
        border: 1px solid #334155;
        border-radius: 8px;
        color: #fff;
        font-size: 15px;
        outline: none;
        cursor: pointer;
        width: 100%;
        box-sizing: border-box;
    }

    .setup-select:focus {
        border-color: #38bdf8;
    }

    /* أزرار التحكم بالتحدي */
    .controls-wrapper {
        display: flex;
        gap: 15px;
        align-items: center;
        justify-content: center;
        flex-wrap: wrap;
        width: 100%;
        margin-top: 10px;
    }

    .start-trigger-btn {
        background-color: #4ade80;
        color: #020617;
        font-size: 16px;
        font-weight: bold;
        border: none;
        padding: 12px 30px;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s;
        min-width: 160px;
    }

    .start-trigger-btn:hover {
        background-color: #22c55e;
        transform: translateY(-2px);
        box-shadow: 0 0 15px rgba(74, 222, 128, 0.4);
    }

    /* زر الإيقاف الإضافي */
    .stop-trigger-btn {
        background-color: #ef4444;
        color: #fff;
        font-size: 16px;
        font-weight: bold;
        border: none;
        padding: 12px 25px;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s;
        display: none;
        min-width: 130px;
    }

    .stop-trigger-btn:hover {
        background-color: #dc2626;
        transform: translateY(-2px);
        box-shadow: 0 0 15px rgba(239, 68, 68, 0.4);
    }

    /* لوحة الإحصائيات الحية أثناء اللعب */
    .stats-bar {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
        margin-bottom: 25px;
        width: 100%;
        box-sizing: border-box;
    }

    .stat-card {
        background: #1e293b;
        border: 1px solid #334155;
        padding: 12px 10px;
        border-radius: 12px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        text-align: center;
    }

    .stat-val {
        font-size: clamp(20px, 5vw, 28px);
        font-weight: bold;
        color: #38bdf8;
    }

    .stat-lbl {
        font-size: clamp(11px, 3vw, 13px);
        color: #94a3b8;
        margin-top: 5px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* حاوية الكلمات المتتالية */
    .words-display-wall {
        background-color: #020617;
        border: 2px solid #334155;
        border-radius: 16px;
        padding: 20px;
        font-size: clamp(18px, 5vw, 24px);
        line-height: 1.8;
        height: 180px;
        overflow-y: auto;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: center;
        align-content: flex-start;
        direction: ltr;
        position: relative;
        width: 100%;
        box-sizing: border-box;
    }

    .words-display-wall::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 25px;
        background: linear-gradient(transparent, #020617);
        pointer-events: none;
    }

    /* مظهر الكلمات المتتالية */
    .speed-word {
        color: #475569;
        padding: 2px 6px;
        border-radius: 6px;
        transition: all 0.2s ease;
        font-family: 'Fira Code', monospace;
        display: inline-block;
        word-break: break-all;
    }

    .speed-word.current {
        color: #38bdf8;
        background-color: rgba(56, 189, 248, 0.15);
        font-weight: bold;
        border-bottom: 2px solid #38bdf8;
        transform: scale(1.05);
    }

    .speed-word.correct {
        color: #4ade80;
        font-weight: 600;
    }

    .speed-word.wrong {
        color: #ef4444;
        text-decoration: line-through;
    }

    /* حقل الكتابة الاحترافي الكبير */
    .speed-input-container {
        width: 100%;
        display: flex;
        justify-content: center;
        margin-top: 25px;
    }

    .speed-input {
        width: 100%;
        max-width: 550px;
        padding: 15px 20px;
        font-size: clamp(16px, 4.5vw, 22px);
        font-family: 'Fira Code', monospace;
        background-color: #0f172a;
        border: 2px solid #334155;
        border-radius: 12px;
        color: #fff;
        text-align: center;
        outline: none;
        transition: all 0.3s ease;
        box-sizing: border-box;
    }

    .speed-input:focus {
        border-color: #38bdf8;
        box-shadow: 0 0 20px rgba(56, 189, 248, 0.3);
    }

    .speed-input:disabled {
        background-color: #1e293b;
        color: #94a3b8;
        cursor: not-allowed;
        border-color: #334155;
    }

    /* 📱 استجابة شاشات الهواتف والتابلت اللمسية */
    @media (max-width: 768px) {
        .speed-test-box h2 {
            font-size: clamp(18px, 5.5vw, 24px);
        }

        .source-selector-box {
            flex-direction: column !important;
            text-align: center !important;
            padding: 15px;
            gap: 10px;
        }

        .selector-options {
            width: 100%;
            justify-content: center;
        }

        .source-btn {
            width: 100%;
            text-align: center;
        }

        .setup-panel {
            padding: 15px;
            gap: 15px;
            flex-direction: column;
        }

        .setup-group {
            max-width: 100%;
        }

        .controls-wrapper {
            width: 100%;
            flex-direction: column;
            gap: 10px;
        }

        .start-trigger-btn,
        .stop-trigger-btn {
            width: 100%;
        }

        .stats-bar {
            gap: 8px;
        }
    }
</style>

<div class="speed-test-box">
    <h2>⚡ حلبـة تحدي السرعـة الخارقة (السرعة والدقة)</h2>
    <p style="color: #94a3b8; margin-bottom: 25px; font-size: 14px;">شريكي، اختر مصدر البيانات، نوع التحدي والوقت من الأسفل ثم انطلق لتفجير طاقتك! 🔥</p>

    <div class="source-selector-box">
        <span class="selector-title">🎯 مصدر مفردات التحدي:</span>
        <div class="selector-options">
            <button type="button" class="source-btn active" id="btn-global-src" onclick="changeSpeedSource('global')">🌐 بيانات الموقع العامة</button>
            <?php if (isset($_SESSION['user_id'])): ?>
                <button type="button" class="source-btn" id="btn-personal-src" onclick="changeSpeedSource('personal')">🥷 بنكي الشخصي المخصص</button>
            <?php else: ?>
                <a href="login.php" class="source-btn disabled-btn" title="سجل دخولك لتفعيل هذا الخيار">🔒 بنكي الشخصي (يتطلب دخول)</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="setup-panel" id="setup-area">
        <div class="setup-group">
            <label for="mode-select">نوع التحدي:</label>
            <select id="mode-select" class="setup-select">
                <option value="words_only">🔤 كلمات ومصطلحات عادية فقط</option>
                <option value="codes_only">💻 مصطلحات برمجية (تصفية حسب اللغة)</option>
                <option value="all_codes">🔥 كل اللغات البرمجية معاً (صعب)</option>
                <option value="matrix_mix">💀 العشوائية المطلقة (خلط الكلمات والأكواد)</option>
            </select>
        </div>

        <div class="setup-group" id="lang-filter-group" style="display: none;">
            <label for="lang-filter">اللغة البرمجية المحددة:</label>
            <select id="lang-filter" class="setup-select">
                <?php foreach ($languages as $l): ?>
                    <option value="<?php echo htmlspecialchars($l); ?>"><?php echo htmlspecialchars($l); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="setup-group">
            <label for="time-select">مدة التحدي:</label>
            <select id="time-select" class="setup-select">
                <option value="60">⏱️ دقيقة واحدة (60 ثانية)</option>
                <option value="120">⏱️ دقيقتان (120 ثانية)</option>
                <option value="180">⏱️ 3 دقائق (180 ثانية)</option>
                <option value="240">⏱️ 4 دقائق (240 ثانية)</option>
                <option value="300">⏱️ 5 دقائق (300 ثانية - كابوس)</option>
            </select>
        </div>

        <div class="controls-wrapper">
            <button id="start-btn" class="start-trigger-btn">ابدأ التحدي الآن 💥</button>
            <button id="stop-btn" class="stop-trigger-btn">✋ إيقاف وإلغاء</button>
        </div>
    </div>

    <div class="stats-bar">
        <div class="stat-card">
            <div class="stat-val" id="timer-val">60</div>
            <div class="stat-lbl">المتبقي (ثانية)</div>
        </div>
        <div class="stat-card">
            <div class="stat-val" id="wpm-val">0</div>
            <div class="stat-lbl">كلمة / دقيقة</div>
        </div>
        <div class="stat-card">
            <div class="stat-val" id="accuracy-val">100%</div>
            <div class="stat-lbl">نسبة الدقة</div>
        </div>
    </div>

    <div class="words-display-wall" id="words-wall">
        <span style="color: #94a3b8; font-size: 16px;">اضغط زر البدء لتوليد الطابور العشوائي والبدء في الكتابة السريعة! 🚀</span>
    </div>

    <div class="speed-input-container">
        <input type="text" id="speed-input-field" class="speed-input" placeholder="حقل الكتابة مغلق.. اضغط ابدأ أولاً" disabled autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const modeSelect = document.getElementById("mode-select");
        const langFilterGroup = document.getElementById("lang-filter-group");
        const langFilter = document.getElementById("lang-filter");
        const timeSelect = document.getElementById("time-select");
        const startBtn = document.getElementById("start-btn");
        const stopBtn = document.getElementById("stop-btn");
        const wordsWall = document.getElementById("words-wall");
        const speedInputField = document.getElementById("speed-input-field");

        const timerVal = document.getElementById("timer-val");
        const wpmVal = document.getElementById("wpm-val");
        const accuracyVal = document.getElementById("accuracy-val");

        let currentSource = 'global';
        let gameWordsQueue = [];
        let currentWordIdx = 0;
        let timerInterval = null;
        let totalDuration = 60;
        let timeLeft = 60;
        let isPlaying = false;

        let totalTypedWords = 0;
        let correctTypedWords = 0;

        const normalKeySound = new Audio("assets/sounds/click.mp3");
        const spaceKeySound = new Audio("assets/sounds/space.mp3");

        normalKeySound.preload = "auto";
        spaceKeySound.preload = "auto";
        normalKeySound.volume = 0.5;
        spaceKeySound.volume = 0.6;

        function playKeyClickSound(isSpecialKey = false) {
            try {
                if (isSpecialKey) {
                    spaceKeySound.currentTime = 0;
                    spaceKeySound.play().catch(e => console.log(e));
                } else {
                    normalKeySound.currentTime = 0;
                    normalKeySound.play().catch(e => console.log(e));
                }
            } catch (e) {
                console.log(e);
            }
        }

        timeSelect.addEventListener("change", () => {
            if (!isPlaying) {
                timerVal.textContent = timeSelect.value;
            }
        });

        modeSelect.addEventListener("change", () => {
            if (modeSelect.value === "codes_only") {
                langFilterGroup.style.display = "flex";
            } else {
                langFilterGroup.style.display = "none";
            }
        });

        window.changeSpeedSource = function(source) {
            if (isPlaying) {
                alert("يرجى إنهاء أو إيقاف التحدي الحالي أولاً قبل تغيير المصدر!");
                return;
            }
            currentSource = source;
            document.getElementById('btn-global-src').classList.toggle('active', source === 'global');
            const personalBtn = document.getElementById('btn-personal-src');
            if (personalBtn) personalBtn.classList.toggle('active', source === 'personal');
        }

        function shuffleArray(array) {
            for (let i = array.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [array[i], array[j]] = [array[j], array[i]];
            }
            return array;
        }

        function fetchAndBuildQueue() {
            wordsWall.innerHTML = '<span style="color: #38bdf8; font-size: 16px;">جاري شحن مستودع المفردات بشكل ذكي... 🔋</span>';
            startBtn.disabled = true;

            const mode = modeSelect.value;
            let apiMode = 'words';
            let selectedLang = '';

            if (mode === 'codes_only') {
                apiMode = 'codes';
                selectedLang = langFilter.value;
            } else if (mode === 'all_codes') {
                apiMode = 'codes';
            } else if (mode === 'matrix_mix') {
                apiMode = 'mix';
            }

            fetch(`get-game-words.php?source=${currentSource}&mode=${apiMode}&lang=${encodeURIComponent(selectedLang)}`)
                .then(response => response.json())
                .then(data => {
                    startBtn.disabled = false;
                    if (data.status === 'success') {
                        let pool = data.words.map(item => item.text);

                        if (pool.length === 0) {
                            pool = ["Keyali", "Team", "Localhost", "XAMPP", "PHP", "MySQL", "JavaScript", "Coding"];
                        }

                        let finalPool = [];
                        for (let i = 0; i < 15; i++) {
                            finalPool = finalPool.concat(pool);
                        }

                        gameWordsQueue = shuffleArray(finalPool);
                        executeGameLaunch();
                    } else {
                        alert(data.message);
                        wordsWall.innerHTML = `<span style="color: #ef4444; font-size: 15px;">فشل الجلب: ${data.message}</span>`;
                        isPlaying = false;
                        stopBtn.style.display = "none";
                    }
                })
                .catch(error => {
                    startBtn.disabled = false;
                    isPlaying = false;
                    stopBtn.style.display = "none";
                    console.error("Error:", error);
                    wordsWall.innerHTML = '<span style="color: #ef4444; font-size: 15px;">خطأ في الاتصال بالخادم الرئيسي!</span>';
                });
        }

        function renderWordsWall() {
            wordsWall.innerHTML = "";
            gameWordsQueue.forEach((word, idx) => {
                const span = document.createElement("span");
                span.className = "speed-word";
                span.id = `speed-w-${idx}`;
                span.textContent = word;
                wordsWall.appendChild(span);
            });
            document.getElementById("speed-w-0").classList.add("current");
            wordsWall.scrollTop = 0;
        }

        function startSpeedTest() {
            if (isPlaying) return;
            isPlaying = true;

            stopBtn.style.display = "inline-block";

            normalKeySound.play().then(() => {
                normalKeySound.pause();
                normalKeySound.currentTime = 0;
            });
            spaceKeySound.play().then(() => {
                spaceKeySound.pause();
                spaceKeySound.currentTime = 0;
            });

            fetchAndBuildQueue();
        }

        function executeGameLaunch() {
            totalDuration = parseInt(timeSelect.value);
            timeLeft = totalDuration;

            currentWordIdx = 0;
            totalTypedWords = 0;
            correctTypedWords = 0;

            timerVal.textContent = timeLeft;
            wpmVal.textContent = "0";
            accuracyVal.textContent = "100%";

            renderWordsWall();

            speedInputField.disabled = false;
            speedInputField.value = "";
            speedInputField.placeholder = "اكتب واضغط Enter للتحقق والاعتماد... 💾";

            // منع انبثاق لوحة مفاتيح الجوال فجأة لتجنب اهتزاز الشاشة التلقائي إلا برغبة اللاعب
            if (window.innerWidth > 768) {
                speedInputField.focus();
            }

            startBtn.textContent = "🔄 إعادة تشغيل";

            timerInterval = setInterval(() => {
                timeLeft--;
                timerVal.textContent = timeLeft;

                if (timeLeft < totalDuration) {
                    let minutesPassed = (totalDuration - timeLeft) / 60;
                    let currentWPM = Math.round(correctTypedWords / minutesPassed);
                    wpmVal.textContent = currentWPM || 0;
                }

                if (timeLeft <= 0) {
                    endSpeedTest();
                }
            }, 1000);
        }

        function endSpeedTest() {
            clearInterval(timerInterval);
            isPlaying = false;
            speedInputField.disabled = true;
            speedInputField.placeholder = "انتهى الوقت! رؤية النتيجة بالأعلى 🏆";
            stopBtn.style.display = "none";

            alert(`🏁 انتهى التحدي يا شريكي الخارق!\nسرعتك: ${wpmVal.textContent} كلمة بالدقيقة (WPM)\nدقتك: ${accuracyVal.textContent}\nأنت مذهل!`);
        }

        function forceStopGame() {
            clearInterval(timerInterval);
            isPlaying = false;

            speedInputField.disabled = true;
            speedInputField.value = "";
            speedInputField.placeholder = "تم إيقاف التحدي حراً.. اختر إعداداتك وابدأ مجدداً";

            timerVal.textContent = timeSelect.value;
            wpmVal.textContent = "0";
            accuracyVal.textContent = "100%";

            startBtn.textContent = "ابدأ التحدي الآن 💥";
            stopBtn.style.display = "none";

            wordsWall.innerHTML = '<span style="color: #94a3b8; font-size: 16px;">تم الإيقاف بنجاح. حلبة الاستعداد بانتظارك من جديد! 🚀</span>';
        }

        startBtn.addEventListener("click", () => {
            clearInterval(timerInterval);
            isPlaying = false;
            startSpeedTest();
        });

        stopBtn.addEventListener("click", forceStopGame);

        speedInputField.addEventListener("keydown", (e) => {
            if (!isPlaying) return;

            // 1. تشغيل أصوات النقر عند أي زر باستثناء الـ Enter لكي لا تتداخل الأصوات
            if (e.key !== "Enter") {
                const isSpaceKey = (e.key === " ");
                playKeyClickSound(isSpaceKey);
            }

            // 2. إذا ضغط Enter، نقوم بعملية التحقق واعتماد الكلمة
            if (e.key === "Enter") {
                e.preventDefault(); // منع كسر السطر

                let typed = speedInputField.value.trim();
                if (typed === "") return;

                let targetWord = gameWordsQueue[currentWordIdx].trim();
                const currentSpan = document.getElementById(`speed-w-${currentWordIdx}`);

                totalTypedWords++;

                if (typed.toLowerCase() === targetWord.toLowerCase()) {
                    correctTypedWords++;
                    currentSpan.className = "speed-word correct";
                } else {
                    currentSpan.className = "speed-word wrong";
                }

                let accPercent = Math.round((correctTypedWords / totalTypedWords) * 100);
                accuracyVal.textContent = accPercent + "%";

                currentWordIdx++;
                const nextSpan = document.getElementById(`speed-w-${currentWordIdx}`);
                if (nextSpan) {
                    currentSpan.classList.remove("current");
                    nextSpan.classList.add("current");
                    nextSpan.scrollIntoView({
                        behavior: 'smooth',
                        block: 'nearest'
                    });
                }

                speedInputField.value = "";
            }
        });
    });
</script>

<?php
// 5. تضمين ملف الفوتر لإغلاق الأوسمة
include 'includes/footer.php';
?>