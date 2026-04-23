<?php
session_start();
include("config/db.php");

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = $_POST['email'];
    $password = $_POST['password'];

    $sql    = "SELECT * FROM users WHERE email='$email'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $user_id = $user['id'];

            $check   = "SELECT * FROM user_profile WHERE user_id='$user_id'";
            $result2 = $conn->query($check);

            if ($result2 && $result2->num_rows > 0) {
                header("Location: dashboard.php");
                exit();
            } else {
                header("Location: assessment.php");
                exit();
            }

        } else {
            $error = "Incorrect password. Please try again.";
        }
    } else {
        $error = "No account found with that email.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Login - NutriSense</title>
    <link rel="stylesheet" href="login.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
 
</head>

<body>

<!-- NAVBAR -->
<nav class="navbar-custom">
    <div class="nav-brand">
        <h2 id="name">Nutri<span>Sense</span></h2>
        <span class="nav-tagline">Where Your Lifestyle Meets Insight</span>
    </div>
    <a href="signup.php" class="nav-signup-link">New here? Create account →</a>
</nav>

<!-- STARFIELD -->
<canvas id="stars"></canvas>

<!-- LOGIN CARD -->
<div class="login-card">

    <div class="card-icon">🌿</div>
    <h2 class="card-title">Welcome back</h2>
    <p class="card-subtitle">Good to see you again. <a href="signup.php">New here?</a></p>

    <?php if ($error): ?>
        <div class="error-box">
            <span>⚠</span>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" id="loginForm" novalidate>

        <!-- Email -->
        <div class="field-wrap">
            <label class="field-label" for="email">Email address</label>
            <div class="input-icon-wrap">
                <span class="input-icon">✉</span>
                <input type="email" id="email" name="email" placeholder="you@email.com" required
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
        </div>

        <!-- Password -->
        <div class="field-wrap">
            <div class="pw-row">
                <label class="field-label" for="password">Password</label>
                <a href="forgot.php" class="forgot-link">Forgot password?</a>
            </div>
            <div class="input-icon-wrap">
                <span class="input-icon">🔒</span>
                <input type="password" id="password" name="password" placeholder="Your password" required>
            </div>
        </div>

        <!-- Remember me -->
        <div class="remember-wrap">
            <input type="checkbox" id="remember" name="remember">
            <label for="remember">Keep me signed in</label>
        </div>

        <!-- Submit -->
        <button type="submit" class="btn-login" id="loginBtn">
            <div class="spinner" id="spinner"></div>
            <span id="btnText">Sign in to NutriSense</span>
        </button>

    </form>

    <div class="divider"><hr><span>or</span><hr></div>

    <!-- Google -->
    <button class="btn-google">
        <svg width="18" height="18" viewBox="0 0 48 48">
            <path fill="#FFC107" d="M43.6 20H24v8h11.3C33.6 33.1 29.3 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3 0 5.7 1.1 7.8 2.9l5.7-5.7C34.1 6.5 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20c11 0 19.7-8 19.7-20 0-1.3-.1-2.7-.1-4z"/>
            <path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 15.1 19 12 24 12c3 0 5.7 1.1 7.8 2.9l5.7-5.7C34.1 6.5 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/>
            <path fill="#4CAF50" d="M24 44c5.2 0 9.9-1.8 13.5-4.7l-6.2-5.2C29.4 35.6 26.8 36 24 36c-5.2 0-9.6-2.9-11.6-7.2l-6.6 5.1C9.6 40.1 16.3 44 24 44z"/>
            <path fill="#1976D2" d="M43.6 20H24v8h11.3c-.9 2.4-2.5 4.4-4.6 5.8l6.2 5.2C40.8 35.8 44 30.3 44 24c0-1.3-.1-2.7-.4-4z"/>
        </svg>
        Continue with Google
    </button>

    <p class="card-footer-link">
        Don't have an account? <a href="signup.php">Sign up free</a>
    </p>

</div>

<script>
// ── FORM SUBMIT — loading state ──
document.getElementById('loginForm').addEventListener('submit', function() {
    const btn     = document.getElementById('loginBtn');
    const spinner = document.getElementById('spinner');
    const btnText = document.getElementById('btnText');
    btn.disabled          = true;
    spinner.style.display = 'block';
    btnText.textContent   = 'Signing in...';
});

// ── STARFIELD ──
const canvas = document.getElementById("stars");
const ctx    = canvas.getContext("2d");
canvas.width  = window.innerWidth;
canvas.height = window.innerHeight;

let stars = [];
let mouse = { x: null, y: null };

for (let i = 0; i < 120; i++) {
    stars.push({
        x:        Math.random() * canvas.width,
        y:        Math.random() * canvas.height,
        radius:   Math.random() * 1.8 + 0.4,
        alpha:    Math.random(),
        speed:    Math.random() * 0.012 + 0.004,
        isBright: Math.random() < 0.15,
        isGreen:  Math.random() < 0.2
    });
}

window.addEventListener("mousemove", e => { mouse.x = e.x; mouse.y = e.y; });

function animate() {
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
        glow.addColorStop(0.5, `rgba(255,255,255,${star.alpha * 0.4})`);
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
    requestAnimationFrame(animate);
}

animate();

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