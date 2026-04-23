<?php

session_start();
include("config/db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $avatar = $_POST['avatar'];

    // INSERT USER
    $sql = "INSERT INTO users (name, email, password) 
            VALUES ('$name', '$email', '$password')";

    if ($conn->query($sql) === TRUE) {

        $user_id = $conn->insert_id;

        // SAVE AVATAR
        $avatar_sql = "INSERT INTO avatar (user_id, avatar_type)
                       VALUES ('$user_id', '$avatar')";
        $conn->query($avatar_sql);

        // 🔥 REDIRECT TO LOGIN
        header("Location: login.php");
        exit();

    } else {
        echo "Error: " . $conn->error;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Signup - NutriSense</title>
    <link rel ="stylesheet" href ="signup.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

<!-- NAVBAR -->
<nav class="navbar-custom">
    <div class="nav-brand">
        <h2 id="name">🧬 Nutri<span>Sense</span></h2>
        <span class="nav-tagline">Where Your Lifestyle Meets Insight</span>
    </div>
    <a href="login.php" class="nav-login-link">Already a member? Sign in →</a>
</nav>

<!-- STARFIELD CANVAS -->
<canvas id="stars"></canvas>

<!-- SIGNUP CARD -->
<div class="signup-card">

    <div class="card-badge">Free to join</div>
    <h3 class="card-title">Create your account</h3>
    <p class="card-subtitle">Join thousands living healthier. <a href="login.php">Already a member?</a></p>

    <form method="POST" id="signupForm" novalidate>

        <!-- Name -->
        <div class="mb-3">
            <label class="form-label">Full name</label>
            <input type="text" name="name" class="form-control" placeholder="Your name" required>
        </div>

        <!-- Email -->
        <div class="mb-3">
            <label class="form-label">Email address</label>
            <input type="email" name="email" class="form-control" placeholder="you@email.com" required>
        </div>

        <!-- Password + strength meter -->
        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" id="passwordInput" class="form-control" placeholder="Min. 8 characters" minlength="8" required>
            <div class="strength-bar-wrap">
                <div class="strength-seg" id="seg1"></div>
                <div class="strength-seg" id="seg2"></div>
                <div class="strength-seg" id="seg3"></div>
                <div class="strength-seg" id="seg4"></div>
            </div>
            <div class="strength-label" id="strengthLabel"></div>
        </div>

        <!-- Avatar Selection -->
        <div class="mb-3">
            <span class="avatar-label">Choose your avatar</span>
            <div class="avatar-grid">
                <div class="avatar-option" onclick="selectAvatar(this, 'adventurer')">
                    <img src="https://api.dicebear.com/7.x/adventurer/svg?seed=Alex" width="54" alt="Avatar 1">
                </div>
                <div class="avatar-option" onclick="selectAvatar(this, 'bottts')">
                    <img src="https://api.dicebear.com/7.x/bottts/svg?seed=Max" width="54" alt="Avatar 2">
                </div>
                <div class="avatar-option" onclick="selectAvatar(this, 'pixel-art')">
                    <img src="https://api.dicebear.com/7.x/pixel-art/svg?seed=Sam" width="54" alt="Avatar 3">
                </div>
                <div class="avatar-option" onclick="selectAvatar(this, 'fun-emoji')">
                    <img src="https://api.dicebear.com/7.x/fun-emoji/svg?seed=Leo" width="54" alt="Avatar 4">
                </div>
            </div>
            <div class="avatar-error" id="avatarError">Please choose an avatar to continue.</div>
            <input type="hidden" name="avatar" id="avatar">
        </div>

        <!-- Terms -->
        <div class="terms-wrap">
            <input type="checkbox" id="terms" required>
            <label for="terms">
                I agree to NutriSense's <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>.
            </label>
        </div>

        <!-- Submit -->
        <button type="submit" class="btn-custom" id="submitBtn">
            <div class="spinner" id="spinner"></div>
            <span id="btnText">Sign Up</span>
        </button>

        <div class="divider"><hr><span>or</span><hr></div>

        <!-- Google OAuth -->
        <button type="button" class="btn-google">
            <svg width="18" height="18" viewBox="0 0 48 48">
                <path fill="#FFC107" d="M43.6 20H24v8h11.3C33.6 33.1 29.3 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3 0 5.7 1.1 7.8 2.9l5.7-5.7C34.1 6.5 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20c11 0 19.7-8 19.7-20 0-1.3-.1-2.7-.1-4z"/>
                <path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 15.1 19 12 24 12c3 0 5.7 1.1 7.8 2.9l5.7-5.7C34.1 6.5 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/>
                <path fill="#4CAF50" d="M24 44c5.2 0 9.9-1.8 13.5-4.7l-6.2-5.2C29.4 35.6 26.8 36 24 36c-5.2 0-9.6-2.9-11.6-7.2l-6.6 5.1C9.6 40.1 16.3 44 24 44z"/>
                <path fill="#1976D2" d="M43.6 20H24v8h11.3c-.9 2.4-2.5 4.4-4.6 5.8l6.2 5.2C40.8 35.8 44 30.3 44 24c0-1.3-.1-2.7-.4-4z"/>
            </svg>
            Continue with Google
        </button>

    </form>

    <p class="text-center mt-3" style="color: var(--text-muted); font-size:13.5px;">
        Already have an account? <a href="login.php" style="color:var(--green); text-decoration:none; font-weight:500;">Login</a>
    </p>

</div>

<!-- Toast notification -->
<div class="toast-success" id="toast">🌿 Account created! Redirecting...</div>

<script>
// ── AVATAR SELECTION ──
function selectAvatar(el, type) {
    document.querySelectorAll('.avatar-option').forEach(o => o.classList.remove('avatar-selected'));
    el.classList.add('avatar-selected');
    document.getElementById('avatar').value = type;
    document.getElementById('avatarError').style.display = 'none';
}

// ── PASSWORD STRENGTH ──
const pwInput = document.getElementById('passwordInput');
const segs = [1,2,3,4].map(i => document.getElementById('seg'+i));
const strengthLabel = document.getElementById('strengthLabel');
const levels = [
    { color: '#e57373', label: 'Weak' },
    { color: '#FFB74D', label: 'Fair' },
    { color: '#81C784', label: 'Good' },
    { color: '#4CAF50', label: 'Strong' },
];

pwInput.addEventListener('input', () => {
    const val = pwInput.value;
    let score = 0;
    if (val.length >= 8)          score++;
    if (/[A-Z]/.test(val))        score++;
    if (/[0-9]/.test(val))        score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    segs.forEach((s, i) => {
        s.style.background = i < score
            ? levels[score - 1].color
            : 'rgba(255,255,255,0.12)';
    });

    if (val.length === 0) {
        segs.forEach(s => s.style.background = 'rgba(255,255,255,0.12)');
        strengthLabel.textContent = '';
    } else {
        strengthLabel.textContent  = levels[score - 1]?.label || '';
        strengthLabel.style.color  = levels[score - 1]?.color || 'rgba(255,255,255,0.55)';
    }
});

// ── FORM SUBMIT ──
document.getElementById('signupForm').addEventListener('submit', function(e) {
    // Avatar validation
    if (!document.getElementById('avatar').value) {
        e.preventDefault();
        document.getElementById('avatarError').style.display = 'block';
        return;
    }

    // Terms validation
    if (!document.getElementById('terms').checked) {
        e.preventDefault();
        alert('Please agree to our Terms of Service to continue.');
        return;
    }

    // Loading state (form will POST naturally to PHP)
    const btn      = document.getElementById('submitBtn');
    const spinner  = document.getElementById('spinner');
    const btnText  = document.getElementById('btnText');
    btn.disabled          = true;
    spinner.style.display = 'block';
    btnText.textContent   = 'Creating account...';

    // Show toast (optional — remove if PHP redirects immediately)
    const toast = document.getElementById('toast');
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3000);
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
            let dx   = star.x - mouse.x;
            let dy   = star.y - mouse.y;
            let dist = Math.sqrt(dx * dx + dy * dy);
            if (dist < 100) { star.x += dx * 0.002; star.y += dy * 0.002; }
        }
    });

    requestAnimationFrame(animate);
}

animate();

// Resize: fix canvas + re-scatter stars
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