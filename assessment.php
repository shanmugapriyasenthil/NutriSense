<?php
session_start();
// Ensure db.php exists in your config folder
include("config/db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Basic sanitization
    $age = mysqli_real_escape_string($conn, $_POST['age']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $height = mysqli_real_escape_string($conn, $_POST['height']);
    $weight = mysqli_real_escape_string($conn, $_POST['weight']);

    $sql = "INSERT INTO user_profile (user_id, age, gender, height, weight)
            VALUES ('$user_id', '$age', '$gender', '$height', '$weight')";

    if ($conn->query($sql) === TRUE) {
        header("Location: dashboard.php");
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NutriSense | Assessment</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assessment.css">
</head>
<body>

<div class="split-container">

    <div class="left-panel">
        <div class="intro-content">
            <h1>Welcome to NutriSense 🌿</h1>
            <p>
                Your journey to a healthier lifestyle starts here.
                We analyze your habits and physical data to provide 
                expert, personalized nutrition insights.
            </p>
            <ul>
                <li>✦ Personalized health insights</li>
                <li>✦ Smart nutrition tracking</li>
                <li>✦ AI-powered recommendations</li>
            </ul>
        </div>
    </div>

    <div class="right-panel">
        <div class="assessment-card">
            <h2>Initial Assessment</h2>

            <form method="POST">
                <div class="input-group">
                    <label>Age</label>
                    <input type="number" name="age" placeholder="How old are you?" required>
                </div>

                <div class="input-group">
                    <label>Gender</label>
                    <select name="gender" required>
                        <option value="" disabled selected>Select Gender</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="input-group">
                    <label>Height (cm)</label>
                    <input type="number" name="height" placeholder="e.g. 175" required>
                </div>

                <div class="input-group">
                    <label>Weight (kg)</label>
                    <input type="number" name="weight" placeholder="e.g. 70" required>
                </div>

                <button type="submit">Continue to Dashboard</button>
            </form>
        </div>
    </div>

</div>

</body>
</html>