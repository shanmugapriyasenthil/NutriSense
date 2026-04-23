<?php
session_start();
include("config/db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 🔷 FETCH USERNAME
$user_sql = "SELECT name FROM users WHERE id='$user_id'";
$user_res = $conn->query($user_sql);
$user_data = $user_res->fetch_assoc();
$username = $user_data['name'] ?? 'User';
$first_name = explode(' ', $username)[0];
$user_avatar_type = $user_data['avatar'] ?? 'adventurer';

function getHealthSuggestion($sleep, $water, $activity, $food, $stress, $score) {
    $output = "";
    if ($score >= 80) $output .= "🟢 Excellent Health Condition\n\n";
    elseif ($score >= 60) $output .= "🟡 Good, but needs improvement\n\n";
    else $output .= "🔴 Health needs attention\n\n";

    $output .= "📊 Analysis:\n";
    $output .= ($sleep < 6)    ? "- Not enough sleep\n"       : "- Sleep is adequate\n";
    $output .= ($water < 2)    ? "- Low water intake\n"       : "- Hydration is good\n";
    $output .= ($activity < 3) ? "- Low activity\n"           : "- Activity is good\n";
    $output .= ($food < 3)     ? "- Poor diet\n"              : "- Diet is good\n";
    $output .= ($stress > 3)   ? "- High stress\n"            : "- Stress is under control\n";

    $output .= "\n💡 Action Plan:\n";
    if ($sleep < 6)    $output .= "• Sleep 7–8 hours\n";
    if ($water < 2)    $output .= "• Drink more water\n";
    if ($activity < 3) $output .= "• Exercise daily\n";
    if ($food < 3)     $output .= "• Improve diet\n";
    if ($stress > 3)   $output .= "• Reduce stress\n";
    if ($score >= 80)  $output .= "• Maintain current lifestyle 👍\n";

    return $output;
}

// 🔷 FETCH LATEST HEALTH DATA
$sql = "SELECT * FROM daily_health WHERE user_id='$user_id' ORDER BY date DESC LIMIT 1";
$result = $conn->query($sql);

$health_score = null;
$sleep = $water = $activity = $food = $stress = 0;

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $health_score = $row['health_score'];
    $sleep        = $row['sleep_hours'];
    $water        = $row['water_intake'];
    $activity     = $row['activity_level'];
    $food         = $row['food_quality'];
    $stress       = $row['stress_level'];
}

// 🔷 AVATAR EXPRESSION
if ($health_score === null)      { $eyes = "default";   $mouth = "smile"; }
elseif ($health_score <= 40)     { $eyes = "closed";    $mouth = "sad"; }
elseif ($health_score <= 70)     { $eyes = "default";   $mouth = "serious"; }
elseif ($health_score <= 90)     { $eyes = "happy";     $mouth = "smile"; }
else                              { $eyes = "surprised"; $mouth = "twinkle"; }

$avatar_url = "https://api.dicebear.com/7.x/avataaars/svg?seed=$user_id&eyes=$eyes&mouth=$mouth";

// 🔷 CATEGORY & COLOR
$category = "No Data";
$score_color = "#888";
if ($health_score !== null) {
    if      ($health_score >= 80) { $category = "Excellent"; $score_color = "#4CAF50"; }
    elseif  ($health_score >= 60) { $category = "Good";      $score_color = "#FFC107"; }
    elseif  ($health_score >= 40) { $category = "Moderate";  $score_color = "#FF9800"; }
    else                          { $category = "Poor";       $score_color = "#f44336"; }
}

// 🔷 GRAPH DATA
$history_sql = "SELECT date, health_score FROM daily_health WHERE user_id='$user_id' ORDER BY date DESC LIMIT 7";
$history_result = $conn->query($history_sql);
$dates = []; $scores = [];
while ($row = $history_result->fetch_assoc()) {
    $dates[]  = $row['date'];
    $scores[] = $row['health_score'];
}
$dates  = array_reverse($dates);
$scores = array_reverse($scores);

// 🔷 STREAK
$streak_sql = "SELECT streak FROM users WHERE id='$user_id'";
$res    = $conn->query($streak_sql);
$data   = $res->fetch_assoc();
$streak = $data['streak'] ?? 0;

// 🔷 SUGGESTION
$suggestion = ($health_score !== null)
    ? getHealthSuggestion($sleep,$water,$activity,$food,$stress,$health_score)
    : "Log today's data to get your personalized health suggestion.";

