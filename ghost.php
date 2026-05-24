<?php
// 1. تضمين ملفات المشروع الأساسية
include 'config/db.php';
include 'includes/header.php';

// مصفوفة لتخزين الكلمات من قاعدة البيانات
$all_words = [];
$top_ghosts = [];
$global_highest_score = 0;

try {
    // جلب الكلمات من جدولك (words) بترتيب عشوائي بالكامل
    $stmt = $pdo->query("SELECT id, word_text, word_meaning FROM words ORDER BY RAND()");
    $fetched_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($fetched_rows as $row) {
        if (!empty($row['word_text'])) {
            $all_words[] = [
                'id'          => $row['id'],
                'word'        => trim($row['word_text']),
                'translation' => trim($row['word_meaning'])
            ];
        }
    }

    // جلب التوب لطور الشبح
    try {
        $top_ghosts_stmt = $pdo->query("SELECT username, ghost_words_streak FROM users WHERE ghost_words_streak > 0 ORDER BY ghost_words_streak DESC LIMIT 5");
        $top_ghosts = $top_ghosts_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $top_ghosts = [];
    }

    // جلب أعلى سكور في طور الشبح على مستوى المنصة كلها
    try {
        $max_score_stmt = $pdo->query("SELECT MAX(ghost_words_streak) AS max_streak FROM users");
        $max_row = $max_score_stmt->fetch(PDO::FETCH_ASSOC);
        $global_highest_score = $max_row['max_streak'] ? intval($max_row['max_streak']) : 0;
    } catch (PDOException $e) {
        $global_highest_score = 0;
    }
} catch (PDOException $e) {
    echo "<div style='background:#ef4444; color:#fff; padding:15px; text-align:center; font-weight:bold;'>⚠️ خطأ في قاعدة البيانات: " . $e->getMessage() . "</div>";
    $all_words = [];
}

if (empty($all_words)) {
    $all_words = [
        ['id' => 1, 'word' => 'Accomplish', 'translation' => 'إنجاز'],
        ['id' => 2, 'word' => 'Simultaneous', 'translation' => 'متزامن']
    ];
}
?>

<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

