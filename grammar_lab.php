<?php 
include 'config/db.php';
include 'includes/header.php'; 

try {
    $cat_stmt = $pdo->query("SELECT DISTINCT category FROM grammar_challenges ORDER BY category ASC");
    $categories = $cat_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $categories = [];
}
?>

<style>

:root{
    --bg:#020617;
    --card:#0f172a;
    --text:#f8fafc;
    --muted:#94a3b8;
    --neon:#a855f7;
    --neon2:#7c3aed;
    --success:#22c55e;
    --danger:#ef4444;
}

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{

    background:
    radial-gradient(circle at top right, rgba(168,85,247,.15), transparent 30%),
    radial-gradient(circle at bottom left, rgba(124,58,237,.15), transparent 30%),
    var(--bg);

    color:var(--text);

    font-family:'Segoe UI',sans-serif;

    overflow-x:hidden;
}

.grammar-wrapper{

    min-height:100vh;

    width:100%;

    padding:25px;

    display:flex;
    justify-content:center;
    align-items:center;
}

.grammar-lab-box{

    width:100%;
    max-width:950px;

    background:rgba(15,23,42,.92);

    border:1px solid rgba(255,255,255,.08);

    border-radius:35px;

    padding:35px;

    position:relative;

    overflow:hidden;

    backdrop-filter:blur(14px);

    box-shadow:
    0 0 40px rgba(168,85,247,.12),
    0 25px 60px rgba(0,0,0,.5);
}

.grammar-lab-box::before{

    content:'';

    position:absolute;

    width:320px;
    height:320px;

    background:rgba(168,85,247,.12);

    border-radius:50%;

    top:-120px;
    right:-120px;

    filter:blur(30px);
}

/* HEADER */

.top-bar{

    display:flex;

    justify-content:space-between;

    align-items:center;

    gap:20px;

    flex-wrap:wrap;

    margin-bottom:30px;
}

.logo-title h1{

    font-size:38px;

    font-weight:800;

    line-height:1.3;

    background:linear-gradient(to right,#fff,#d8b4fe);

    -webkit-background-clip:text;

    -webkit-text-fill-color:transparent;
}

.logo-title span{

    color:var(--muted);

    font-size:14px;
}

/* SCORE */

.score-box{

    background:linear-gradient(135deg,var(--neon),var(--neon2));

    padding:16px 30px;

    border-radius:22px;

    text-align:center;

    box-shadow:0 12px 25px rgba(168,85,247,.35);
}

.score-box small{

    display:block;

    color:#ede9fe;

    margin-bottom:6px;
}

.score-box h2{

    font-size:30px;
}

/* SELECT */

.controls{

    margin-bottom:25px;

    display:flex;

    justify-content:center;
}

#category-select{

    width:260px;

    max-width:100%;

    background:#1e293b;

    color:white;

    border:none;

    outline:none;

    padding:14px 18px;

    border-radius:18px;

    font-size:15px;

    transition:.3s;

    box-shadow:0 8px 20px rgba(0,0,0,.3);
}

#category-select:focus{

    box-shadow:0 0 0 3px rgba(168,85,247,.35);
}

/* CARD */

.challenge-card{

    background:linear-gradient(145deg,#111827,#172554);

    padding:35px;

    border-radius:30px;

    text-align:center;

    margin-bottom:30px;

    border:1px solid rgba(255,255,255,.05);

    box-shadow:0 15px 35px rgba(0,0,0,.35);
}

.challenge-label{

    color:#c084fc;

    font-size:13px;

    letter-spacing:2px;

    text-transform:uppercase;

    margin-bottom:12px;
}

#grammar-hint-text{

    font-size:28px;

    font-weight:700;

    line-height:1.7;

    margin-bottom:25px;
}

/* HINT BUTTON */