// 🔷 GREETING
$hour = (int)date('H');
if ($hour < 12)      $greeting = "Good morning";
elseif ($hour < 17)  $greeting = "Good afternoon";
else                 $greeting = "Good evening";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Dashboard – NutriSense</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --green:        #4CAF50;
            --green-dark:   #2e7d32;
            --green-glow:   rgba(76,175,80,0.25);
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
        }

        /* ── STARFIELD ── */
        #stars {
            position: fixed;
            inset: 0;
            z-index: 0;
            background: radial-gradient(ellipse at 40% 20%, #0d2b18, #0a1a10 55%, #050d08);
        }

        /* ── NAVBAR ── */
        .navbar {
            position: fixed;
            top: 0; width: 100%;
            height: 64px;
            padding: 0 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(10,26,16,0.6);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--glass-border);
            z-index: 1000;
        }

        .nav-brand {
            display: flex;
            flex-direction: column;
        }

        .nav-brand h1 {
            font-family: 'Playfair Display', serif;
            font-size: 20px;
            font-weight: 700;
            color: #fff;
            line-height: 1;
            cursor: pointer;
        }

        .nav-brand h1 span { color: var(--green); }

        .nav-brand small {
            font-size: 10px;
            color: var(--text-muted);
            letter-spacing: 0.8px;
            margin-top: 2px;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .nav-user {
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .nav-avatar {
            width: 34px; height: 34px;
            border-radius: 50%;
            border: 2px solid rgba(76,175,80,0.5);
            background: rgba(76,175,80,0.1);
            overflow: hidden;
            display: flex; align-items: center; justify-content: center;
        }

        .nav-avatar img { width: 34px; height: 34px; }

        .nav-username {
            font-size: 13px;
            font-weight: 500;
            color: var(--text-light);
        }

        .nav-streak {
            display: flex;
            align-items: center;
            gap: 5px;
            background: rgba(255,152,0,0.12);
            border: 1px solid rgba(255,152,0,0.25);
            color: #FFB74D;
            font-size: 12px;
            font-weight: 500;
            padding: 4px 10px;
            border-radius: 20px;
        }

        .btn-logout {
            font-size: 12.5px;
            color: var(--text-muted);
            text-decoration: none;
            padding: 5px 12px;
            border: 1px solid var(--glass-border);
            border-radius: 8px;
            transition: all 0.2s;
        }
        .btn-logout:hover { color: #fff; border-color: rgba(255,255,255,0.25); }

        /* ── MAIN LAYOUT ── */
        .main {
            position: relative;
            z-index: 10;
            max-width: 1200px;
            margin: 0 auto;
            padding: 24px 24px 40px;
        }

        /* ── GREETING HEADER ── */
        .page-header {
            margin-bottom: 24px;
        }

        .page-header .greeting {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 4px;
        }

        .page-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 700;
            color: #fff;
            line-height: 1.2;
        }

        .page-header h2 span { color: var(--green); }

        /* ── GRID LAYOUTS ── */
        .grid-top {
            display: grid;
            grid-template-columns: 240px 1fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }

        .grid-mid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }

        .grid-bottom {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        /* ── GLASS CARD ── */
        .card {
            background: var(--glass-bg);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        .card-label {
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 12px;
        }

        /* ── AVATAR CARD ── */
        .avatar-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-align: center;
        }

        .avatar-ring {
            width: 110px; height: 110px;
            border-radius: 50%;
            border: 3px solid rgba(76,175,80,0.35);
            background: rgba(76,175,80,0.08);
            display: flex; align-items: center; justify-content: center;
            position: relative;
        }

        .avatar-ring img { width: 95px; height: 95px; }

        .avatar-name {
            font-family: 'Playfair Display', serif;
            font-size: 17px;
            font-weight: 700;
            color: #fff;
        }

        .avatar-tag {
            font-size: 11px;
            padding: 3px 10px;
            border-radius: 20px;
            font-weight: 500;
        }

        .tag-excellent { background: rgba(76,175,80,0.15);  color: #81C784; border: 1px solid rgba(76,175,80,0.25); }
        .tag-good      { background: rgba(255,193,7,0.15);  color: #FFD54F; border: 1px solid rgba(255,193,7,0.25); }
        .tag-moderate  { background: rgba(255,152,0,0.15);  color: #FFB74D; border: 1px solid rgba(255,152,0,0.25); }
        .tag-poor      { background: rgba(244,67,54,0.15);  color: #EF9A9A; border: 1px solid rgba(244,67,54,0.25); }
        .tag-nodata    { background: rgba(255,255,255,0.07);color: #aaa;    border: 1px solid rgba(255,255,255,0.12); }

        /* ── SCORE CARD ── */
        .score-card {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .score-big {
            font-family: 'Playfair Display', serif;
            font-size: 4rem;
            font-weight: 700;
            line-height: 1;
            color: <?php echo $score_color; ?>;
        }

        .score-denom {
            font-size: 1.2rem;
            color: var(--text-muted);
            font-weight: 400;
        }

        .score-bar-wrap {
            margin-top: 14px;
        }

        .score-bar-track {
            height: 6px;
            background: rgba(255,255,255,0.08);
            border-radius: 10px;
            overflow: hidden;
        }

        .score-bar-fill {
            height: 100%;
            border-radius: 10px;
            background: <?php echo $score_color; ?>;
            width: <?php echo ($health_score ?? 0); ?> ;
            transition: width 1s ease;
        }

        .score-bar-labels {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 5px;
        }

        /* ── METRICS CARD ── */
        .metrics-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
            margin-top: 4px;
        }

        .metric-item {
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--glass-border);
            border-radius: 10px;
            padding: 12px 10px;
            text-align: center;
        }

        .metric-icon { font-size: 18px; margin-bottom: 4px; }

        .metric-value {
            font-size: 17px;
            font-weight: 500;
            color: #fff;
        }

        .metric-name {
            font-size: 10.5px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .metric-bar {
            height: 3px;
            background: rgba(255,255,255,0.08);
            border-radius: 4px;
            margin-top: 6px;
            overflow: hidden;
        }

        .metric-bar-fill {
            height: 100%;
            border-radius: 4px;
            background: var(--green);
        }

        /* ── CHART CARD ── */
        .chart-wrap {
            height: 180px;
            position: relative;
            margin-top: 8px;
        }

        /* ── SUGGESTION CARD ── */
        .suggestion-text {
            font-size: 13.5px;
            color: var(--text-light);
            line-height: 1.75;
            white-space: pre-line;
        }

        /* ── HISTORY TABLE ── */
        .history-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13.5px;
            margin-top: 8px;
        }

        .history-table th {
            text-align: left;
            padding: 8px 10px;
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            color: var(--text-muted);
            border-bottom: 1px solid var(--glass-border);
        }

        .history-table td {
            padding: 10px 10px;
            color: var(--text-light);
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .history-table tr:last-child td { border-bottom: none; }

        .score-pill {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        /* ── STREAK BIG ── */
        .streak-big {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .streak-icon {
            font-size: 2.8rem;
            line-height: 1;
        }

        .streak-num {
            font-family: 'Playfair Display', serif;
            font-size: 3rem;
            font-weight: 700;
            color: #FFB74D;
            line-height: 1;
        }

        .streak-label {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        /* ── ACTION BUTTONS ── */
        .action-wrap {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: auto;
        }

        .btn-add {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px;
            background: var(--green);
            color: #fff;
            text-decoration: none;
            border-radius: 12px;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
            box-shadow: 0 4px 16px rgba(76,175,80,0.3);
        }

        .btn-add:hover {
            background: var(--green-dark);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(76,175,80,0.4);
        }

        .btn-secondary {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 11px;
            background: rgba(255,255,255,0.05);
            color: var(--text-light);
            text-decoration: none;
            border-radius: 12px;
            border: 1px solid var(--glass-border);
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            transition: all 0.2s;
        }

        .btn-secondary:hover {
            background: rgba(255,255,255,0.09);
            border-color: rgba(255,255,255,0.22);
        }

        /* ── NO DATA STATE ── */
        .no-data {
            text-align: center;
            padding: 30px 0;
            color: var(--text-muted);
            font-size: 14px;
        }

        .no-data-icon { font-size: 2.5rem; margin-bottom: 8px; }

        /* ── RESPONSIVE ── */
        @media (max-width: 900px) {
            .grid-top { grid-template-columns: 1fr 1fr; }
            .grid-top .avatar-card { grid-column: span 2; flex-direction: row; justify-content: flex-start; padding: 16px 20px; }
            .grid-mid, .grid-bottom { grid-template-columns: 1fr; }
        }

        @media (max-width: 560px) {
            .grid-top { grid-template-columns: 1fr; }
            .grid-top .avatar-card { grid-column: span 1; }
            .main { padding: 16px 14px 32px; }
            .score-big { font-size: 3rem; }
            .nav-right .nav-streak { display: none; }
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
    <div class="nav-right">
        <div class="nav-streak">🔥 <?php echo $streak; ?> day streak</div>
        <div class="nav-user">
            <div class="nav-avatar">
                <img src="https://api.dicebear.com/7.x/<?php echo $user_avatar_type; ?>/svg?seed=<?php echo $user_id; ?>" alt="avatar">
            </div>
            <span class="nav-username"><?php echo htmlspecialchars($first_name); ?></span>
        </div>
        <a href="logout.php" class="btn-logout">Sign out</a>
    </div>
</nav>

<!-- MAIN DASHBOARD -->
<main class="main">

    <!-- GREETING -->
    <div class="page-header">
        <p class="greeting"><?php echo $greeting; ?>, <?php echo htmlspecialchars($first_name); ?> 👋</p>
        <h2>Your Health <span>Dashboard</span></h2>
    </div>

    <!-- ROW 1: Avatar | Score | Metrics -->
    <div class="grid-top">

        <!-- Avatar Card -->
        <div class="card avatar-card">
            <div class="avatar-ring">
                <img src="<?php echo $avatar_url; ?>" alt="Your Avatar">
            </div>
            <div class="avatar-name"><?php echo htmlspecialchars($username); ?></div>
            <?php
                $tag_class = match($category) {
                    'Excellent' => 'tag-excellent',
                    'Good'      => 'tag-good',
                    'Moderate'  => 'tag-moderate',
                    'Poor'      => 'tag-poor',
                    default     => 'tag-nodata'
                };
            ?>
            <span class="avatar-tag <?php echo $tag_class; ?>"><?php echo $category; ?></span>
        </div>

        <!-- Score Card -->
        <div class="card score-card">
            <div class="card-label">Health Score</div>
            <?php if ($health_score !== null): ?>
                <div>
                    <span class="score-big"><?php echo $health_score; ?></span>
                    <span class="score-denom">/100</span>
                </div>
                <div class="score-bar-wrap">
                    <div class="score-bar-track">
                        <div class="score-bar-fill"></div>
                    </div>
                    <div class="score-bar-labels">
                        <span>0</span><span>50</span><span>100</span>
                    </div>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <div class="no-data-icon">📊</div>
                    <p>No score yet</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Streak + Actions Card -->
        <div class="card" style="display:flex; flex-direction:column; justify-content:space-between; gap:16px;">
            <div>
                <div class="card-label">Daily Streak</div>
                <div class="streak-big">
                    <div class="streak-icon">🔥</div>
                    <div>
                        <div class="streak-num"><?php echo $streak; ?></div>
                        <div class="streak-label">days in a row</div>
                    </div>
                </div>
            </div>
            <div class="action-wrap">
                <a href="daily_entry.php" class="btn-add">＋ Log Today's Data</a>
                <a href="logout.php" class="btn-secondary">↩ Sign Out</a>
            </div>
        </div>

    </div>

    <!-- ROW 2: Metrics | Suggestion -->
    <div class="grid-mid">

        <!-- Metrics Card -->
        <div class="card">
            <div class="card-label">Today's Vitals</div>
            <?php if ($health_score !== null): ?>
            <div class="metrics-grid">
                <div class="metric-item">
                    <div class="metric-icon">😴</div>
                    <div class="metric-value"><?php echo $sleep; ?>h</div>
                    <div class="metric-name">Sleep</div>
                    <div class="metric-bar"><div class="metric-bar-fill" style="width:<?php echo min(($sleep/9)*100,100); ?>%; background:#7986CB;"></div></div>
                </div>
                <div class="metric-item">
                    <div class="metric-icon">💧</div>
                    <div class="metric-value"><?php echo $water; ?>L</div>
                    <div class="metric-name">Water</div>
                    <div class="metric-bar"><div class="metric-bar-fill" style="width:<?php echo min(($water/4)*100,100); ?>%; background:#29B6F6;"></div></div>
                </div>
                <div class="metric-item">
                    <div class="metric-icon">🏃</div>
                    <div class="metric-value"><?php echo $activity; ?>/5</div>
                    <div class="metric-name">Activity</div>
                    <div class="metric-bar"><div class="metric-bar-fill" style="width:<?php echo ($activity/5)*100; ?>%;"></div></div>
                </div>
                <div class="metric-item">
                    <div class="metric-icon">🥗</div>
                    <div class="metric-value"><?php echo $food; ?>/5</div>
                    <div class="metric-name">Food</div>
                    <div class="metric-bar"><div class="metric-bar-fill" style="width:<?php echo ($food/5)*100; ?>%;"></div></div>
                </div>
                <div class="metric-item">
                    <div class="metric-icon">🧘</div>
                    <div class="metric-value"><?php echo $stress; ?>/5</div>
                    <div class="metric-name">Stress</div>
                    <div class="metric-bar"><div class="metric-bar-fill" style="width:<?php echo ($stress/5)*100; ?>%; background:#EF5350;"></div></div>
                </div>
                <div class="metric-item">
                    <div class="metric-icon">❤️</div>
                    <div class="metric-value"><?php echo $health_score; ?></div>
                    <div class="metric-name">Overall</div>
                    <div class="metric-bar"><div class="metric-bar-fill" style="width:<?php echo $health_score; ?>%; background:<?php echo $score_color; ?>;"></div></div>
                </div>
            </div>
            <?php else: ?>
                <div class="no-data">
                    <div class="no-data-icon">🩺</div>
                    <p>Log your first entry to see vitals</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Suggestion Card -->
        <div class="card">
            <div class="card-label">AI Health Suggestion</div>
            <div class="suggestion-text"><?php echo htmlspecialchars($suggestion); ?></div>
        </div>

    </div>

    <!-- ROW 3: Chart | History -->
    <div class="grid-bottom">

        <!-- Chart -->
        <div class="card">
            <div class="card-label">7-Day Progress</div>
            <?php if (!empty($dates)): ?>
                <div class="chart-wrap">
                    <canvas id="healthChart"></canvas>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <div class="no-data-icon">📈</div>
                    <p>No history yet — start logging!</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- History Table -->
        <div class="card">
            <div class="card-label">Score History</div>
            <?php if (!empty($dates)): ?>
            <table class="history-table">
                <tr>
                    <th>Date</th>
                    <th>Score</th>
                    <th>Status</th>
                </tr>
                <?php foreach ($dates as $i => $date):
                    $s = $scores[$i];
                    if      ($s >= 80) { $pill_bg = 'rgba(76,175,80,0.15)';  $pill_color = '#81C784'; $status = 'Excellent'; }
                    elseif  ($s >= 60) { $pill_bg = 'rgba(255,193,7,0.15)';  $pill_color = '#FFD54F'; $status = 'Good'; }
                    elseif  ($s >= 40) { $pill_bg = 'rgba(255,152,0,0.15)';  $pill_color = '#FFB74D'; $status = 'Moderate'; }
                    else               { $pill_bg = 'rgba(244,67,54,0.15)';  $pill_color = '#EF9A9A'; $status = 'Poor'; }
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($date); ?></td>
                    <td><strong><?php echo $s; ?>/100</strong></td>
                    <td>
                        <span class="score-pill" style="background:<?php echo $pill_bg; ?>; color:<?php echo $pill_color; ?>;">
                            <?php echo $status; ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php else: ?>
                <div class="no-data">
                    <div class="no-data-icon">📋</div>
                    <p>No history yet</p>
                </div>
            <?php endif; ?>
        </div>

    </div>

</main>

<script>
// ── CHART ──
const chartCanvas = document.getElementById('healthChart');
if (chartCanvas) {
    const chartCtx = chartCanvas.getContext('2d');
    const gradient = chartCtx.createLinearGradient(0, 0, 0, 180);
    gradient.addColorStop(0, 'rgba(76,175,80,0.35)');
    gradient.addColorStop(1, 'rgba(76,175,80,0)');

    new Chart(chartCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($dates); ?>,
            datasets: [{
                label: 'Health Score',
                data: <?php echo json_encode($scores); ?>,
                borderColor: '#4CAF50',
                backgroundColor: gradient,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#4CAF50',
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    grid: { color: 'rgba(255,255,255,0.05)' },
                    ticks: { color: 'rgba(255,255,255,0.4)', font: { size: 11 } }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: 'rgba(255,255,255,0.4)', font: { size: 11 } }
                }
            }
        }
    });
}

// ── STARFIELD ──
const canvas = document.getElementById("stars");
const ctx    = canvas.getContext("2d");
canvas.width  = window.innerWidth;
canvas.height = window.innerHeight;

let stars = [];
let mouse = { x: null, y: null };

for (let i = 0; i < 140; i++) {
    stars.push({
        x:        Math.random() * canvas.width,
        y:        Math.random() * canvas.height,
        radius:   Math.random() * 1.8 + 0.3,
        alpha:    Math.random(),
        speed:    Math.random() * 0.012 + 0.003,
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