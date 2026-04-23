 <?php
// Using getenv() allows Render to safely inject your database secrets
$host = getenv('DB_HOST');
$username = getenv('DB_USER');
$password = getenv('DB_PASSWORD');
$database = getenv('DB_NAME');

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    // This will print a clear error in the Render logs if the DB fails
    error_log("Database Connection Failed: " . $conn->connect_error);
    die("Connection failed. Please check back later.");
}
?>