.hint-btn{

    position:relative;

    display:inline-flex;

    align-items:center;

    justify-content:center;

    gap:12px;

    background:linear-gradient(135deg,#facc15,#f59e0b);

    color:white;

    border:none;

    padding:16px 38px;

    border-radius:20px;

    font-size:16px;

    font-weight:800;

    cursor:pointer;

    overflow:hidden;

    transition:.35s ease;

    box-shadow:
    0 12px 25px rgba(245,158,11,.35),
    0 0 25px rgba(250,204,21,.18);
}

.hint-btn::before{

    content:'';

    position:absolute;

    top:0;
    left:-120%;

    width:100%;
    height:100%;

    background:linear-gradient(
        to right,
        transparent,
        rgba(255,255,255,.35),
        transparent
    );

    transition:.6s;
}

.hint-btn:hover::before{
    left:120%;
}

.hint-btn:hover{

    transform:translateY(-5px) scale(1.04);

    box-shadow:
    0 18px 35px rgba(245,158,11,.45),
    0 0 35px rgba(250,204,21,.4);
}

.hint-icon{

    font-size:24px;

    animation:bulbGlow 2s infinite;
}

@keyframes bulbGlow{

    0%{
        transform:scale(1);
        filter:drop-shadow(0 0 0px #fde047);
    }

    50%{
        transform:scale(1.18);
        filter:drop-shadow(0 0 12px #fde047);
    }

    100%{
        transform:scale(1);
        filter:drop-shadow(0 0 0px #fde047);
    }
}

/* BUILD AREA */

.assembly-zone{

    min-height:120px;

    background:rgba(15,23,42,.7);

    border:2px dashed #334155;

    border-radius:25px;

    padding:25px;

    display:flex;

    flex-wrap:wrap;

    direction:ltr;

    justify-content:flex-start;

    align-items:center;

    gap:12px;

    transition:.3s;

    margin-bottom:30px;
}

.assembly-zone::before{

    content:'Build the sentence here ✨';

    color:#64748b;

    width:100%;

    font-size:14px;
}

.assembly-zone.has-items::before{
    display:none;
}

/* WORDS */

#shuffled-cubes-zone{

    display:flex;

    justify-content:center;

    align-items:center;

    gap:14px;

    flex-wrap:wrap;
}

.word-cube{

    background:linear-gradient(145deg,#1e293b,#111827);

    border:1px solid #334155;

    color:white;

    padding:15px 24px;

    border-radius:18px;

    cursor:pointer;

    font-weight:700;

    transition:.3s;

    user-select:none;

    position:relative;

    overflow:hidden;

    box-shadow:0 8px 20px rgba(0,0,0,.3);
}

.word-cube::before{

    content:'';

    position:absolute;

    top:0;
    left:-100%;

    width:100%;
    height:100%;

    background:linear-gradient(
        to right,
        transparent,
        rgba(255,255,255,.15),
        transparent
    );

    transition:.5s;
}

.word-cube:hover::before{
    left:100%;
}

.word-cube:hover{

    transform:translateY(-6px);

    border-color:var(--neon);

    box-shadow:0 0 20px rgba(168,85,247,.45);
}

.word-cube.selected{

    opacity:0;

    transform:scale(0);

    pointer-events:none;
}

/* BUILT WORDS */

.built-cube{

    background:linear-gradient(135deg,var(--neon),var(--neon2));

    color:white;

    padding:15px 24px;

    border-radius:16px;

    font-weight:bold;

    cursor:pointer;

    animation:pop .25s ease;

    box-shadow:0 10px 25px rgba(168,85,247,.35);
}

@keyframes pop{

    from{
        transform:scale(.5);
        opacity:0;
    }

    to{
        transform:scale(1);
        opacity:1;
    }
}

/* HINT EFFECT */

@keyframes pulse{

    0%{
        transform:scale(1);
        box-shadow:0 0 0 0 rgba(250,204,21,.7);
    }

    50%{
        transform:scale(1.12);
        box-shadow:0 0 0 18px rgba(250,204,21,0);
    }

    100%{
        transform:scale(1);
        box-shadow:0 0 0 0 rgba(250,204,21,0);
    }
}

.hint-highlight{

    animation:pulse 1.5s infinite;

    border:2px solid #facc15 !important;
}

/* SUCCESS & ERROR */

.success{

    border-color:var(--success)!important;

    box-shadow:0 0 25px rgba(34,197,94,.25);
}

.shake{

    animation:shake .3s;

    border-color:var(--danger)!important;
}

@keyframes shake{

    0%,100%{
        transform:translateX(0);
    }

    25%{
        transform:translateX(-10px);
    }

    75%{
        transform:translateX(10px);
    }
}

/* TABLET */

@media(max-width:768px){

    .grammar-wrapper{
        padding:18px;
    }

    .grammar-lab-box{
        padding:25px;
        border-radius:28px;
    }

    .top-bar{
        flex-direction:column;
        align-items:flex-start;
    }

    .score-box{
        width:100%;
    }

    .logo-title h1{
        font-size:28px;
    }

    #grammar-hint-text{
        font-size:22px;
    }
}

/* MOBILE */

@media(max-width:600px){

    .grammar-wrapper{
        padding:12px;
    }

    .grammar-lab-box{
        padding:18px;
        border-radius:24px;
    }

    .logo-title h1{
        font-size:22px;
    }

    .logo-title span{
        font-size:12px;
    }

    #grammar-hint-text{
        font-size:18px;
    }

    .challenge-card{
        padding:22px;
        border-radius:24px;
    }

    #category-select{
        width:100%;
        font-size:14px;
    }

    .hint-btn{
        width:100%;
        padding:14px;
        font-size:15px;
        border-radius:16px;
    }

    .hint-icon{
        font-size:22px;
    }

    .word-cube,
    .built-cube{
        padding:12px 16px;
        font-size:14px;
        border-radius:14px;
    }

    .assembly-zone{
        padding:18px;
        min-height:100px;
        gap:10px;
    }

    #shuffled-cubes-zone{
        gap:10px;
    }

    .score-box h2{
        font-size:24px;
    }
}

</style>