<style>
    /* 🔒 جدار حماية لمنع النسخ أو التظليل كلياً */
    body,
    .word-display,
    .word-card,
    #word-text {
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
    }

    /* 📱 تحسين الحاوية الرئيسية لتكون مرنة ومتوافقة مع الموبايل */
    .ghost-word-container {
        display: flex;
        gap: 20px;
        max-width: 1000px;
        margin: 20px auto;
        align-items: flex-start;
        justify-content: center;
        padding: 0 15px;
        flex-direction: row;
        /* افتراضي للكمبيوتر */
    }

    .game-box {
        flex: 1;
        max-width: 650px;
        text-align: center;
        width: 100%;
    }

    .ghost-leaderboard {
        width: 280px;
        background: linear-gradient(135deg, #110c24, #05020a);
        border: 2px dashed #7c3aed;
        border-radius: 20px;
        padding: 20px;
        box-shadow: 0 10px 30px rgba(124, 58, 237, 0.2);
    }

    .ghost-leaderboard h3 {
        color: #c084fc;
        font-size: 19px;
        margin-bottom: 15px;
        text-align: center;
    }

    .ghost-rank-list {
        list-style: none;
        padding: 0;
        margin: 0;
        text-align: right;
        direction: rtl;
    }

    .ghost-rank-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 10px;
        border-bottom: 1px solid rgba(124, 58, 237, 0.2);
        color: #f3e8ff;
        font-family: 'Fira Code', monospace;
    }

    .ghost-rank-item .badge-rank {
        background: #6d28d9;
        padding: 2px 7px;
        border-radius: 6px;
        font-size: 11px;
    }

    .no-ghosts-yet {
        text-align: center;
        color: #6b7280;
        font-size: 14px;
        padding: 20px 0;
    }

    .word-card {
        background: linear-gradient(135deg, #090514, #130b24);
        border: 2px solid #5b21b6;
        border-radius: 20px;
        padding: 50px 20px;
        margin-bottom: 25px;
        box-shadow: 0 15px 35px rgba(124, 58, 237, 0.15);
        position: relative;
    }

    /* خط الكلمة متجاوب مع حجم الشاشة */
    .word-display {
        font-family: 'Fira Code', monospace;
        font-size: clamp(28px, 5vw, 42px);
        font-weight: bold;
        color: #e9d5ff;
        letter-spacing: 1px;
        margin-bottom: 20px;
        direction: ltr;
        transition: opacity 0.4s ease, filter 0.4s ease;
        word-wrap: break-word;
    }

    .word-display.ghost-vanished {
        opacity: 0 !important;
        filter: blur(12px);
        pointer-events: none;
    }

    .ghost-timer {
        font-size: 15px;
        color: #f43f5e;
        font-weight: bold;
        margin-top: 5px;
        background: rgba(244, 63, 94, 0.1);
        display: inline-block;
        padding: 5px 15px;
        border-radius: 10px;
    }

    .ghost-timer.typing-time {
        color: #fbbf24;
        background: rgba(251, 191, 36, 0.1);
    }

    /* تعديل عرض عدادات الوقت لتناسب الموبايل بشكل لطيف دون تداخل */
    .timers-wrapper {
        display: flex;
        justify-content: center;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }

    .round-total-timer {
        font-size: 16px;
        color: #38bdf8;
        font-weight: bold;
        background: rgba(56, 189, 248, 0.15);
        border: 1px solid rgba(56, 189, 248, 0.4);
        padding: 8px 20px;
        border-radius: 50px;
        display: inline-block;
    }

    .ghost-streak-badge {
        font-size: 16px;
        color: #c084fc;
        background: rgba(139, 92, 246, 0.15);
        border: 1px solid rgba(139, 92, 246, 0.4);
        padding: 8px 20px;
        border-radius: 50px;
        display: inline-block;
        font-weight: bold;
    }

    .word-translation {
        display: inline-block;
        background: rgba(139, 92, 246, 0.1);
        color: #ddd6fe;
        padding: 8px 20px;
        border-radius: 30px;
        font-size: 16px;
        margin-top: 15px;
        border: 1px solid rgba(139, 92, 246, 0.2);
    }

    .controls-container {
        position: absolute;
        top: 15px;
        right: 15px;
    }

    .speaker-btn {
        background: rgba(34, 197, 94, 0.1);
        border: 1px solid rgba(34, 197, 94, 0.3);
        color: #22c55e;
        font-size: 18px;
        border-radius: 50px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
    }

    .word-input {
        width: 100%;
        max-width: 400px;
        padding: 12px 15px;
        font-size: clamp(20px, 4vw, 26px);
        font-family: 'Fira Code', monospace;
        background-color: #0c0717;
        border: 2px solid #4c1d95;
        border-radius: 15px;
        color: #fff;
        text-align: center;
        outline: none;
        direction: ltr;
        box-sizing: border-box;
    }

    .word-input:focus {
        border-color: #a78bfa;
        box-shadow: 0 0 20px rgba(167, 139, 250, 0.3);
    }

    .word-input:disabled {
        background-color: #1a1528;
        color: #6b7280;
        cursor: not-allowed;
    }

    .word-input.input-error {
        border-color: #ef4444 !important;
        box-shadow: 0 0 15px rgba(239, 68, 68, 0.3);
    }

    .feedback-zone {
        height: 40px;
        font-size: clamp(16px, 3.5vw, 22px);
        font-weight: bold;
        margin-bottom: 20px;
        padding: 0 10px;
    }

    .text-success {
        color: #4ade80;
    }

    .text-error {
        color: #f87171;
    }

    .btn-start-adventure {
        background: linear-gradient(90deg, #7c3aed, #2563eb);
        color: #fff;
        padding: 12px 35px;
        font-size: clamp(16px, 4vw, 22px);
        font-weight: bold;
        border: none;
        border-radius: 50px;
        cursor: pointer;
        box-shadow: 0 0 25px rgba(124, 58, 237, 0.6);
        margin-bottom: 20px;
        transition: transform 0.2s;
        width: 90%;
        max-width: 350px;
    }

    .btn-start-adventure:hover {
        transform: scale(1.03);
    }

    .btn-skip {
        background-color: #312e81;
        color: #e0e7ff;
        padding: 12px 30px;
        font-size: 15px;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        margin-top: 15px;
    }

    .rules-card {
        background: rgba(15, 10, 31, 0.7);
        border: 1px solid rgba(124, 58, 237, 0.3);
        border-radius: 15px;
        padding: 20px;
        text-align: right;
        max-width: 500px;
        margin: 0 auto 25px auto;
        direction: rtl;
        box-shadow: inset 0 0 20px rgba(124, 58, 237, 0.1);
    }

    .rules-card h4 {
        color: #c084fc;
        font-size: 17px;
        margin-top: 0;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .rules-card ul {
        margin: 0;
        padding-right: 15px;
        color: #cbd5e1;
        font-size: 13px;
        line-height: 1.7;
    }

    .rules-card li {
        margin-bottom: 8px;
    }

    .highlight-gold {
        color: #fbbf24;
        font-weight: bold;
    }

    .highlight-purple {
        color: #a78bfa;
        font-weight: bold;
    }

    .game-over-box {
        background: linear-gradient(135deg, #1c0a21, #0a030d);
        border: 3px double #f43f5e;
        border-radius: 20px;
        padding: 30px 20px;
        box-shadow: 0 0 35px rgba(244, 63, 94, 0.3);
        max-width: 500px;
        margin: 30px auto;
        text-align: center;
        position: relative;
        width: 100%;
        box-sizing: border-box;
    }

    .server-status-msg {
        font-size: 13px;
        color: #a78bfa;
        margin-top: 10px;
        font-style: italic;
    }

    /* 🛡️ شاشات الموبايل (Media Queries) لجعل التصميم عمودياً ومنساباً */
    @media (max-width: 768px) {
        .ghost-word-container {
            flex-direction: column;
            /* تحويل الترتيب عمودياً لكي ينزل التوب للأسفل */
            align-items: center;
        }

        .game-box {
            order: 1;
            /* جعل صندوق اللعبة يظهر أولاً في الأعلى */
        }

        .ghost-leaderboard {
            order: 2;
            /* جعل الليدربورد تنزل تحت صندوق اللعب */
            width: 100%;
            max-width: 400px;
            box-sizing: border-box;
            margin-top: 20px;
        }

        .word-card {
            padding: 40px 15px;
        }
    }
</style>

<div class="ghost-word-container">
    <div class="ghost-leaderboard">
        <h3>👻 توب أشباح الكلمات 👻</h3>
        <ul class="ghost-rank-list" id="leaderboard-list">
            <?php if (!empty($top_ghosts)): ?>
                <?php foreach ($top_ghosts as $index => $ghost): ?>
                    <li class="ghost-rank-item">
                        <span><span class="badge-rank">#<?php echo $index + 1; ?></span> <?php echo htmlspecialchars($ghost['username']); ?></span>
                        <span style="color: #c084fc; font-weight:bold;"><?php echo $ghost['ghost_words_streak']; ?> ⚡</span>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-ghosts-yet">
                    👤 لا يوجد أشباح حالياً...<br>
                    <span style="color: #a78bfa; font-weight: bold;">كن أول شبح يحفر اسمه هنا! 🔥</span>
                </div>
            <?php endif; ?>
        </ul>
    </div>

    <div class="game-box">
        <h2 style="color: #e9d5ff; margin-bottom: 5px; font-size: clamp(20px, 5vw, 28px);">👻 GHOST MODE: تحدي الكلمات الخاطفة</h2>
        <p style="color: #a78bfa; margin-bottom: 25px; font-size: 14px;">طور السرعة، الحفظ، والتحدي الاستثنائي</p>

        <div id="start-screen-zone">
            <div class="rules-card">
                <h4>📜 قواعد تحدي الـ دقيقتين:</h4>
                <ul>
                    <li>⏱️ الوقت الإجمالي للجولة هو <span class="highlight-gold">120 ثانية (دقيقتان)</span> لتجميع أعلى سكور.</li>
                    <li>👁️ تظهر الكلمة والترجمة لمدة <span class="highlight-purple">5 ثوانٍ</span> لحفظها بتركيز.</li>
                    <li>👻 تختفي الكلمة، ويبدأ عداد الكلمة الخاطفة مدته <span class="highlight-purple">15 ثانية</span> للكتابة غيباً.</li>
                    <li>⚡ السرعة تمنحك نقاطاً مضاعفة: أول 5 ثوانٍ <span class="highlight-gold">(+6 نقاط)</span> ثم <span class="highlight-gold">(+4 نقاط)</span> ثم <span class="highlight-gold">(+2 نقطة)</span>.</li>
                    <li>⚠️ الخطأ الإملائي أو انتهاء وقت الكلمة <span style="color: #fbbf24; font-weight:bold;">يخصم نقطتين (-2) فقط</span> من مجموعك الحالي.</li>
                    <li>🎉 <span style="color: #4ade80; font-weight:bold;">مفاجأة تكسير الأرقام:</span> ستحصل على حفلة قصاصات ملونة وأبواق نصر لو حطمت الرقم القياسي العام للمنصة!</li>
                </ul>
            </div>
            <button type="button" id="start-game-btn" class="btn-start-adventure">إبدأ تحدي الـ دقيقتين! ⏱️💀</button>
        </div>

        <div id="game-playground-zone" style="display: none;">
            <div class="timers-wrapper">
                <div class="round-total-timer">⏱️ المتبقي: <span id="round-timer-count">120</span>s</div>
                <div class="ghost-streak-badge">💀 الـ Streak: <span id="streak-count">0</span></div>
            </div>

            <div class="word-card">
                <div class="controls-container">
                    <button type="button" id="speak-word-btn" class="speaker-btn" title="اضغط للاستماع">🔊</button>
                </div>
                <div class="word-display" id="word-text">Loading...</div>
                <div class="ghost-timer" id="countdown-ui">تجهّز...</div>
                <div class="word-translation" id="translation-text">الترجمة: جاري التحميل...</div>
            </div>

            <div class="feedback-zone" id="game-feedback"></div>
            <div>
                <input type="text" id="word-field" class="word-input" placeholder="انتظر اختفاء الكلمة..." autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" disabled>
            </div>
            <div>
                <button id="skip-btn" class="btn-skip">تخطي الكلمة الحالية ⏭️</button>
            </div>
        </div>

        <div id="game-over-zone" class="game-over-box" style="display: none;">
            <h2 style="color: #f43f5e; margin-bottom: 10px; font-size: clamp(20px, 5vw, 26px);">🏁 انتهى وقت الجولة!</h2>
            <p style="color: #cbd5e1; font-size: 15px;">لقد صمدت بقوة أشباح خارقة وكانت نتيجتك النهائية هي:</p>
            <div style="font-size: 42px; font-weight: bold; color: #4ade80; margin: 15px 0;" id="final-score-display">0 ⚡</div>
            <div id="server-response-msg" class="server-status-msg">جاري مزامنة وحفظ النقاط بالسيرفر... 🔄</div>
            <button type="button" id="restart-game-btn" class="btn-start-adventure" style="box-shadow: 0 0 20px rgba(74, 222, 128, 0.4); margin-top: 20px;">اللعب جولة جديدة 🔄</button>
        </div>
    </div>
</div>

<script>
    const originalWordsBank = <?php echo json_encode($all_words); ?>;
    let activeWordsPool = [...originalWordsBank];
    const globalHighestScore = <?php echo $global_highest_score; ?>;
</script>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        // حماية الصفحة ضد النسخ
        document.addEventListener('contextmenu', event => event.preventDefault());
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && (e.key === 'c' || e.key === 'v' || e.key === 'x' || e.key === 'u' || e.key === 'I')) {
                e.preventDefault();
            }
        });

        // نظام توليد الصوت الحيوي الذكي والمستقر برمجياً
        const audioCtx = new(window.AudioContext || window.webkitAudioContext)();

        function playBeep(frequency, duration, type = "sine", volume = 0.1) {
            try {
                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();
                osc.type = type;
                osc.frequency.value = frequency;
                gain.gain.setValueAtTime(volume, audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.00001, audioCtx.currentTime + duration);
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                osc.start();
                osc.stop(audioCtx.currentTime + duration);
            } catch (e) {
                console.log("Audio play blocked");
            }
        }

        function soundTick() {
            playBeep(600, 0.05, "triangle", 0.15);
        }

        function soundSuccess() {
            playBeep(880, 0.15, "sine", 0.2);
            setTimeout(() => playBeep(1320, 0.2, "sine", 0.2), 80);
        }

        function soundError() {
            playBeep(220, 0.3, "sawtooth", 0.2);
        }

        function soundGameOver() {
            playBeep(440, 0.2, "square", 0.2);
            setTimeout(() => playBeep(330, 0.4, "square", 0.2), 200);
        }

        function soundNewRecordTrumpet() {
            const notes = [523.25, 659.25, 783.99, 1046.50];
            notes.forEach((freq, index) => {
                setTimeout(() => {
                    playBeep(freq, 0.3, "square", 0.2);
                }, index * 150);
            });
        }

        function launchConfettiFestival() {
            soundNewRecordTrumpet();
            var duration = 4 * 1000;
            var end = Date.now() + duration;

            (function frame() {
                confetti({
                    particleCount: 5,
                    angle: 60,
                    spread: 55,
                    origin: {
                        x: 0,
                        y: 0.8
                    },
                    colors: ['#7c3aed', '#a78bfa', '#38bdf8', '#4ade80', '#fbbf24']
                });
                confetti({
                    particleCount: 5,
                    angle: 120,
                    spread: 55,
                    origin: {
                        x: 1,
                        y: 0.8
                    },
                    colors: ['#7c3aed', '#a78bfa', '#38bdf8', '#4ade80', '#fbbf24']
                });

                if (Date.now() < end) {
                    requestAnimationFrame(frame);
                }
            }());
        }

        const startGameBtn = document.getElementById("start-game-btn");
        const restartGameBtn = document.getElementById("restart-game-btn");
        const startScreenZone = document.getElementById("start-screen-zone");
        const gamePlaygroundZone = document.getElementById("game-playground-zone");
        const gameOverZone = document.getElementById("game-over-zone");
        const wordText = document.getElementById("word-text");
        const translationText = document.getElementById("translation-text");
        const wordField = document.getElementById("word-field");
        const gameFeedback = document.getElementById("game-feedback");
        const streakCount = document.getElementById("streak-count");
        const skipBtn = document.getElementById("skip-btn");
        const speakWordBtn = document.getElementById("speak-word-btn");
        const countdownUi = document.getElementById("countdown-ui");
        const roundTimerCount = document.getElementById("round-timer-count");
        const finalScoreDisplay = document.getElementById("final-score-display");
        const serverResponseMsg = document.getElementById("server-response-msg");

        let streak = 0;
        let currentWordObj = {};
        let isSolved = false;
        let isGameRunning = false;

        let ghostTimeout = null;
        let wordCountdownInterval = null;
        let roundTimerInterval = null;

        let typingSecondsLeft = 15;
        let isTypingPhase = false;
        let totalRoundTime = 120;

        function speakCurrentWord() {
            if (!currentWordObj.word || !isGameRunning) return;
            window.speechSynthesis.cancel();
            let utterance = new SpeechSynthesisUtterance(currentWordObj.word.trim());
            utterance.lang = 'en-US';
            utterance.rate = 0.8;
            window.speechSynthesis.speak(utterance);
        }

        speakWordBtn.addEventListener("click", speakCurrentWord);
        startGameBtn.addEventListener("click", startNewRound);
        restartGameBtn.addEventListener("click", startNewRound);

        function startNewRound() {
            if (audioCtx.state === 'suspended') {
                audioCtx.resume();
            }

            startScreenZone.style.display = "none";
            gameOverZone.style.display = "none";
            gamePlaygroundZone.style.display = "block";

            streak = 0;
            streakCount.textContent = streak;
            totalRoundTime = 120;
            roundTimerCount.textContent = totalRoundTime;
            isGameRunning = true;

            activeWordsPool = [...originalWordsBank];
            activeWordsPool.sort(() => Math.random() - 0.5);

            startRoundGlobalTimer();
            loadWordChallenge();
        }

        function startRoundGlobalTimer() {
            clearInterval(roundTimerInterval);
            roundTimerInterval = setInterval(() => {
                totalRoundTime--;
                if (totalRoundTime >= 0) {
                    roundTimerCount.textContent = totalRoundTime;
                    if (totalRoundTime <= 5 && totalRoundTime > 0) {
                        soundTick();
                    }
                }
                if (totalRoundTime <= 0) {
                    endGameRound();
                }
            }, 1000);
        }

        function endGameRound() {
            isGameRunning = false;
            clearInterval(roundTimerInterval);
            clearInterval(wordCountdownInterval);
            clearTimeout(ghostTimeout);
            window.speechSynthesis.cancel();

            soundGameOver();

            gamePlaygroundZone.style.display = "none";
            gameOverZone.style.display = "block";
            finalScoreDisplay.textContent = `${streak} ⚡`;
            serverResponseMsg.textContent = "جاري مزامنة وحفظ النقاط بالسيرفر... 🔄";

            if (streak > globalHighestScore && globalHighestScore > 0) {
                launchConfettiFestival();
            }

            sendScoreToServer(streak);
        }

        function sendScoreToServer(finalScore) {
            fetch('save_ghost_score.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        score: finalScore
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        serverResponseMsg.style.color = "#4ade80";
                        serverResponseMsg.textContent = data.message;
                        confetti({
                            particleCount: 150,
                            spread: 80,
                            origin: {
                                y: 0.6
                            }
                        });
                        setTimeout(() => {
                            window.location.reload();
                        }, 3500);
                    } else if (data.status === 'no_change') {
                        serverResponseMsg.style.color = "#cbd5e1";
                        serverResponseMsg.textContent = "👍 جولة رائعة، لكنها لم تتخطَ رقمك القياسي السابق.";
                    } else {
                        serverResponseMsg.style.color = "#fbbf24";
                        serverResponseMsg.textContent = data.message;
                    }
                })
                .catch(error => {
                    serverResponseMsg.style.color = "#f87171";
                    serverResponseMsg.textContent = "⚠️ عذراً، تعذر الاتصال بالسيرفر لحفظ النتيجة.";
                });
        }

        function loadWordChallenge() {
            if (!isGameRunning) return;

            clearTimeout(ghostTimeout);
            clearInterval(wordCountdownInterval);

            if (activeWordsPool.length === 0) {
                activeWordsPool = [...originalWordsBank];
                activeWordsPool.sort(() => Math.random() - 0.5);
            }

            currentWordObj = activeWordsPool[0];
            isSolved = false;
            isTypingPhase = false;

            wordText.textContent = currentWordObj.word;
            wordText.classList.remove("ghost-vanished");
            translationText.innerHTML = `🎯 الترجمة العربية: <strong>${currentWordObj.translation}</strong>`;

            wordField.value = "";
            wordField.className = "word-input";
            wordField.disabled = true;
            wordField.placeholder = "احفظ الكلمة الآن...";
            gameFeedback.textContent = "";

            speakCurrentWord();

            let showTimeLeft = 5;
            countdownUi.className = "ghost-timer";
            countdownUi.textContent = `👁️ تختفي بعد: ${showTimeLeft}s`;

            wordCountdownInterval = setInterval(() => {
                showTimeLeft--;
                if (showTimeLeft > 0) {
                    countdownUi.textContent = `👁️ تختفي بعد: ${showTimeLeft}s`;
                } else {
                    clearInterval(wordCountdownInterval);
                }
            }, 1000);

            ghostTimeout = setTimeout(() => {
                if (!isGameRunning) return;
                wordText.textContent = "???";
                wordText.classList.add("ghost-vanished");

                isTypingPhase = true;
                wordField.disabled = false;
                wordField.placeholder = "اكتب الكلمة المتلاشية هنا...";
                wordField.focus();

                startTypingCountdown();
            }, 5000);
        }

        function startTypingCountdown() {
            typingSecondsLeft = 15;
            countdownUi.className = "ghost-timer typing-time";
            countdownUi.textContent = `⏳ اكتب سريعاً! الوقت المتبقي: ${typingSecondsLeft}s`;

            wordCountdownInterval = setInterval(() => {
                typingSecondsLeft--;
                if (typingSecondsLeft > 0) {
                    countdownUi.textContent = `⏳ اكتب سريعاً! الوقت المتبقي: ${typingSecondsLeft}s`;
                    if (typingSecondsLeft <= 4) {
                        soundTick();
                    }
                } else {
                    clearInterval(wordCountdownInterval);
                    timeOutFailure();
                }
            }, 1000);
        }

        function timeOutFailure() {
            soundError();
            streak = Math.max(0, streak - 2);
            streakCount.textContent = streak;
            isSolved = true;
            wordField.disabled = true;
            wordField.classList.add("input-error");

            gameFeedback.className = "feedback-zone text-error";
            gameFeedback.innerHTML = `⏰ انتهى وقت الكلمة! (-2 نقطة) الكلمة هي: <span style="color:#fff;">${currentWordObj.word}</span>`;

            activeWordsPool.shift();
            setTimeout(loadWordChallenge, 2200);
        }

        // تعديل الفحص لدعم زر المسافة/Enter وزر الذهاب (Go/Done) في لوحة مفاتيح الهاتف
        wordField.addEventListener("input", (e) => {
            if (isSolved || !isTypingPhase || !isGameRunning) return;

            let typed = wordField.value;
            let correct = currentWordObj.word.trim();

            // فحص إذا قام اللاعب بضغط مسافة على كيبورد الموبايل
            if (typed.endsWith(" ")) {
                checkWordSubmission(typed.trim(), correct);
            }
        });

        wordField.addEventListener("keydown", (e) => {
            if (isSolved || !isTypingPhase || !isGameRunning) return;
            if (e.key === "Enter") {
                e.preventDefault();
                checkWordSubmission(wordField.value.trim(), currentWordObj.word.trim());
            }
        });

        function checkWordSubmission(typed, correct) {
            if (typed.toLowerCase() === correct.toLowerCase() && typed !== "") {
                clearInterval(wordCountdownInterval);
                isSolved = true;
                soundSuccess();

                let pointsEarned = 2;
                let speedMessage = "🎯 على الحافة!";

                if (typingSecondsLeft >= 10) {
                    pointsEarned = 6;
                    speedMessage = "⚡ سرعة شبحية خارقة!";
                } else if (typingSecondsLeft >= 5) {
                    pointsEarned = 4;
                    speedMessage = "🔋 تركيز رائع وسريع!";
                }

                streak += pointsEarned;
                streakCount.textContent = streak;
                wordText.classList.remove("ghost-vanished");
                wordText.innerHTML = `<span style="color: #4ade80;">${correct}</span>`;
                gameFeedback.className = "feedback-zone text-success";
                gameFeedback.innerHTML = `✓ ${speedMessage} (+ ${pointsEarned} ستريك)`;
                wordField.disabled = true;
                speakCurrentWord();
                activeWordsPool.shift();
                setTimeout(loadWordChallenge, 2000);

            } else if (typed !== "") {
                clearInterval(wordCountdownInterval);
                isSolved = true;
                soundError();

                streak = Math.max(0, streak - 2);
                streakCount.textContent = streak;
                gameFeedback.className = "feedback-zone text-error";
                gameFeedback.innerHTML = `✗ خطأ إملائي! (-2 نقطة) الكلمة الصحيحة: <span style="color:#fff;">${correct}</span>`;
                wordField.classList.add("input-error");
                wordField.disabled = true;
                activeWordsPool.shift();
                setTimeout(loadWordChallenge, 2200);
            }
        }

        skipBtn.addEventListener("click", () => {
            if (!isGameRunning) return;
            clearInterval(wordCountdownInterval);
            window.speechSynthesis.cancel();
            soundError();
            streak = Math.max(0, streak - 2);
            streakCount.textContent = streak;
            activeWordsPool.shift();
            loadWordChallenge();
        });
    });
</script>

<?php include 'includes/footer.php'; ?>