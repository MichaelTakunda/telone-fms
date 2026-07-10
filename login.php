<?php
session_start();
require_once "config/db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        $sql = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ":email" => $email
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user["password"])) {
            $_SESSION["user_id"] = $user["user_id"];
            $_SESSION["full_name"] = $user["full_name"];
            $_SESSION["email"] = $user["email"];
            $_SESSION["role"] = $user["role"];

            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - TelOne FMS</title>
    <link rel="stylesheet" href="./assets/css/styles.css">
</head>
<body>

<div class="login-container">
    <h1>TelOne FMS</h1>
    <h2>Login</h2>

    <?php if (!empty($error)): ?>
        <div class="error-message">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" placeholder="Enter email address">
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Enter password">
        </div>

        <button type="submit">Login</button>
    </form>
</div>

</body>
</html>