<div class="grammar-wrapper">

    <div class="grammar-lab-box">

        <div class="top-bar">

            <div class="logo-title">

                <h1> مختبر القواعد </h1>

                <span>تحدي قواعد اللغة الإنجليزية التفاعلي</span>

            </div>

            <div class="score-box">

                <small> Score</small>

                <h2 id="user-score">0</h2>

            </div>

        </div>

        <div class="controls">

            <select id="category-select">

                <option value="all">All Categories</option>

                <?php foreach ($categories as $cat): ?>

                    <option value="<?= htmlspecialchars($cat) ?>">

                        <?= htmlspecialchars($cat) ?>

                    </option>

                <?php endforeach; ?>

            </select>

        </div>

        <div class="challenge-card">

            <div class="challenge-label">

               التحدي

            </div>

            <div id="grammar-hint-text">

                Choose a category...

            </div>

            <button class="hint-btn" onclick="showHint()">

                <span class="hint-icon">💡</span>

                

            </button>

        </div>

        <div class="assembly-zone" id="assembly-display-zone"></div>

        <div id="shuffled-cubes-zone"></div>

    </div>

</div>

<script>

const sounds = {

    click:new Audio("assets/sounds/click.mp3"),

    correct:new Audio("assets/sounds/correct.mp3"),

    error:new Audio("assets/sounds/error.mp3")
};

let fetchedRules = [];

let currentIndex = 0;

let score = 0;

async function loadGrammarRules(){

    let cat = document.getElementById('category-select').value;

    let res = await fetch(
        `get-game-words.php?mode=grammar&category=${encodeURIComponent(cat)}`
    );

    let data = await res.json();

    fetchedRules = data.words || [];

    currentIndex = 0;

    renderRule();
}

function renderRule(){

    if(fetchedRules.length === 0) return;

    let rule = fetchedRules[currentIndex];

    document.getElementById('grammar-hint-text').textContent = rule.hint;

    let assemblyZone = document.getElementById('assembly-display-zone');

    let shuffledZone = document.getElementById('shuffled-cubes-zone');

    assemblyZone.innerHTML = "";

    shuffledZone.innerHTML = "";

    assemblyZone.classList.remove('success');

    let words = rule.text.split(" ");

    [...words]

    .sort(() => Math.random() - 0.5)

    .forEach(word => {

        let div = document.createElement('div');

        div.className = 'word-cube';

        div.textContent = word;

        div.onclick = () => {

            sounds.click.play();

            div.classList.add('selected');

            let built = document.createElement('div');

            built.className = 'built-cube';

            built.textContent = word;

            built.onclick = () => {

                built.remove();

                div.classList.remove('selected');

                toggleAssemblyPlaceholder();

                checkSentence(words);
            };

            assemblyZone.appendChild(built);

            toggleAssemblyPlaceholder();

            checkSentence(words);
        };

        shuffledZone.appendChild(div);
    });
}

function toggleAssemblyPlaceholder(){

    let assemblyZone = document.getElementById('assembly-display-zone');

    if(assemblyZone.children.length > 0){

        assemblyZone.classList.add('has-items');

    }else{

        assemblyZone.classList.remove('has-items');
    }
}

function showHint(){

    let original = fetchedRules[currentIndex].text.split(" ");

    let assemblyZone = document.getElementById('assembly-display-zone');

    let shuffledZone = document.getElementById('shuffled-cubes-zone');

    let assembled = Array.from(assemblyZone.children)
    .map(c => c.textContent);

    let nextWord = original.find(word => !assembled.includes(word));

    if(nextWord){

        let target = Array.from(shuffledZone.children)

        .find(c =>
            c.textContent === nextWord &&
            !c.classList.contains('selected')
        );

        if(target){

            target.classList.add('hint-highlight');

            setTimeout(() => {

                target.classList.remove('hint-highlight');

            },1500);
        }
    }
}

function checkSentence(originalWords){

    let assemblyZone = document.getElementById('assembly-display-zone');

    let userWords = Array.from(assemblyZone.children)
    .map(c => c.textContent);

    if(userWords.length === originalWords.length){

        if(JSON.stringify(userWords) === JSON.stringify(originalWords)){

            assemblyZone.classList.add('success');

            sounds.correct.play();

            let msg = new SpeechSynthesisUtterance(
                originalWords.join(" ")
            );

            msg.lang = 'en-US';

            window.speechSynthesis.speak(msg);

            score += 10;

            document.getElementById('user-score').textContent = score;

            setTimeout(() => {

                currentIndex++;

                if(currentIndex < fetchedRules.length){

                    renderRule();

                }else{

                    alert("🎉 انتصار! لقد أتقنت جميع قواعد النحو.");
                }

            },2200);

        }else{

            assemblyZone.classList.add('shake');

            sounds.error.play();

            setTimeout(() => {

                assemblyZone.classList.remove('shake');

            },500);
        }

    }else{

        assemblyZone.style.borderColor = "#334155";
    }
}

document.getElementById('category-select').onchange = loadGrammarRules;

loadGrammarRules();

</script>

<?php include 'includes/footer.php'; ?>