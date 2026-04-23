<?php
session_start();
include("config/db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$date = date("Y-m-d");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $sleep = $_POST['sleep'];
    $water = $_POST['water'];

    $a1 = $_POST['activity_q1'];
    $a2 = $_POST['activity_q2'];
    $activity = round(($a1 + $a2) / 2);

    $f1 = $_POST['food_q1'];
    $f2 = $_POST['food_q2'];
    $f3 = $_POST['food_q3'];
    $food = round(($f1 + $f2 + $f3) / 3);

    $s1 = $_POST['stress_q1'];
    $s2 = $_POST['stress_q2'];
    $s3 = $_POST['stress_q3'];
    $stress = round(($s1 + $s2 + $s3) / 3);

    $sleep_score    = ($sleep >= 7) ? 20 : (($sleep >= 5) ? 15 : 10);
    $water_score    = ($water >= 3) ? 20 : (($water >= 2) ? 15 : 10);
    $activity_score = ($activity / 5) * 20;
    $food_score     = ($food / 5) * 20;
    $stress_score   = ((5 - $stress) / 5) * 20;
    $health_score   = round($sleep_score + $water_score + $activity_score + $food_score + $stress_score);

    $sql = "INSERT INTO daily_health 
    (user_id, date, sleep_hours, water_intake, activity_level, food_quality, stress_level, health_score)
    VALUES ('$user_id', '$date', '$sleep', '$water', '$activity', '$food', '$stress', '$health_score')
    ON DUPLICATE KEY UPDATE
    sleep_hours='$sleep', water_intake='$water', activity_level='$activity',
    food_quality='$food', stress_level='$stress', health_score='$health_score'";

    if      ($health_score <= 40) { $avatar_state = "weak";   $level = 1; }
    elseif  ($health_score <= 70) { $avatar_state = "normal"; $level = 2; }
    elseif  ($health_score <= 90) { $avatar_state = "fit";    $level = 3; }
    else                          { $avatar_state = "strong"; $level = 4; }

    $check_sql = "SELECT date FROM daily_health WHERE user_id='$user_id' ORDER BY date DESC LIMIT 2";
    $res = $conn->query($check_sql);
    $dates_arr = [];
    while ($row = $res->fetch_assoc()) { $dates_arr[] = $row['date']; }

    $user_sql  = "SELECT streak FROM users WHERE id='$user_id'";
    $user_res  = $conn->query($user_sql);
    $user_data = $user_res->fetch_assoc();
    $current_streak = $user_data['streak'] ?? 0;

    if (count($dates_arr) > 1) {
        $last_date = $dates_arr[1];
        $yesterday = date("Y-m-d", strtotime("-1 day"));
        $current_streak = ($last_date == $yesterday) ? $current_streak + 1 : max(0, $current_streak - 1);
    } else {
        $current_streak = 1;
    }

    $conn->query("UPDATE users SET streak='$current_streak' WHERE id='$user_id'");
    $conn->query("INSERT INTO avatar (user_id, level, avatar_state) VALUES ('$user_id', '$level', '$avatar_state')
    ON DUPLICATE KEY UPDATE level='$level', avatar_state='$avatar_state'");

    if ($conn->query($sql) === TRUE) {
        header("Location: dashboard.php");
        exit();
    } else {
        $db_error = $conn->error;
    }
}

// Fetch username for navbar
$uq = $conn->query("SELECT name FROM users WHERE id='$user_id'");
$ud = $uq->fetch_assoc();
$first_name = explode(' ', $ud['name'] ?? 'User')[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Daily Entry – NutriSense</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">

    <style>
        :root {
            --green:        #4CAF50;
            --green-dark:   #2e7d32;
            --green-glow:   rgba(76,175,80,0.22);
            --glass-bg:     rgba(255,255,255,0.05);
            --glass-border: rgba(255,255,255,0.11);
            --text-muted:   rgba(255,255,255,0.50);
            --text-light:   rgba(255,255,255,0.85);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: #0a1a10;
            min-height: 100vh;
            color: #fff;
            padding-top: 72px;
            padding-bottom: 40px;
        }

        /* ── STARFIELD ── */
        #stars {
            position: fixed; inset: 0; z-index: 0;
            background: radial-gradient(ellipse at 40% 20%, #0d2b18, #0a1a10 55%, #050d08);
        }

        /* ── NAVBAR ── */
        .navbar {
            position: fixed; top: 0; width: 100%; height: 64px;
            padding: 0 28px;
            display: flex; align-items: center; justify-content: space-between;
            background: rgba(10,26,16,0.6);
            backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--glass-border);
            z-index: 1000;
        }

        .nav-brand h1 {
            font-family: 'Playfair Display', serif;
            font-size: 20px; font-weight: 700; color: #fff; cursor: pointer;
        }
        .nav-brand h1 span { color: var(--green); }
        .nav-brand small { font-size: 10px; color: var(--text-muted); letter-spacing: 0.8px; display: block; margin-top: 1px; }

        .nav-back {
            font-size: 13px; color: var(--text-muted); text-decoration: none;
            padding: 5px 14px; border: 1px solid var(--glass-border); border-radius: 8px;
            transition: all 0.2s;
        }
        .nav-back:hover { color: #fff; border-color: rgba(255,255,255,0.25); }

        /* ── MAIN ── */
        .main {
            position: relative; z-index: 10;
            max-width: 900px;
            margin: 0 auto;
            padding: 24px 24px 40px;
        }

        .page-header { margin-bottom: 22px; }
        .page-header .sub { font-size: 13px; color: var(--text-muted); margin-bottom: 4px; }
        .page-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 1.9rem; font-weight: 700; color: #fff;
        }
        .page-header h2 span { color: var(--green); }

        /* ── PROGRESS STEPS ── */
        .steps {
            display: flex; align-items: center; gap: 0;
            margin-bottom: 24px;
        }

        .step {
            display: flex; align-items: center; gap: 7px;
            font-size: 12px; font-weight: 500;
            color: var(--text-muted);
        }

        .step.active { color: var(--green); }
        .step.done   { color: rgba(76,175,80,0.6); }

        .step-dot {
            width: 26px; height: 26px; border-radius: 50%;
            border: 1.5px solid var(--glass-border);
            display: flex; align-items: center; justify-content: center;
            font-size: 11px;
        }

        .step.active .step-dot { border-color: var(--green); background: rgba(76,175,80,0.12); color: var(--green); font-weight: 700; }
        .step.done .step-dot   { border-color: rgba(76,175,80,0.4); background: rgba(76,175,80,0.08); }

        .step-line {
            flex: 1; height: 1px;
            background: var(--glass-border);
            margin: 0 8px;
        }

        /* ── GRID ── */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .form-grid .full { grid-column: span 2; }

        /* ── CARD ── */
        .card {
            background: var(--glass-bg);
            backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 20px;
        }

        .card-label {
            font-size: 11px; font-weight: 500; letter-spacing: 1px;
            text-transform: uppercase; color: var(--text-muted);
            margin-bottom: 14px;
            display: flex; align-items: center; gap: 7px;
        }

        .card-label-icon { font-size: 15px; }

        /* ── FIELD ── */
        .field { margin-bottom: 14px; }
        .field:last-child { margin-bottom: 0; }

        .field label {
            display: block; font-size: 12.5px; font-weight: 500;
            color: rgba(255,255,255,0.65); letter-spacing: 0.2px;
            margin-bottom: 6px;
        }

        .field input[type="number"],
        .field select {
            width: 100%; padding: 10px 14px;
            border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.14);
            background: rgba(255,255,255,0.07);
            color: #fff;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
            -webkit-appearance: none;
        }

        .field input[type="number"]::placeholder { color: rgba(255,255,255,0.25); }

        .field input:focus, .field select:focus {
            outline: none;
            border-color: var(--green);
            box-shadow: 0 0 0 3px var(--green-glow);
            background: rgba(255,255,255,0.10);
        }

        .field select option { background: #1a3a22; color: #fff; }

        /* ── SLIDER ── */
        .slider-wrap { display: flex; align-items: center; gap: 10px; }

        .slider-wrap input[type="range"] {
            flex: 1; -webkit-appearance: none; height: 4px;
            background: rgba(255,255,255,0.12); border-radius: 4px; outline: none; cursor: pointer;
        }

        .slider-wrap input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none; width: 18px; height: 18px;
            border-radius: 50%; background: var(--green);
            border: 2px solid #fff; cursor: pointer;
            box-shadow: 0 0 6px var(--green-glow);
        }

        .slider-val {
            min-width: 28px; text-align: center;
            font-size: 14px; font-weight: 500; color: var(--green);
            background: rgba(76,175,80,0.10); border: 1px solid rgba(76,175,80,0.2);
            border-radius: 7px; padding: 2px 6px;
        }

        .slider-hints {
            display: flex; justify-content: space-between;
            font-size: 11px; color: var(--text-muted); margin-top: 6px;
        }

        /* ── SUBMIT ── */
        .submit-card {
            display: flex; align-items: center; justify-content: space-between;
            gap: 14px; flex-wrap: wrap;
        }

        .submit-info { font-size: 13px; color: var(--text-muted); }
        .submit-info strong { color: #fff; }

        .btn-submit {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            padding: 13px 32px;
            background: var(--green); color: #fff; border: none;
            border-radius: 12px;
            font-family: 'DM Sans', sans-serif; font-size: 15px; font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 16px rgba(76,175,80,0.3);
        }

        .btn-submit:hover {
            background: var(--green-dark);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(76,175,80,0.4);
        }

        .btn-submit:active { transform: scale(0.98); }

        /* ── ERROR ── */
        .error-box {
            background: rgba(244,67,54,0.10); border: 1px solid rgba(244,67,54,0.25);
            color: #ef9a9a; font-size: 13px; padding: 10px 14px;
            border-radius: 10px; margin-bottom: 16px;
        }

        /* ── RESPONSIVE ── */
        @media (max-width: 600px) {
            .form-grid { grid-template-columns: 1fr; }
            .form-grid .full { grid-column: span 1; }
            .main { padding: 16px 14px 32px; }
            .steps { display: none; }
        }
    </style>
</head>

<body>

<!-- STARFIELD -->
<canvas id="stars"></canvas>

<!-- NAVBAR -->
<nav class="navbar">
    <div class="nav-brand">
        <h1>Nutri<span>Sense</span></h1>
        <small>Your personal nutrition companion</small>
    </div>
    <a href="dashboard.php" class="nav-back">← Back to Dashboard</a>
</nav>

<!-- MAIN -->
<main class="main">

    <div class="page-header">
        <p class="sub">📅 <?php echo date('l, F j, Y'); ?></p>
        <h2>Daily <span>Health Check</span></h2>
    </div>

    <!-- STEPS -->
    <div class="steps">
        <div class="step active"><div class="step-dot">1</div> Basics</div>
        <div class="step-line"></div>
        <div class="step active"><div class="step-dot">2</div> Activity</div>
        <div class="step-line"></div>
        <div class="step active"><div class="step-dot">3</div> Food</div>
        <div class="step-line"></div>
        <div class="step active"><div class="step-dot">4</div> Stress</div>
    </div>

    <?php if (!empty($db_error)): ?>
        <div class="error-box">⚠ Database error: <?php echo htmlspecialchars($db_error); ?></div>
    <?php endif; ?>

    <form method="POST" id="entryForm">
    <div class="form-grid">

        <!-- SLEEP & WATER -->
        <div class="card">
            <div class="card-label"><span class="card-label-icon">😴</span> Sleep</div>
            <div class="field">
                <label>How many hours did you sleep?</label>
                <input type="number" name="sleep" step="0.5" min="0" max="24" placeholder="e.g. 7.5" required>
            </div>
        </div>

        <div class="card">
            <div class="card-label"><span class="card-label-icon">💧</span> Hydration</div>
            <div class="field">
                <label>Water intake today (litres)</label>
                <input type="number" name="water" step="0.1" min="0" max="10" placeholder="e.g. 2.5" required>
            </div>
        </div>

        <!-- ACTIVITY -->
        <div class="card">
            <div class="card-label"><span class="card-label-icon">🏃</span> Activity</div>
            <div class="field">
                <label>Did you exercise today?</label>
                <select name="activity_q1" required>
                    <option value="" disabled selected>Select</option>
                    <option value="1">No exercise</option>
                    <option value="2">Light (walk, stretch)</option>
                    <option value="3">Moderate (jog, yoga)</option>
                    <option value="5">Intense (gym, sport)</option>
                </select>
            </div>
            <div class="field">
                <label>Overall activity level today</label>
                <div class="slider-wrap">
                    <input type="range" name="activity_q2" min="1" max="5" value="3"
                        oninput="document.getElementById('act2val').textContent = this.value">
                    <span class="slider-val" id="act2val">3</span>
                </div>
                <div class="slider-hints"><span>1 – Sitting all day</span><span>5 – Very active</span></div>
            </div>
        </div>

        <!-- FOOD -->
        <div class="card">
            <div class="card-label"><span class="card-label-icon">🥗</span> Food Quality</div>
            <div class="field">
                <label>Fruits & vegetables today?</label>
                <select name="food_q1" required>
                    <option value="" disabled selected>Select</option>
                    <option value="5">Yes, plenty</option>
                    <option value="3">A little</option>
                    <option value="1">None</option>
                </select>
            </div>
            <div class="field">
                <label>Junk / processed food?</label>
                <select name="food_q2" required>
                    <option value="" disabled selected>Select</option>
                    <option value="5">None at all</option>
                    <option value="3">A little</option>
                    <option value="1">A lot</option>
                </select>
            </div>
            <div class="field">
                <label>Meals on time?</label>
                <select name="food_q3" required>
                    <option value="" disabled selected>Select</option>
                    <option value="5">Yes, all meals</option>
                    <option value="2">Skipped some</option>
                </select>
            </div>
        </div>

        <!-- STRESS -->
        <div class="card full">
            <div class="card-label"><span class="card-label-icon">🧘</span> Stress & Mood</div>
            <div class="form-grid" style="padding:0; background:none; border:none; backdrop-filter:none;">
                <div class="field">
                    <label>Anxiety level today</label>
                    <div class="slider-wrap">
                        <input type="range" name="stress_q1" min="1" max="5" value="3"
                            oninput="document.getElementById('s1val').textContent = this.value">
                        <span class="slider-val" id="s1val">3</span>
                    </div>
                    <div class="slider-hints"><span>1 – Calm</span><span>5 – Very anxious</span></div>
                </div>
                <div class="field">
                    <label>Emotional disturbance</label>
                    <div class="slider-wrap">
                        <input type="range" name="stress_q2" min="1" max="5" value="3"
                            oninput="document.getElementById('s2val').textContent = this.value">
                        <span class="slider-val" id="s2val">3</span>
                    </div>
                    <div class="slider-hints"><span>1 – Stable</span><span>5 – Very disturbed</span></div>
                </div>
                <div class="field">
                    <label>Overall mood today</label>
                    <div class="slider-wrap">
                        <input type="range" name="stress_q3" min="1" max="5" value="3"
                            oninput="document.getElementById('s3val').textContent = this.value">
                        <span class="slider-val" id="s3val">3</span>
                    </div>
                    <div class="slider-hints"><span>1 – Very happy 😄</span><span>5 – Very sad 😞</span></div>
                </div>
            </div>
        </div>

        <!-- SUBMIT -->
        <div class="card full submit-card">
            <div class="submit-info">
                Logging for <strong><?php echo date('M j, Y'); ?></strong> as <strong><?php echo htmlspecialchars($first_name); ?></strong>
            </div>
            <button type="submit" class="btn-submit" id="submitBtn">
                <span id="btnText">Save & See My Score →</span>
            </button>
        </div>

    </div>
    </form>

</main>

<script>
// Loading state on submit
document.getElementById('entryForm').addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    const txt = document.getElementById('btnText');
    btn.disabled = true;
    txt.textContent = 'Saving...';
});

// ── STARFIELD ──
const canvas = document.getElementById("stars");
const ctx    = canvas.getContext("2d");
canvas.width  = window.innerWidth;
canvas.height = window.innerHeight;

let stars = [];
let mouse = { x: null, y: null };

for (let i = 0; i < 130; i++) {
    stars.push({
        x: Math.random() * canvas.width,
        y: Math.random() * canvas.height,
        radius: Math.random() * 1.8 + 0.3,
        alpha:  Math.random(),
        speed:  Math.random() * 0.012 + 0.003,
        isBright: Math.random() < 0.15,
        isGreen:  Math.random() < 0.2
    });
}

window.addEventListener("mousemove", e => { mouse.x = e.x; mouse.y = e.y; });

function animateStars() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    stars.forEach(star => {
        star.alpha += star.speed;
        if (star.alpha >= 1 || star.alpha <= 0) star.speed = -star.speed;

        let intensity = star.isBright ? 1.4 : 1;
        let col = star.isGreen
            ? `rgba(140,210,140,${star.alpha * intensity})`
            : `rgba(255,255,255,${star.alpha * intensity})`;

        let glow = ctx.createRadialGradient(star.x, star.y, 0, star.x, star.y, star.radius * 4);
        glow.addColorStop(0, col);
        glow.addColorStop(0.5, `rgba(255,255,255,${star.alpha * 0.35})`);
        glow.addColorStop(1, 'rgba(255,255,255,0)');

        ctx.beginPath();
        ctx.arc(star.x, star.y, star.radius, 0, Math.PI * 2);
        ctx.fillStyle = glow;
        ctx.fill();

        if (mouse.x !== null) {
            let dx = star.x - mouse.x, dy = star.y - mouse.y;
            let dist = Math.sqrt(dx * dx + dy * dy);
            if (dist < 100) { star.x += dx * 0.002; star.y += dy * 0.002; }
        }
    });
    requestAnimationFrame(animateStars);
}

animateStars();

window.addEventListener("resize", () => {
    canvas.width  = window.innerWidth;
    canvas.height = window.innerHeight;
    stars.forEach(s => {
        s.x = Math.random() * canvas.width;
        s.y = Math.random() * canvas.height;
    });
});
</script>

</body>
</html>